<?php

declare(strict_types=1);

namespace App\BankRule\FormRequests;

use App\BankAccount\BankAccountAccess;
use App\BankRule\Actions\CreateBankRule;
use App\BankRule\BankRule;
use App\BankRule\BankRuleValidationRules;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Adds a rule for bank feed deposits.
 */
final class StoreBankRuleFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return BankAccountAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return BankRuleValidationRules::for($this);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return BankRuleValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        CreateBankRule::fromBankRule(
            BankRule::new()
                ->setPropertyIfExists(Types::stringKeyed($this->validated()))
                ->setTeamId((string) $this->user()?->current_team_id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule added. It applies to deposits imported from now on, and to pending ones when you match again.')]);

        return back();
    }
}
