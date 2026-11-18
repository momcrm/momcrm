<?php

declare(strict_types=1);

namespace App\Environment\FormRequests;

use App\Environment\EnvironmentFile;
use App\Environment\EnvironmentVariables;
use App\Environment\Exceptions\CouldNotWriteEnvironmentFile;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Appends a variable to the `.env`, after backing the file up. Nothing reads
 * it until the environment is applied.
 */
final class StoreEnvironmentVariableFormRequest extends FormRequest
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
        return [
            'key' => [
                'required',
                'string',
                'max:128',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (EnvironmentVariables::isLocked($value)) {
                        $fail(__(':key cannot be set here.', ['key' => $value]));
                    } elseif ($this->environmentFile()->has($value)) {
                        $fail(__(':key is already set. Edit it instead.', ['key' => $value]));
                    }
                },
            ],
            'value' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.regex' => __('Use upper case letters, digits and underscores, starting with a letter.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $key = $this->string('key')->toString();

        try {
            $this->environmentFile()->set($key, $this->string('value')->toString());
        } catch (CouldNotWriteEnvironmentFile $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('environment.edit');
        }

        EnvironmentVariables::markChanged();
        Log::notice('Environment variable added', ['key' => $key, 'user' => $this->user()?->getAuthIdentifier()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':key added. Apply the environment for it to take effect.', ['key' => $key])]);

        return to_route('environment.edit');
    }

    private function environmentFile(): EnvironmentFile
    {
        return app(EnvironmentFile::class);
    }
}
