<?php

declare(strict_types=1);

use App\BankRule\BankRule;
use App\BankRule\BankRuleCollection;

function bank_rules(array ...$rules): BankRuleCollection
{
    return new BankRuleCollection(array_map(fn (array $rule): BankRule => BankRule::new()
        ->setKind($rule[0])
        ->setContains($rule[1])
        ->setCustomerId($rule[2] ?? null), $rules));
}

it('applies a rule whose text is in the description, whatever its case and spacing', function (): void {
    $rules = bank_rules(['hide', 'transfer  to savings']);

    expect($rules->hides('Fast TRANSFER TO   Savings 0042'))->toBeTrue()
        ->and($rules->hides('Transfer from savings'))->toBeFalse();
});

it('names the customer only when every rule that applies agrees', function (string $description, ?string $customer): void {
    $rules = bank_rules(['match', 'HARBOUR', 'a'], ['match', 'DENTAL', 'a'], ['match', 'COASTAL', 'b'], ['match', 'HARBOUR', 'gone']);

    expect($rules->customerFor($description, ['a' => true, 'b' => true]))->toBe($customer);
})->with([
    'one rule' => ['COASTAL LOGISTICS', 'b'],
    'two rules for one customer' => ['HARBOUR DENTAL', 'a'],
    'rules for different customers' => ['HARBOUR COASTAL', null],
    'no rule' => ['SOMEONE ELSE', null],
]);
