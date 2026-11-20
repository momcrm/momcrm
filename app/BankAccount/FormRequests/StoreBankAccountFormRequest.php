<?php

declare(strict_types=1);

namespace App\BankAccount\FormRequests;

use App\BankAccount\Actions\CreateBankAccount;
use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountAccess;
use App\BankAccount\BankAccountKindEnum;
use App\BankAccount\Properties\Currency;
use App\BankAccount\Properties\Kind;
use App\BankAccount\Properties\Name;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Adds a bank account whose statements the team will import.
 */
final class StoreBankAccountFormRequest extends FormRequest
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
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            Kind::getNameForValidation() => Kind::getValidationRulesForCreate($this),
            Currency::getNameForValidation() => Currency::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $account = CreateBankAccount::fromBankAccount(
            BankAccount::new()
                ->setPropertyIfExists(Types::stringKeyed($this->validated()))
                ->setKind($this->string(Kind::getNameForValidation())->toString() ?: BankAccountKindEnum::Statement->value)
                ->setTeamId((string) $this->user()?->current_team_id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $account->getName()->toString()])]);

        return back();
    }
}
