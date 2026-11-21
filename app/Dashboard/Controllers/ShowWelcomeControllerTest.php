<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

it('shows the front page', function (): void {
    get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});
