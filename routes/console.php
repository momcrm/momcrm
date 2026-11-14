<?php

use App\Activity\ActivityEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use App\Ticket\Actions\RemindDueTickets;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Composer's post-update-cmd runs this after `composer update`. Boost's files are
// gitignored, so machines that never ran `boost:install` have no boost.json and
// `boost:update` would fail the whole Composer command; skip it there instead.
Artisan::command('app:boost-update', function (): int {
    if (! file_exists(base_path('boost.json'))) {
        $this->components->info('Laravel Boost is not set up here; skipping boost:update. Run `php artisan boost:install` to set it up.');

        return Command::SUCCESS;
    }

    return $this->call('boost:update');
})->purpose('Update Laravel Boost guidelines and skills when Boost is set up');

// Deletes audit log and activity rows past their retention (config/audit.php).
// Models are listed because model:prune only discovers app/Models by default.
Schedule::command('model:prune', [
    '--model' => [AuditLogEloquentModel::class, ActivityEloquentModel::class],
])->daily();

// Tells assignees about their open tickets due tomorrow.
Artisan::command('tickets:remind-due', function (): int {
    $this->components->info(__(':count reminders sent.', ['count' => RemindDueTickets::run()]));

    return Command::SUCCESS;
})->purpose('Notify assignees of open tickets due tomorrow');

Schedule::command('tickets:remind-due')->dailyAt('07:00');
