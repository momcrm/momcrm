<?php

declare(strict_types=1);

use App\Label\Label;
use App\Label\LabelCollection;
use App\Label\LabelEloquentModel;
use Illuminate\Support\Collection;

it('collects data objects into the domain collection', function (): void {
    $labels = Label::collect(LabelEloquentModel::factory()->count(2)->create()->all());

    expect($labels)->toBeInstanceOf(LabelCollection::class)->toHaveCount(2)
        ->and(Label::collect($labels))->toBe($labels);
});

it('finds items, and stands in an empty data object for a missing one', function (): void {
    $model = LabelEloquentModel::factory()->create(['name' => 'Urgent']);
    $labels = Label::collect([$model]);

    expect($labels->findById($model->id)->getName()->toString())->toBe('Urgent')
        ->and($labels->findById($labels->first()->getId())->getName()->toString())->toBe('Urgent')
        ->and($labels->findById('missing')->isNull())->toBeTrue()
        ->and($labels->first(fn (Label $label): bool => false)->isNull())->toBeTrue()
        ->and(LabelCollection::make()->first()->isNull())->toBeTrue()
        ->and($labels->ids())->toBe([$model->id]);
});

it('returns a plain collection once the items are turned into something else', function (): void {
    $labels = Label::collect(LabelEloquentModel::factory()->count(2)->create()->all());

    expect($labels->map(fn (Label $label): string => $label->getName()->toString()))->toBeInstanceOf(Collection::class)->not->toBeInstanceOf(LabelCollection::class)
        ->and($labels->mapWithKeys(fn (Label $label): array => [$label->getId()->toString() => 1]))->not->toBeInstanceOf(LabelCollection::class)
        ->and($labels->keys())->not->toBeInstanceOf(LabelCollection::class)
        ->and($labels->filter())->toBeInstanceOf(LabelCollection::class);
});
