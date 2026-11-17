<?php

declare(strict_types=1);

namespace App\Record\FormRequests;

use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Actions\UpdateRecord;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\Properties\Name;
use App\Record\Record;
use App\Record\RecordAccess;
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
    public Record $record;

    public function __construct()
    {
        parent::__construct();

        $this->record = Record::new();
    }

    /**
     * @throws CouldNotFindCustomObject
     * @throws CouldNotFindRecord
     */
    public function authorize(): Response
    {
        $this->record = RecordAccess::record($this, RecordAccess::object($this));

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

        UpdateRecord::fromRecord($this->record);

        return back();
    }
}
