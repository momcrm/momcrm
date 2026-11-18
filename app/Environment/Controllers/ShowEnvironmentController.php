<?php

declare(strict_types=1);

namespace App\Environment\Controllers;

use App\Environment\EnvironmentFile;
use App\Environment\EnvironmentVariables;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The installation's `.env`, one row per variable in file order, for platform
 * admins. Secrets are listed without their values.
 */
final class ShowEnvironmentController
{
    public function __invoke(EnvironmentFile $file): Response
    {
        Gate::authorize('manage', EnvironmentVariables::class);

        return Inertia::render('settings/environment', [
            'variables' => $file->variables()
                ->map(fn (array $variable): array => EnvironmentVariables::present($variable['key'], $variable['value']))
                ->values()
                ->all(),
            'meta' => [
                'config_cached' => app()->configurationIsCached(),
                'modified_at' => $file->modifiedAt()?->toIso8601String(),
                'changed_at' => EnvironmentVariables::changedAt()?->toIso8601String(),
                'applied_at' => EnvironmentVariables::appliedAt()?->toIso8601String(),
                'has_pending_changes' => EnvironmentVariables::hasPendingChanges(),
            ],
        ]);
    }
}
