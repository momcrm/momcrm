<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomObject\StandardObjectEnum;
use App\Record\Properties\Name as RecordName;
use App\Shared\Table;
use App\Shared\Traits\Enum;
use App\Shared\Traits\EnumOptions;
use App\Shared\Types;
use App\TeamUser\Properties\TeamId as MembershipTeamId;
use App\TeamUser\Properties\UserId as MembershipUserId;
use App\User\Properties\Name as UserName;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The kinds of record a "record link" custom field can point at: a team
 * member, any built-in kind of record (StandardObjectEnum), or a record of
 * one of the team's custom objects. Each knows how to find the team's
 * records of its kind and what to call them.
 *
 * Lookups only return what the signed-in person may see, so a picker or a
 * label never reveals a ticket or project in a members-only project.
 */
enum CustomFieldRelationEnum: string
{
    use Enum;
    use EnumOptions;

    case Member = 'member';
    case Customer = 'customer';
    case Contact = 'contact';
    case Ticket = 'ticket';
    case Project = 'project';
    case Record = 'record';

    public function label(): string
    {
        $object = $this->standardObject();

        return match (true) {
            $this === self::Member => 'A team member',
            $this === self::Record => 'A record of a custom object',
            $object !== null => 'A '.$object->labelSingular(),
            default => ucfirst($this->value),
        };
    }

    /**
     * The built-in kind of record the link points at; null for a team member
     * or a custom object's record.
     */
    public function standardObject(): ?StandardObjectEnum
    {
        return StandardObjectEnum::tryFrom($this->value);
    }

    /**
     * The names of the team's records with these ids, by id. Ids that are
     * not the team's, no longer exist, or are out of the person's sight are
     * left out. For records of a custom object, `$objectId` says which object.
     *
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    public function names(string $teamId, array $ids, ?string $objectId = null): array
    {
        if ($ids === []) {
            return [];
        }

        $object = $this->standardObject();

        if ($object !== null) {
            return $this->titled($object, $object->visibleQuery($teamId, self::viewerId())->whereKey($ids));
        }

        return $this->named($this->records($teamId, $objectId)->whereIn($this->idColumn(), $ids));
    }

    /**
     * The team's records whose name contains the search, as picker options.
     * Everything, up to the limit, when the search is empty.
     *
     * @return list<array{value: string, label: string}>
     */
    public function search(string $teamId, string $search, int $limit = 20, ?string $objectId = null): array
    {
        $object = $this->standardObject();

        if ($object !== null) {
            $query = $object->visibleQuery($teamId, self::viewerId())->limit($limit);
            $object->search($query, $search);
            $names = $this->titled($object, $query);
        } else {
            $query = $this->records($teamId, $objectId)->limit($limit);
            $column = $this->nameColumn();

            if ($search !== '') {
                $query->where($column, 'like', "%{$search}%");
            }

            $names = $this->named($query->orderBy($column));
        }

        $options = [];

        foreach ($names as $id => $name) {
            $options[] = ['value' => $id, 'label' => $name];
        }

        return $options;
    }

    /**
     * Whose sight lookups are limited to: the signed-in person. With nobody
     * signed in, only what every member may see is found.
     */
    private static function viewerId(): string
    {
        return Types::nullableString(Auth::id()) ?? '';
    }

    /**
     * @param  EloquentBuilder<covariant Model>  $query
     * @return array<string, string>
     */
    private function titled(StandardObjectEnum $object, EloquentBuilder $query): array
    {
        $names = [];

        foreach ($query->get() as $record) {
            $names[Types::string($record->getKey())] = $object->title($record);
        }

        return $names;
    }

    /**
     * The team's members, or the records of one of its custom objects.
     */
    private function records(string $teamId, ?string $objectId): Builder
    {
        return $this === self::Member
            ? DB::table(Table::Users->value)
                ->join(Table::TeamUser->value, MembershipUserId::getDatabaseTableColumnNameWithTable(Table::TeamUser->value), '=', $this->idColumn())
                ->where(MembershipTeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser->value), $teamId)
            : DB::table(Table::Records->value)->where('team_id', $teamId)->where('object_id', $objectId);
    }

    private function idColumn(): string
    {
        return ($this === self::Member ? Table::Users->value : Table::Records->value).'.id';
    }

    private function nameColumn(): string
    {
        return $this === self::Member
            ? UserName::getDatabaseTableColumnNameWithTable(Table::Users->value)
            : RecordName::getDatabaseTableColumnNameWithTable(Table::Records->value);
    }

    /**
     * @return array<string, string>
     */
    private function named(Builder $query): array
    {
        $names = [];

        foreach ($query->get(["{$this->idColumn()} as record_id", "{$this->nameColumn()} as record_name"]) as $row) {
            $names[Types::string($row->record_id)] = Types::string($row->record_name);
        }

        return $names;
    }
}
