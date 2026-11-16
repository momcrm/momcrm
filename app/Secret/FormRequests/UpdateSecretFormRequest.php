<?php

declare(strict_types=1);

namespace App\Secret\FormRequests;

use App\Secret\Actions\FindSecret;
use App\Secret\Actions\UpdateSecret;
use App\Secret\Exceptions\CouldNotFindSecret;
use App\Secret\Secret;
use App\Secret\SecretValidationRules;
use App\Secret\SecretValues;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Saves a secret. The values are optional: the form sends them only once
 * they have been revealed and edited, so someone who may edit but not reveal
 * can still rename a secret.
 */
final class UpdateSecretFormRequest extends FormRequest
{
    public Secret $secret;

    public function __construct()
    {
        parent::__construct();

        $this->secret = Secret::new();
    }

    /**
     * @throws CouldNotFindSecret
     */
    public function authorize(): Response
    {
        $this->secret = FindSecret::byIdForTeam((string) $this->route('secret'), $this->user()?->current_team_id);

        if ($this->secret->isNull()) {
            throw CouldNotFindSecret::notFound();
        }

        return Gate::authorize('update', $this->secret);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return SecretValidationRules::for($this, $this->secret->getCustomerId()->toNullableString(), valuesRequired: false);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return SecretValidationRules::messages();
    }

    protected function prepareForValidation(): void
    {
        SecretValues::prepare($this);
    }

    public function process(): RedirectResponse
    {
        $secret = UpdateSecret::fromSecret(
            $this->secret->setPropertyIfExists($this->validated()),
            SecretValues::from($this->validated()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title saved.', ['title' => $secret->getTitle()->toString()])]);

        return back();
    }
}
