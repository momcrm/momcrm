<?php

declare(strict_types=1);

namespace App\Environment\FormRequests;

use App\Environment\EnvironmentVariables;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Makes the running app read the `.env` again. A cached config (deploys cache
 * it) is rebuilt from the file; without one, web requests already read it.
 * Queue workers then restart after their current job. Long-running servers
 * keep their old config until they are restarted.
 */
final class ApplyEnvironmentFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('manage', EnvironmentVariables::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        $configCached = app()->configurationIsCached();

        if ($configCached) {
            Artisan::call('config:cache');
        }

        Artisan::call('queue:restart');
        EnvironmentVariables::markApplied();

        Log::notice('Environment applied', ['config_cached' => $configCached, 'user' => $this->user()?->getAuthIdentifier()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $configCached
            ? __('Config rebuilt from the environment file. Queue workers restart after their current job.')
            : __('Web requests already read the environment file. Queue workers restart after their current job.'),
        ]);

        return to_route('environment.edit');
    }
}
