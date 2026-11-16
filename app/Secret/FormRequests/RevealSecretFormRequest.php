<?php

declare(strict_types=1);

namespace App\Secret\FormRequests;

use App\Secret\Actions\FindSecret;
use App\Secret\Actions\RevealSecret;
use App\Secret\Exceptions\CouldNotFindSecret;
use App\Secret\Secret;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Hands one secret's values to the page that asked, as JSON that is never
 * cached, and logs that they were viewed.
 */
final class RevealSecretFormRequest extends FormRequest
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

        return Gate::authorize('reveal', $this->secret);
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
        return response()
            ->json(['values' => RevealSecret::valuesOf($this->secret)])
            ->header('Cache-Control', 'no-store, private');
    }
}
