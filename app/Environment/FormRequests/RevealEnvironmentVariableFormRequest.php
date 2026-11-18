<?php

declare(strict_types=1);

namespace App\Environment\FormRequests;

use App\Environment\EnvironmentFile;
use App\Environment\EnvironmentVariables;
use App\Environment\Exceptions\CouldNotChangeEnvironmentVariable;
use App\Environment\Exceptions\CouldNotFindEnvironmentVariable;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Hands one variable's value, secrets included, to the page that asked, as
 * JSON that is never cached. Each secret revealed is logged.
 */
final class RevealEnvironmentVariableFormRequest extends FormRequest
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
        return [];
    }

    public function process(): JsonResponse
    {
        if (EnvironmentVariables::isSecret($this->variableKey)) {
            Log::notice('Environment variable revealed', ['key' => $this->variableKey, 'user' => $this->user()?->getAuthIdentifier()]);
        }

        return response()
            ->json(['value' => $this->environmentFile()->get($this->variableKey) ?? ''])
            ->header('Cache-Control', 'no-store, private');
    }

    private function environmentFile(): EnvironmentFile
    {
        return app(EnvironmentFile::class);
    }
}
