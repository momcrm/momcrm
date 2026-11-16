<?php

declare(strict_types=1);

namespace App\Contract\FormRequests;

use App\Contract\Actions\FindContract;
use App\Contract\Actions\UpdateContract;
use App\Contract\Contract;
use App\Contract\ContractValidationRules;
use App\Contract\Exceptions\CouldNotFindContract;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateContractFormRequest extends FormRequest
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

        return Gate::authorize('update', $this->contract);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ContractValidationRules::for($this);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ContractValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $contract = UpdateContract::fromContract($this->contract->setPropertyIfExists($this->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title saved.', ['title' => $contract->getTitle()->toString()])]);

        return back();
    }
}
