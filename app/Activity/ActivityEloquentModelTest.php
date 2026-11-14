<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;

use function Pest\Laravel\artisan;

it('prunes rows older than the configured retention', function (): void {
    config(['audit.activity_retention_days' => 30]);

    $old = ActivityEloquentModel::query()->create([...['type' => 'signed_in'], 'created_at' => now()->subDays(31)]);
    $recent = ActivityEloquentModel::query()->create([...['type' => 'signed_in'], 'created_at' => now()->subDays(29)]);

    artisan('model:prune', ['--model' => [ActivityEloquentModel::class]])->assertSuccessful();

    expect(ActivityEloquentModel::query()->find($old->id))->toBeNull()
        ->and(ActivityEloquentModel::query()->find($recent->id))->not->toBeNull();
});
