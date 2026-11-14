<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Audit log and activity rows older than these many days are deleted by
    | the daily `model:prune` schedule.
    |
    */

    'audit_log_retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),

    'activity_retention_days' => (int) env('ACTIVITY_RETENTION_DAYS', 180),

];
