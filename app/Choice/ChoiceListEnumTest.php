<?php

declare(strict_types=1);

use App\Choice\ChoiceListEnum;

it('starts every list with at least one choice and exactly one default per category', function (ChoiceListEnum $list): void {
    $defaults = collect($list->defaults());
    $categories = $list->categories() === [] ? [null] : $list->categories();

    foreach ($categories as $category) {
        $inCategory = $defaults->where('category', $category);

        expect($inCategory)->not->toBeEmpty("{$list->value}: no {$category} choice")
            ->and($inCategory->where('is_default', true))->toHaveCount(1, "{$list->value}: needs one {$category} default");
    }

    expect($defaults->pluck('category')->unique()->diff($categories))->toBeEmpty("{$list->value}: unknown category")
        ->and($defaults->pluck('name')->duplicates())->toBeEmpty("{$list->value}: duplicate names");
})->with(ChoiceListEnum::cases());
