<?php

declare(strict_types=1);

namespace App\Environment\FormRequests;

use App\Environment\EnvironmentFile;
use App\Environment\EnvironmentVariables;
use App\Environment\Exceptions\CouldNotChangeEnvironmentVariable;
use App\Environment\Exceptions\CouldNotFindEnvironmentVariable;
use App\Environment\Exceptions\CouldNotWriteEnvironmentFile;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Sets a variable's value. Only its line is rewritten, and the old file is
 * backed up first. Nothing reads the new value until the environment is applied.
 */
final class UpdateEnvironmentVariableFormRequest extends FormRequest
{
    public string $variableKey = '';

    /**
     * Only a platform admin gets past, and never for a locked variable.
     *
     * @throws CouldNotChangeEnvironmentVariable
     * @throws CouldNotFindEnvironmentVariable
     */
    public function authorize(): Response
    {
        $response = Gate::authorize('manage', EnvironmentVariables::class);

        $this->variableKey = (string) $this->route('variable');
        EnvironmentVariables::ensureEditable($this->environmentFile(), $this->variableKey);

        return $response;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'value' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function process(): RedirectResponse
    {
        try {
            $this->environmentFile()->set($this->variableKey, $this->string('value')->toString());
        } catch (CouldNotWriteEnvironmentFile $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('environment.edit');
        }

        EnvironmentVariables::markChanged();
        Log::notice('Environment variable changed', ['key' => $this->variableKey, 'user' => $this->user()?->getAuthIdentifier()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':key saved. Apply the environment for it to take effect.', ['key' => $this->variableKey])]);

        return to_route('environment.edit');
    }

    private function environmentFile(): EnvironmentFile
    {
        return app(EnvironmentFile::class);
    }
}
