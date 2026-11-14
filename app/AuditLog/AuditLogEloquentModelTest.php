<?php

declare(strict_types=1);

use App\AuditLog\AuditLogEloquentModel;

use function Pest\Laravel\artisan;

it('prunes rows older than the configured retention', function (): void {
    config(['audit.audit_log_retention_days' => 30]);

    $old = AuditLogEloquentModel::query()->create([...['auditable_type' => 'team', 'auditable_id' => fake()->uuid(), 'event' => 'created'], 'created_at' => now()->subDays(31)]);
    $recent = AuditLogEloquentModel::query()->create([...['auditable_type' => 'team', 'auditable_id' => fake()->uuid(), 'event' => 'created'], 'created_at' => now()->subDays(29)]);

    artisan('model:prune', ['--model' => [AuditLogEloquentModel::class]])->assertSuccessful();

    expect(AuditLogEloquentModel::query()->find($old->id))->toBeNull()
        ->and(AuditLogEloquentModel::query()->find($recent->id))->not->toBeNull();
});
