<?php

declare(strict_types=1);

use App\CustomField\CustomField;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldQuery;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Team\TeamEloquentModel;
use App\Ticket\TicketEloquentModel;

/**
 * Tickets titled after the value they hold for the field (null for none),
 * and the titles of those a filter or sort leaves, in order.
 *
 * @param  array<string, mixed>  $valuesByTitle
 */
function ticketsHolding(CustomFieldEloquentModel $field, array $valuesByTitle): void
{
    foreach ($valuesByTitle as $title => $value) {
        TicketEloquentModel::factory()->create([
            'team_id' => $field->team_id,
            'title' => $title,
            'custom_fields' => $value === null ? null : [$field->id => $value],
        ]);
    }
}

/**
 * @return list<string>
 */
function titlesFilteredBy(CustomFieldEloquentModel $field, string $value): array
{
    $query = TicketEloquentModel::query()->withoutGlobalScopes()->where('team_id', $field->team_id);
    CustomFieldQuery::filter($query, CustomField::fromEloquentModel($field), $value);

    return $query->orderBy('title')->pluck('title')->all();
}

/**
 * @return list<string>
 */
function titlesSortedBy(CustomFieldEloquentModel $field, string $direction): array
{
    $query = TicketEloquentModel::query()->withoutGlobalScopes()->where('team_id', $field->team_id);
    CustomFieldQuery::sort($query, CustomField::fromEloquentModel($field), $direction);

    return $query->pluck('title')->all();
}

it('filters by a select option, or by having none', function (): void {
    $field = CustomFieldEloquentModel::factory()->select(['Gold', 'Silver'])->create(['team_id' => TeamEloquentModel::factory()]);
    [$gold, $silver] = array_column($field->options, 'id');
    ticketsHolding($field, ['gold' => $gold, 'silver' => $silver, 'none' => null]);

    expect(titlesFilteredBy($field, $gold))->toBe(['gold'])
        ->and(titlesFilteredBy($field, CustomFieldQuery::NONE))->toBe(['none']);
});

it('filters by an option a multi-select contains', function (): void {
    $field = CustomFieldEloquentModel::factory()->select(['Red', 'Blue'], isMultiple: true)->create(['team_id' => TeamEloquentModel::factory()]);
    [$red, $blue] = array_column($field->options, 'id');
    ticketsHolding($field, ['both' => [$red, $blue], 'blue' => [$blue], 'none' => null]);

    expect(titlesFilteredBy($field, $red))->toBe(['both'])
        ->and(titlesFilteredBy($field, $blue))->toBe(['blue', 'both']);
});

it('filters by a ticked or unticked checkbox', function (): void {
    $field = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Checkbox)->create(['team_id' => TeamEloquentModel::factory()]);
    ticketsHolding($field, ['ticked' => true, 'unticked' => null]);

    expect(titlesFilteredBy($field, '1'))->toBe(['ticked'])
        ->and(titlesFilteredBy($field, '0'))->toBe(['unticked']);
});

it('filters by a linked record', function (): void {
    $field = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create(['team_id' => TeamEloquentModel::factory()]);
    ticketsHolding($field, ['acme' => '019a0000-0000-7000-8000-00000000000a', 'other' => '019a0000-0000-7000-8000-00000000000b']);

    expect(titlesFilteredBy($field, '019a0000-0000-7000-8000-00000000000a'))->toBe(['acme']);
});

it('sorts numbers by size, not as text', function (): void {
    $field = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Number)->create(['team_id' => TeamEloquentModel::factory()]);
    ticketsHolding($field, ['ten' => 10, 'nine' => 9, 'half' => 0.5, 'minus' => -2]);

    expect(titlesSortedBy($field, 'asc'))->toBe(['minus', 'half', 'nine', 'ten'])
        ->and(titlesSortedBy($field, 'desc'))->toBe(['ten', 'nine', 'half', 'minus']);
});

it('sorts text and dates in order', function (CustomFieldTypeEnum $type, array $values): void {
    $field = CustomFieldEloquentModel::factory()->ofType($type)->create(['team_id' => TeamEloquentModel::factory()]);
    ticketsHolding($field, $values);

    expect(titlesSortedBy($field, 'desc'))->toBe(['last', 'middle', 'first']);
})->with([
    'text' => [CustomFieldTypeEnum::Text, ['middle' => 'beta', 'last' => 'gamma', 'first' => 'alpha']],
    'dates' => [CustomFieldTypeEnum::Date, ['middle' => '2026-06-01', 'last' => '2026-12-01', 'first' => '2025-12-31']],
]);

it('sorts a select by the order of its options, with no option first', function (): void {
    $field = CustomFieldEloquentModel::factory()->select(['Zebra', 'Apple'])->create(['team_id' => TeamEloquentModel::factory()]);
    [$zebra, $apple] = array_column($field->options, 'id');
    ticketsHolding($field, ['apple' => $apple, 'none' => null, 'zebra' => $zebra]);

    expect(titlesSortedBy($field, 'asc'))->toBe(['none', 'zebra', 'apple'])
        ->and(titlesSortedBy($field, 'desc'))->toBe(['apple', 'zebra', 'none']);
});

it('sorts ticked checkboxes after unticked ones', function (): void {
    $field = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Checkbox)->create(['team_id' => TeamEloquentModel::factory()]);
    ticketsHolding($field, ['ticked' => true, 'unticked' => null]);

    expect(titlesSortedBy($field, 'asc'))->toBe(['unticked', 'ticked']);
});
