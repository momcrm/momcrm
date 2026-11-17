<?php

declare(strict_types=1);

namespace App\Record\FormRequests;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\CustomObject\CustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\UpdateRecord;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\Properties\Name;
use App\Record\Record;
use App\Record\RecordAccess;
use App\Record\RecordEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Changes a record in place, one or a few fields at a time. Fields left out
 * keep their value.
 */
final class UpdateRecordFormRequest extends FormRequest
{
    public CustomObject $object;

    public Record $record;

    public function __construct()
    {
        parent::__construct();

        $this->object = CustomObject::new();
        $this->record = Record::new();
    }

    /**
     * @throws CouldNotFindCustomObject
     * @throws CouldNotFindRecord
     */
    public function authorize(): Response
    {
        $this->object = RecordAccess::object($this);
        $this->record = RecordAccess::record($this, $this->object);

        return Gate::authorize('update', $this->record);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => ['sometimes', ...Name::getValidationRulesForCreate($this)],
            ...CustomFieldValueRules::for($this->record->getTeamId()->toString(), CustomFieldTargetEnum::Record, $this->record->getCustomFields(), $this->record->getObjectId()->toString()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes($this->record->getTeamId()->toString(), CustomFieldTargetEnum::Record, $this->record->getObjectId()->toString());
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $before = $this->record->toSummary();

        if (array_key_exists(Name::getNameForValidation(), $validated)) {
            $this->record->setName($this->string(Name::getNameForValidation())->trim()->toString());
        }

        if (array_key_exists(CustomFieldValues::getNameForValidation(), $validated)) {
            $this->record->setCustomFields(CustomFieldValueRules::merge(
                $this->record->getTeamId()->toString(),
                CustomFieldTargetEnum::Record,
                $this->record->getCustomFields(),
                $validated[CustomFieldValues::getNameForValidation()],
                $this->record->getObjectId()->toString(),
            ));
        }

        $record = UpdateRecord::fromRecord($this->record);
        $changed = $this->changedFieldNames($before, $record->toSummary());

        if ($changed !== []) {
            RecordActivity::for(
                type: ActivityTypeEnum::RecordUpdated,
                userId: (string) $this->user()?->id,
                teamId: $record->getTeamId()->toString(),
                subject: RecordEloquentModel::query()->withoutGlobalScopes()->findOrFail($record->getId()->toString()),
                details: ['name' => $record->getName()->toString(), 'object' => $this->object->getName()->toString(), 'fields' => $changed],
            );
        }

        return back();
    }

    /**
     * The names of what a save changed, for the activity feed: "Name" and
     * the object's fields whose value is different.
     *
     * @param  array{name: string, custom_fields: array<string, mixed>}  $before
     * @param  array{name: string, custom_fields: array<string, mixed>}  $after
     * @return list<string>
     */
    private function changedFieldNames(array $before, array $after): array
    {
        $changed = $before['name'] === $after['name'] ? [] : ['Name'];

        foreach (FindCustomField::listForTeam($this->record->getTeamId()->toString(), CustomFieldTargetEnum::Record, objectId: $this->record->getObjectId()->toString()) as $field) {
            $id = $field->getId()->toString();

            if (($before['custom_fields'][$id] ?? null) != ($after['custom_fields'][$id] ?? null)) {
                $changed[] = $field->getName()->toString();
            }
        }

        return $changed;
    }
}
