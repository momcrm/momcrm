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
use App\CustomField\Properties\ObjectId;
use App\CustomField\Properties\Options;
use App\CustomField\Properties\Relation;
use App\CustomField\Properties\RelationObjectId;
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
                    ->where(Target::getDatabaseTableColumnName(), $this->string(Target::getNameForValidation())->toString())
                    ->where(ObjectId::getDatabaseTableColumnName(), $this->objectId()),
            ],
            ObjectId::getNameForValidation() => $this->string(Target::getNameForValidation())->toString() === CustomFieldTargetEnum::Record->value
                ? ['required', 'uuid', Rule::exists(Table::CustomObjects->value, 'id')->where('team_id', $this->user()?->current_team_id)]
                : ['prohibited'],
            Relation::getNameForValidation() => $this->string(Type::getNameForValidation())->toString() === CustomFieldTypeEnum::Relation->value
                ? ['required', Rule::enum(CustomFieldRelationEnum::class)]
                : ['prohibited'],
            RelationObjectId::getNameForValidation() => $this->string(Relation::getNameForValidation())->toString() === CustomFieldRelationEnum::Record->value
                ? ['required', 'uuid', Rule::exists(Table::CustomObjects->value, 'id')->where('team_id', $this->user()?->current_team_id)->whereNull('archived_at')]
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
            RelationObjectId::getNameForValidation().'.required' => __('Choose the object the field links to.'),
            RelationObjectId::getNameForValidation().'.exists' => __('Choose one of your objects to link to.'),
            RelationObjectId::getNameForValidation().'.prohibited' => __('Only links to a custom object name one.'),
            ObjectId::getNameForValidation().'.required' => __('Choose the object the field is for.'),
            ObjectId::getNameForValidation().'.exists' => __('Choose one of your objects.'),
            ObjectId::getNameForValidation().'.prohibited' => __('Only fields on custom objects name an object.'),
            Relation::getNameForValidation().'.prohibited' => __('Only record links say what they link to.'),
            ...CustomFieldValidationRules::messages(),
        ];
    }

    public function process(): RedirectResponse
    {
        $teamId = (string) $this->user()?->current_team_id;
        $target = CustomFieldTargetEnum::from($this->string(Target::getNameForValidation())->toString());
        $type = CustomFieldTypeEnum::from($this->string(Type::getNameForValidation())->toString());
        $lastPosition = FindCustomField::listForTeam($teamId, $target, withArchived: true, objectId: $this->objectId())
            ->map(fn (CustomField $field): int => $field->getPosition()->toInteger())
            ->max();

        $field = CreateCustomField::fromCustomField(
            CustomField::new()
                ->setTeamId($teamId)
                ->setTarget($target)
                ->setObjectId($this->objectId())
                ->setType($type)
                ->setRelation($type === CustomFieldTypeEnum::Relation ? $this->input(Relation::getNameForValidation()) : null)
                ->setRelationObjectId($type === CustomFieldTypeEnum::Relation ? $this->input(RelationObjectId::getNameForValidation()) : null)
                ->setName($this->string(Name::getNameForValidation())->trim()->toString())
                ->setDescription($this->input(Description::getNameForValidation()))
                ->setOptions($type->hasOptions() ? CustomFieldValidationRules::normalizeOptions($this->validated(Options::getNameForValidation())) : [])
                ->setPosition(is_int($lastPosition) ? $lastPosition + 1 : 0),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $field->getName()->toString()])]);

        return back();
    }

    /**
     * The custom object the field is for, when it is for one.
     */
    private function objectId(): ?string
    {
        return $this->string(Target::getNameForValidation())->toString() === CustomFieldTargetEnum::Record->value
            ? $this->string(ObjectId::getNameForValidation())->toString()
            : null;
    }
}
