<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('prunes the audit log and activity models daily', function (): void {
    $prune = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'model:prune'));

    expect($prune)->not->toBeNull()
        ->and($prune->expression)->toBe('0 0 * * *')
        ->and($prune->command)->toContain(AuditLogEloquentModel::class)
        ->and($prune->command)->toContain(ActivityEloquentModel::class);
});

it('runs the scheduled prune without errors', function (): void {
    artisan('model:prune', [
        '--model' => [AuditLogEloquentModel::class, ActivityEloquentModel::class],
        '--pretend' => true,
    ])->assertSuccessful();
});

it('reminds assignees of tickets due tomorrow every morning', function (): void {
    $remind = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'tickets:remind-due'));

    expect($remind)->not->toBeNull()
        ->and($remind->expression)->toBe('0 7 * * *');
});

it('runs the due reminder without errors', function (): void {
    artisan('tickets:remind-due')->assertSuccessful();
});

it('reads connected mailboxes every five minutes, one run at a time', function (): void {
    $sync = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'mailboxes:sync'));

    expect($sync)->not->toBeNull()
        ->and($sync->expression)->toBe('*/5 * * * *')
        ->and($sync->withoutOverlapping)->toBeTrue();
});

it('queues the mailbox reads without errors', function (): void {
    artisan('mailboxes:sync')->expectsOutputToContain('0 mailboxes queued.')->assertSuccessful();
});

it('reminds members still clocked in every hour', function (): void {
    $remind = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'attendance:remind'));

    expect($remind)->not->toBeNull()
        ->and($remind->expression)->toBe('0 * * * *');
});

it('runs the clock-out reminder without errors', function (): void {
    artisan('attendance:remind')->expectsOutputToContain('Nobody to remind.')->assertSuccessful();
});
