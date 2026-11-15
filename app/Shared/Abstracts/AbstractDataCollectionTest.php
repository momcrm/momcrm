<?php

declare(strict_types=1);

use App\Choice\Choice;
use App\Choice\ChoiceCollection;
use App\Choice\ChoiceEloquentModel;
use Illuminate\Support\Collection;

it('collects data objects into the domain collection', function (): void {
    $labels = Choice::collect(ChoiceEloquentModel::factory()->label()->count(2)->create()->all());

    expect($labels)->toBeInstanceOf(ChoiceCollection::class)->toHaveCount(2)
        ->and(Choice::collect($labels))->toBe($labels);
});

it('finds items, and stands in an empty data object for a missing one', function (): void {
    $model = ChoiceEloquentModel::factory()->label()->create(['name' => 'Urgent']);
    $labels = Choice::collect([$model]);

    expect($labels->findById($model->id)->getName()->toString())->toBe('Urgent')
        ->and($labels->findById($labels->first()->getId())->getName()->toString())->toBe('Urgent')
        ->and($labels->findById('missing')->isNull())->toBeTrue()
        ->and($labels->first(fn (Choice $label): bool => false)->isNull())->toBeTrue()
        ->and(ChoiceCollection::make()->first()->isNull())->toBeTrue()
        ->and($labels->ids())->toBe([$model->id]);
});

it('returns a plain collection once the items are turned into something else', function (): void {
    $labels = Choice::collect(ChoiceEloquentModel::factory()->label()->count(2)->create()->all());

    expect($labels->map(fn (Choice $label): string => $label->getName()->toString()))->toBeInstanceOf(Collection::class)->not->toBeInstanceOf(ChoiceCollection::class)
        ->and($labels->mapWithKeys(fn (Choice $label): array => [$label->getId()->toString() => 1]))->not->toBeInstanceOf(ChoiceCollection::class)
        ->and($labels->keys())->not->toBeInstanceOf(ChoiceCollection::class)
        ->and($labels->filter())->toBeInstanceOf(ChoiceCollection::class);
});
