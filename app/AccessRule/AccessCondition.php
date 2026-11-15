<?php

declare(strict_types=1);

namespace App\AccessRule;

/**
 * One condition of an access rule, read from its stored array. An attribute or
 * operator the code no longer knows leaves the condition broken: AccessEvaluator
 * then treats it so the rule can only take access away, never grant it.
 */
final readonly class AccessCondition
{
    /**
     * @param  string|array<int, string>|null  $value
     */
    public function __construct(
        public ?AccessAttributeEnum $attribute,
        public ?AccessOperatorEnum $operator,
        public string|array|null $value,
    ) {}

    /**
     * @param  array<mixed>  $condition
     */
    public static function fromArray(array $condition): self
    {
        $attribute = $condition['attribute'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        return new self(
            attribute: is_string($attribute) ? AccessAttributeEnum::tryFrom($attribute) : null,
            operator: is_string($operator) ? AccessOperatorEnum::tryFrom($operator) : null,
            value: match (true) {
                is_array($value) => array_values(array_map(strval(...), array_filter($value, is_scalar(...)))),
                is_scalar($value) => (string) $value,
                default => null,
            },
        );
    }

    public function isBroken(): bool
    {
        return $this->attribute === null
            || $this->operator === null
            || ! in_array($this->operator, $this->attribute->type()->operators(), true);
    }

    /**
     * @return array{attribute: ?string, operator: ?string, value: string|array<int, string>|null}
     */
    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute?->value,
            'operator' => $this->operator?->value,
            'value' => $this->value,
        ];
    }
}
