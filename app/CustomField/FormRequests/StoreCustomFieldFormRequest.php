<?php

declare(strict_types=1);

namespace App\CustomField\FormRequests;

use App\CustomField\Actions\CreateCustomField;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\CustomField\CustomFieldValidationRules;
use App\CustomField\Properties\Description;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\Options;
use App\CustomField\Properties\Relation;
use App\CustomField\Properties\Target;
use App\CustomField\Properties\Type;
use App\Shared\Table;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Adds a field to the bottom of one kind of record's custom fields.
 */
final class StoreCustomFieldFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return CustomFieldAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Target::getNameForValidation() => ['required', Rule::enum(CustomFieldTargetEnum::class)],
            Type::getNameForValidation() => ['required', Rule::enum(CustomFieldTypeEnum::class)],
            Name::getNameForValidation() => [
                ...Name::getValidationRulesForCreate($this),
                Rule::unique(Table::CustomFields->value, Name::getDatabaseTableColumnName())
                    ->where('team_id', $this->user()?->current_team_id)
                    ->where(Target::getDatabaseTableColumnName(), $this->string(Target::getNameForValidation())->toString()),
            ],
            Relation::getNameForValidation() => $this->string(Type::getNameForValidation())->toString() === CustomFieldTypeEnum::Relation->value
                ? ['required', Rule::enum(CustomFieldRelationEnum::class)]
                : ['prohibited'],
            Description::getNameForValidation() => Description::getValidationRulesForCreate($this),
            ...CustomFieldValidationRules::options(CustomFieldTypeEnum::tryFrom($this->string(Type::getNameForValidation())->toString())),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            Name::getNameForValidation().'.unique' => __('A field with that name already exists here, or in the archive.'),
            Relation::getNameForValidation().'.required' => __('Choose what the field links to.'),
            Relation::getNameForValidation().'.prohibited' => __('Only record links say what they link to.'),
            ...CustomFieldValidationRules::messages(),
        ];
    }

    public function process(): RedirectResponse
    {
        $teamId = (string) $this->user()?->current_team_id;
        $target = CustomFieldTargetEnum::from($this->string(Target::getNameForValidation())->toString());
        $type = CustomFieldTypeEnum::from($this->string(Type::getNameForValidation())->toString());
        $lastPosition = FindCustomField::listForTeam($teamId, $target, withArchived: true)
            ->map(fn (CustomField $field): int => $field->getPosition()->toInteger())
            ->max();

        $field = CreateCustomField::fromCustomField(
            CustomField::new()
                ->setTeamId($teamId)
                ->setTarget($target)
                ->setType($type)
                ->setRelation($type === CustomFieldTypeEnum::Relation ? $this->input(Relation::getNameForValidation()) : null)
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setDescription($this->input(Description::getNameForValidation()))
                ->setOptions($type->hasOptions() ? CustomFieldValidationRules::normalizeOptions($this->validated(Options::getNameForValidation())) : [])
                ->setPosition(is_int($lastPosition) ? $lastPosition + 1 : 0),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $field->getName()->toString()])]);

        return back();
    }
}
