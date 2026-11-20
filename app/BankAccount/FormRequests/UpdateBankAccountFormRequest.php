<?php

declare(strict_types=1);

namespace App\BankAccount\FormRequests;

use App\BankAccount\Actions\FindBankAccount;
use App\BankAccount\Actions\UpdateBankAccount;
use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountAccess;
use App\BankAccount\Exceptions\CouldNotFindBankAccount;
use App\BankAccount\Properties\Currency;
use App\BankAccount\Properties\Name;
use App\BankTransaction\Actions\FindBankTransaction;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Renames a bank account, or changes its currency while nothing has been
 * imported into it.
 */
final class UpdateBankAccountFormRequest extends FormRequest
{
    public BankAccount $account;

    public function __construct()
    {
        parent::__construct();

        $this->account = BankAccount::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindBankAccount
     */
    public function authorize(): Response
    {
        $this->account = FindBankAccount::byIdForTeam((string) $this->route('bankAccount'), BankAccountAccess::team($this)->getId()->toString());

        if ($this->account->isNull()) {
            throw CouldNotFindBankAccount::notFound();
        }

        return BankAccountAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            Currency::getNameForValidation() => Currency::getValidationRulesForCreate($this),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $currency = $this->string(Currency::getNameForValidation())->toString();

            if ($currency !== $this->account->getCurrency()->toEnum()->value
                && FindBankTransaction::countForAccount($this->account->getId()->toString()) > 0) {
                $validator->errors()->add(Currency::getNameForValidation(), __('The currency can’t change once statements are imported into the account.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $account = UpdateBankAccount::fromBankAccount(
            $this->account->setPropertyIfExists(Types::stringKeyed($this->validated())),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $account->getName()->toString()])]);

        return back();
    }
}
