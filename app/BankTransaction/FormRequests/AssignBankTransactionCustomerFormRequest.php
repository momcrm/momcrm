<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankTransaction\Actions\AssignBankTransactionCustomer;
use App\BankTransaction\Actions\FindBankTransaction;
use App\BankTransaction\BankTransaction;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use App\Customer\Actions\FindCustomer;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Picks the customer a pending bank feed line is from, by hand, or clears it.
 */
final class AssignBankTransactionCustomerFormRequest extends FormRequest
{
    public BankTransaction $transaction;

    public function __construct()
    {
        parent::__construct();

        $this->transaction = BankTransaction::new();
    }

    /**
     * @throws CouldNotFindBankTransaction
     */
    public function authorize(): Response
    {
        $this->transaction = FindBankTransaction::byIdForTeam((string) $this->route('bankTransaction'), $this->user()?->current_team_id);

        if ($this->transaction->isNull()) {
            throw CouldNotFindBankTransaction::notFound();
        }

        return Gate::authorize('update', $this->transaction);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['present', 'nullable', 'uuid'],
        ];
    }

    /**
     * The customer must be the team's and billed in the account's currency,
     * since the money can only pay their invoices in it.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->transaction->isPending()) {
                $validator->errors()->add('customer_id', __('Only a pending line’s customer can change.'));

                return;
            }

            $customerId = Types::nullableString($this->input('customer_id'));

            if ($customerId === null) {
                return;
            }

            $customer = FindCustomer::byIdForTeam($customerId, $this->user()?->current_team_id);
            $currency = $this->transaction->getBankAccount()->toData()->getCurrency()->toEnum();

            if ($customer->isNull()) {
                $validator->errors()->add('customer_id', __('Choose one of your customers.'));
            } elseif ($customer->getCurrency()->toEnum() !== $currency) {
                $validator->errors()->add('customer_id', __(':name is billed in :theirs, but this money is in :account.', [
                    'name' => $customer->getName()->toString(),
                    'theirs' => $customer->getCurrency()->toEnum()->value,
                    'account' => $currency->value,
                ]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        AssignBankTransactionCustomer::to($this->transaction, Types::nullableString($this->input('customer_id')));

        return back();
    }
}
