<?php

declare(strict_types=1);

namespace App\Contract\FormRequests;

use App\Contract\Actions\DeleteContract;
use App\Contract\Actions\FindContract;
use App\Contract\Contract;
use App\Contract\Exceptions\CouldNotFindContract;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Deletes a contract with its documents.
 */
final class DeleteContractFormRequest extends FormRequest
{
    public Contract $contract;

    public function __construct()
    {
        parent::__construct();

        $this->contract = Contract::new();
    }

    /**
     * @throws CouldNotFindContract
     */
    public function authorize(): Response
    {
        $this->contract = FindContract::byIdForTeam((string) $this->route('contract'), $this->user()?->current_team_id);

        if ($this->contract->isNull()) {
            throw CouldNotFindContract::notFound();
        }

        return Gate::authorize('delete', $this->contract);
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
        DeleteContract::fromContract($this->contract);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title deleted.', ['title' => $this->contract->getTitle()->toString()])]);

        return to_route('customers.contracts', $this->contract->getCustomerId()->toString());
    }
}
