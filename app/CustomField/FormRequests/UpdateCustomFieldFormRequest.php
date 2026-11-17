<?php

declare(strict_types=1);

namespace App\CustomField\FormRequests;

use App\CustomField\Actions\UpdateCustomField;
use App\CustomField\CustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\CustomFieldValidationRules;
use App\CustomField\Exceptions\CouldNotFindCustomField;
use App\CustomField\Properties\Description;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\ObjectId;
use App\CustomField\Properties\Options;
use App\CustomField\Properties\Target;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

/**
 * Renames a field, changes its hint, or edits a select's options. Its record
 * type and its type stay as they are, so stored values keep their meaning.
 */
final class UpdateCustomFieldFormRequest extends FormRequest
{
    public CustomField $field;

    public function __construct()
    {
        parent::__construct();

        $this->field = CustomField::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindCustomField
     */
    public function authorize(): Response
    {
        $this->field = CustomFieldAccess::field($this);

        return CustomFieldAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => [
                'sometimes',
                ...Name::getValidationRulesForCreate($this),
                Rule::unique(Table::CustomFields->value, Name::getDatabaseTableColumnName())
                    ->where('team_id', $this->field->getTeamId()->toString())
                    ->where(Target::getDatabaseTableColumnName(), $this->field->getTarget()->toEnum()->value)
                    ->where(ObjectId::getDatabaseTableColumnName(), $this->field->getObjectId()->toNullableString())
                    ->ignore($this->field->getId()->toString()),
            ],
            Description::getNameForValidation() => ['sometimes', ...Description::getValidationRulesForCreate($this)],
            ...CustomFieldValidationRules::options($this->field->getType()->toEnum(), isUpdate: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Name::getNameForValidation().'.unique' => __('A field with that name already exists here, or in the archive.'),
            ...CustomFieldValidationRules::messages(),
        ];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        if (array_key_exists(Name::getNameForValidation(), $validated)) {
            $this->field->setName($this->string(Name::getNameForValidation())->trim()->toString());
        }

        if (array_key_exists(Description::getNameForValidation(), $validated)) {
            $this->field->setDescription($validated[Description::getNameForValidation()]);
        }

        if (array_key_exists(Options::getNameForValidation(), $validated)) {
            $this->field->setOptions(CustomFieldValidationRules::normalizeOptions(
                $validated[Options::getNameForValidation()],
                $this->field->getOptions()->toOptions(),
            ));
        }

        UpdateCustomField::fromCustomField($this->field);

        return back();
    }
}
