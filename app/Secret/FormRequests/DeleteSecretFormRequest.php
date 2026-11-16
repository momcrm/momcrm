<?php

declare(strict_types=1);

namespace App\Secret\FormRequests;

use App\Secret\Actions\DeleteSecret;
use App\Secret\Actions\FindSecret;
use App\Secret\Exceptions\CouldNotFindSecret;
use App\Secret\Secret;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteSecretFormRequest extends FormRequest
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

        return Gate::authorize('delete', $this->secret);
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
        DeleteSecret::fromSecret($this->secret);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title deleted.', ['title' => $this->secret->getTitle()->toString()])]);

        return to_route('customers.secrets', $this->secret->getCustomerId()->toString());
    }
}
