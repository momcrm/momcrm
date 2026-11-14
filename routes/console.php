<?php

use App\Activity\ActivityEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deletes audit log and activity rows past their retention (config/audit.php).
// Models are listed because model:prune only discovers app/Models by default.
Schedule::command('model:prune', [
    '--model' => [AuditLogEloquentModel::class, ActivityEloquentModel::class],
])->daily();
