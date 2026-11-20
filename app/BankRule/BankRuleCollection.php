<?php

declare(strict_types=1);

namespace App\BankRule;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, BankRule>
 */
final class BankRuleCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return BankRule::class;
    }

    /**
     * The hide rules whose text is in the description.
     */
    public function hides(string $description): bool
    {
        foreach ($this as $rule) {
            if ($rule->getKind()->toEnum() === BankRuleKindEnum::Hide && $rule->appliesTo($description)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The customer named by every match rule whose text is in the
     * description, or null when none apply or they disagree.
     *
     * @param  array<string, mixed>  $eligible  ids of the customers that may be named, as keys
     */
    public function customerFor(string $description, array $eligible): ?string
    {
        $customers = [];

        foreach ($this as $rule) {
            $customerId = $rule->getCustomerId()->toNullableString();

            if ($rule->getKind()->toEnum() === BankRuleKindEnum::Match && $customerId !== null && isset($eligible[$customerId]) && $rule->appliesTo($description)) {
                $customers[$customerId] = true;
            }
        }

        return count($customers) === 1 ? (string) array_key_first($customers) : null;
    }

    /**
     * @return list<array{id: string, kind: string, contains: string, customer_id: ?string, customer_name: ?string}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (BankRule $rule): array => $rule->toSummary())->all());
    }
}
