<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\AccessRule\Properties\Actions;
use App\AccessRule\Properties\Audience;
use App\AccessRule\Properties\Conditions;
use App\AccessRule\Properties\Effect;
use App\AccessRule\Properties\IsEnabled;
use App\AccessRule\Properties\Name;
use App\AccessRule\Properties\Resources;
use App\Shared\Types;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Rules shared by adding and editing an access rule. Beyond the shapes, each
 * action must belong to one of the chosen resources, and each condition must
 * use an attribute the rule can read, an operator that suits it and values
 * from its list.
 */
final class AccessRuleValidationRules
{
    public const int MAX_CONDITIONS = 10;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function for(FormRequest $request, bool $isNew = true): array
    {
        return [
            ...($isNew ? [Audience::getNameForValidation() => Audience::getValidationRulesForCreate($request)] : []),
            Name::getNameForValidation() => Name::getValidationRulesForCreate($request),
            Effect::getNameForValidation() => Effect::getValidationRulesForCreate($request),
            IsEnabled::getNameForValidation() => ['sometimes', 'boolean'],
            Resources::getNameForValidation() => ['present', 'array'],
            Resources::getNameForValidation().'.*' => ['distinct', Rule::enum(AccessResourceEnum::class)],
            Actions::getNameForValidation() => ['present', 'array'],
            Actions::getNameForValidation().'.*' => ['distinct', Rule::enum(AccessActionEnum::class)],
            Conditions::getNameForValidation() => ['present', 'array', 'max:'.self::MAX_CONDITIONS],
            Conditions::getNameForValidation().'.*' => ['array:attribute,operator,value'],
            Conditions::getNameForValidation().'.*.attribute' => ['required', Rule::enum(AccessAttributeEnum::class)],
            Conditions::getNameForValidation().'.*.operator' => ['required', Rule::enum(AccessOperatorEnum::class)],
            Conditions::getNameForValidation().'.*.value' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            Conditions::getNameForValidation().'.max' => __('A rule can have at most :max conditions.'),
        ];
    }

    /**
     * Checks that need the rule as a whole, run once the shapes are valid. A
     * new rule's audience comes from the form; an existing rule keeps its own.
     */
    public static function after(Validator $validator, string $teamId, string $userId, ?AccessAudienceEnum $audience = null): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        /** @var array{audience?: string, resources: array<int, string>, actions: array<int, string>, conditions: array<int, array{attribute: string, operator: string, value?: mixed}>} $data */
        $data = $validator->getData();
        $audience ??= AccessAudienceEnum::from($data['audience'] ?? AccessAudienceEnum::Staff->value);
        $resources = array_map(AccessResourceEnum::from(...), $data['resources']);
        $audienceResources = AccessResourceEnum::forAudience($audience);

        foreach ($resources as $resource) {
            if (! in_array($resource, $audienceResources, true)) {
                $validator->errors()->add('resources', __('Rules for :audience can\'t cover :resource.', [
                    'audience' => strtolower($audience->label()),
                    'resource' => strtolower($resource->label()),
                ]));

                return;
            }
        }

        foreach ($data['actions'] as $action) {
            $action = AccessActionEnum::from($action);
            $candidates = $resources === [] ? $audienceResources : $resources;

            if (! collect($candidates)->contains(fn (AccessResourceEnum $resource): bool => $resource->supports($action, $audience))) {
                $validator->errors()->add('actions', __('":action" doesn\'t apply to the chosen records.', ['action' => $action->label()]));
            }
        }

        foreach ($data['conditions'] as $index => $condition) {
            $attribute = AccessAttributeEnum::from($condition['attribute']);
            $operator = AccessOperatorEnum::from($condition['operator']);
            $value = $condition['value'] ?? null;

            if (! $attribute->isFor($audience)) {
                $validator->errors()->add("conditions.{$index}.attribute", __('":attribute" can\'t be used in rules for :audience.', [
                    'attribute' => $attribute->label(),
                    'audience' => strtolower($audience->label()),
                ]));

                continue;
            }

            if (! $attribute->appliesTo($resources)) {
                $validator->errors()->add("conditions.{$index}.attribute", __('":attribute" can only be used on a rule for :resource alone.', [
                    'attribute' => $attribute->label(),
                    'resource' => strtolower($attribute->resource()?->label() ?? ''),
                ]));

                continue;
            }

            if (! in_array($operator, $attribute->type()->operators(), true)) {
                $validator->errors()->add("conditions.{$index}.operator", __('Choose how to compare ":attribute".', ['attribute' => $attribute->label()]));

                continue;
            }

            if ($operator->takesNoValue()) {
                continue;
            }

            $allowed = array_column(AccessRuleFormOptions::valuesFor($attribute, $teamId, $userId), 'value');
            $values = $operator->takesList() ? $value : [$value];
            $isValid = is_array($values)
                && $values !== []
                && collect($values)->every(fn (mixed $item): bool => is_string($item) && in_array($item, $allowed, true));

            if (! $isValid) {
                $validator->errors()->add("conditions.{$index}.value", $operator->takesList() ? __('Choose one or more values.') : __('Choose a value.'));
            }
        }
    }

    /**
     * The conditions as stored: a value only where the operator needs one, a
     * list only where it takes one. Takes validated input.
     *
     * @return list<array{attribute: string, operator: string, value: string|list<string>|null}>
     */
    public static function normalizeConditions(mixed $conditions): array
    {
        return array_values(array_map(function (mixed $condition): array {
            $condition = Types::stringKeyed($condition);
            $operator = AccessOperatorEnum::from(Types::string($condition['operator'] ?? null));
            $value = $condition['value'] ?? null;

            return [
                'attribute' => Types::string($condition['attribute'] ?? null),
                'operator' => $operator->value,
                'value' => match (true) {
                    $operator->takesNoValue() => null,
                    $operator->takesList() => Types::stringList((array) $value),
                    default => Types::nullableString($value),
                },
            ];
        }, is_array($conditions) ? $conditions : []));
    }
}
