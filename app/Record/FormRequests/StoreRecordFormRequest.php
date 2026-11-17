<?php

declare(strict_types=1);

namespace App\Record\FormRequests;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\CustomObject\CustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\CreateRecord;
use App\Record\Properties\Name;
use App\Record\Record;
use App\Record\RecordAccess;
use App\Record\RecordEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Adds a record to an object.
 */
final class StoreRecordFormRequest extends FormRequest
{
    public CustomObject $object;

    public function __construct()
    {
        parent::__construct();

        $this->object = CustomObject::new();
    }

    /**
     * @throws CouldNotFindCustomObject
     */
    public function authorize(): Response
    {
        $this->object = RecordAccess::object($this);

        return Gate::authorize('create', [Record::class, RecordAccess::blank($this->object)]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            ...CustomFieldValueRules::for($this->object->getTeamId()->toString(), CustomFieldTargetEnum::Record, objectId: $this->object->getId()->toString()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes($this->object->getTeamId()->toString(), CustomFieldTargetEnum::Record, $this->object->getId()->toString());
    }

    public function process(): RedirectResponse
    {
        $record = CreateRecord::fromRecord(
            RecordAccess::blank($this->object)
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setCustomFields(CustomFieldValueRules::merge(
                    $this->object->getTeamId()->toString(),
                    CustomFieldTargetEnum::Record,
                    new CustomFieldValues(null),
                    $this->validated(CustomFieldValues::getNameForValidation(), []),
                    $this->object->getId()->toString(),
                ))
                ->setCreatedById((string) $this->user()?->id),
        );

        RecordActivity::for(
            type: ActivityTypeEnum::RecordCreated,
            userId: (string) $this->user()?->id,
            teamId: $record->getTeamId()->toString(),
            subject: RecordEloquentModel::query()->withoutGlobalScopes()->findOrFail($record->getId()->toString()),
            details: ['name' => $record->getName()->toString(), 'object' => $this->object->getName()->toString()],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $record->getName()->toString()])]);

        return to_route('records.show', [$this->object->getId()->toString(), $record->getId()->toString()]);
    }
}
