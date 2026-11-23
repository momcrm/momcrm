<?php

use App\Activity\ActivityEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use App\Mailbox\Actions\SyncMailboxes;
use App\Service\Actions\RunServiceInvoices;
use App\Shift\Actions\RemindOpenShifts;
use App\Ticket\Actions\RemindDueTickets;
use App\User\Actions\SetPlatformAdmin;
use Carbon\CarbonImmutable;
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

// Tells members still clocked in well after their day should have ended.
// It never clocks anyone out: a guessed time would end up in payroll.
Artisan::command('attendance:remind', function (): int {
    $this->components->info(trans_choice('{0} Nobody to remind.|{1} 1 reminder sent.|[2,*] :count reminders sent.', RemindOpenShifts::run()));

    return Command::SUCCESS;
})->purpose('Remind members who are still clocked in after their day');

Schedule::command('attendance:remind')->hourly();

// Reads new mail from every connected mailbox, one queued job per mailbox.
Artisan::command('mailboxes:sync', function (): int {
    $this->components->info(__(':count mailboxes queued.', ['count' => SyncMailboxes::run()]));

    return Command::SUCCESS;
})->purpose('Read new mail from every connected mailbox');

Schedule::command('mailboxes:sync')->everyFiveMinutes()->withoutOverlapping(10);

// Drafts the month's invoices from customers' services, as Amphora's monthly
// invoice run does. Safe to run again: periods already invoiced are skipped.
Artisan::command('services:invoice {--date= : Bill as if it were this day, YYYY-MM-DD} {--team= : Only this team}', function (): int {
    $date = $this->option('date');
    $team = $this->option('team');
    $count = RunServiceInvoices::on(
        is_string($date) && $date !== '' ? CarbonImmutable::parse($date) : CarbonImmutable::today(),
        is_string($team) && $team !== '' ? $team : null,
    );

    $this->components->info(trans_choice('{0} No invoices to draft.|{1} 1 invoice drafted.|[2,*] :count invoices drafted.', $count));

    return Command::SUCCESS;
})->purpose("Draft invoices for customers' services that are due");

Schedule::command('services:invoice')->monthlyOn(2, '00:00');

// Platform admins look after the installation itself, such as its `.env`.
// There is no page for this, so no team owner can make themselves one.
Artisan::command('users:platform-admin {email} {--revoke : Stop them being a platform admin}', function (string $email): int {
    $isPlatformAdmin = ! $this->option('revoke');

    if (SetPlatformAdmin::forEmail($email, $isPlatformAdmin)->isNull()) {
        $this->components->error(__('Nobody has the email address :email.', ['email' => $email]));

        return Command::FAILURE;
    }

    $this->components->info($isPlatformAdmin
        ? __(':email is now a platform admin.', ['email' => $email])
        : __(':email is no longer a platform admin.', ['email' => $email]));

    return Command::SUCCESS;
})->purpose('Make someone a platform admin, who can manage the environment');
