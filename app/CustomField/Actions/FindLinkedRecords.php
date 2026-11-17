<?php

declare(strict_types=1);

namespace App\CustomField\Actions;

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomField;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldQuery;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomField\Properties\ArchivedAt;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\Position;
use App\CustomField\Properties\Relation;
use App\CustomField\Properties\RelationObjectId;
use App\CustomField\Properties\TeamId;
use App\CustomField\Properties\Type;
use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\Project\ProjectEloquentModel;
use App\Project\ProjectVisibility;
use App\Record\RecordEloquentModel;
use App\Shared\Types;
use App\Ticket\TicketEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The other side of record links: what points at a record through a
 * record-link custom field, such as the tickets whose "Vehicle" is this van.
 */
final class FindLinkedRecords
{
    public const int PER_GROUP = 10;

    /**
     * Every record that links to this one, grouped by the field that links
     * it: the kind of record the field is on, the field's name, how many
     * records link and the first few of them.
     *
     * Only what the person may see is listed: tickets and projects obey
     * project visibility, and archived objects and fields are left out.
     *
     * @param  ?string  $objectId  The custom object of the record linked to, when `$kind` is a custom object's record.
     * @return list<array{key: string, source: string, field: string, count: int, records: list<array{id: string, title: string, url: string}>}>
     */
    public static function to(string $teamId, string $userId, CustomFieldRelationEnum $kind, string $id, ?string $objectId = null): array
    {
        $objects = FindCustomObject::listForTeam($teamId);
        $groups = [];

        foreach (self::fieldsLinkingTo($teamId, $kind, $objectId) as $field) {
            $target = $field->getTarget()->toEnum();
            $object = $objects->first(fn (CustomObject $object): bool => $field->getObjectId()->equalsTo($object->getId()->toString()));

            if ($target === CustomFieldTargetEnum::Record && $object->isNull()) {
                continue;
            }

            $query = self::records($target, $teamId, $userId, $object);
            CustomFieldQuery::filter($query, $field, $id);
            $count = $query->count();

            if ($count === 0) {
                continue;
            }

            $groups[] = [
                'key' => $field->getId()->toString(),
                'source' => $target === CustomFieldTargetEnum::Record ? $object->getNamePlural()->toString() : $target->label(),
                'field' => $field->getName()->toString(),
                'count' => $count,
                'records' => array_values($query->limit(self::PER_GROUP)->get()->map(fn (Model $model): array => self::item($target, $model))->all()),
            ];
        }

        return $groups;
    }

    /**
     * The team's live record-link fields, on any kind of record, that point
     * at this kind.
     *
     * @return list<CustomField>
     */
    private static function fieldsLinkingTo(string $teamId, CustomFieldRelationEnum $kind, ?string $objectId): array
    {
        return array_values(CustomFieldEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(Type::getDatabaseTableColumnName(), CustomFieldTypeEnum::Relation->value)
            ->where(Relation::getDatabaseTableColumnName(), $kind->value)
            ->where(RelationObjectId::getDatabaseTableColumnName(), $kind === CustomFieldRelationEnum::Record ? $objectId : null)
            ->whereNull(ArchivedAt::getDatabaseTableColumnName())
            ->orderBy(Position::getDatabaseTableColumnName())
            ->orderBy(Name::getDatabaseTableColumnName())
            ->get()
            ->map(fn (CustomFieldEloquentModel $model): CustomField => CustomField::fromEloquentModel($model))
            ->all());
    }

    /**
     * The team's records of a kind that the person may see, newest first.
     *
     * @return Builder<covariant Model>
     */
    private static function records(CustomFieldTargetEnum $target, string $teamId, string $userId, CustomObject $object): Builder
    {
        $query = match ($target) {
            CustomFieldTargetEnum::Ticket => TicketEloquentModel::query(),
            CustomFieldTargetEnum::Customer => CustomerEloquentModel::query(),
            CustomFieldTargetEnum::Contact => ContactEloquentModel::query(),
            CustomFieldTargetEnum::Project => ProjectEloquentModel::query(),
            CustomFieldTargetEnum::Record => RecordEloquentModel::query(),
        };

        $query->withoutGlobalScopes()->where('team_id', $teamId)->latest();

        match ($target) {
            CustomFieldTargetEnum::Ticket => ProjectVisibility::constrainTickets($query->getQuery(), $userId),
            CustomFieldTargetEnum::Project => ProjectVisibility::constrainProjects($query->getQuery(), $userId),
            CustomFieldTargetEnum::Record => $query->where('object_id', $object->getId()->toString()),
            default => null,
        };

        return $query;
    }

    /**
     * @return array{id: string, title: string, url: string}
     */
    private static function item(CustomFieldTargetEnum $target, Model $model): array
    {
        return match (true) {
            $model instanceof TicketEloquentModel => ['id' => $model->id, 'title' => "#{$model->number} {$model->title}", 'url' => route('tickets.show', $model->id)],
            $model instanceof CustomerEloquentModel => ['id' => $model->id, 'title' => $model->name, 'url' => route('customers.show', $model->id)],
            $model instanceof ContactEloquentModel => ['id' => $model->id, 'title' => trim("{$model->first_name} {$model->last_name}"), 'url' => route('contacts.edit', $model->id)],
            $model instanceof ProjectEloquentModel => ['id' => $model->id, 'title' => $model->name, 'url' => route('projects.show', $model->id)],
            $model instanceof RecordEloquentModel => ['id' => $model->id, 'title' => $model->name, 'url' => route('records.show', [$model->object_id, $model->id])],
            default => ['id' => Types::string($model->getKey()), 'title' => $target->label(), 'url' => ''],
        };
    }
}
