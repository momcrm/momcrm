<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankAccount\BankAccountKindEnum;
use App\BankTransaction\Actions\ApplyBankTransaction;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\BankTransactionSelection;
use App\BankTransaction\Exceptions\CouldNotApplyBankTransaction;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use App\Payment\Payment;
use App\Payment\PaymentMethodEnum;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Records a payment from each picked line that has a customer, in one go:
 * the money goes to their invoices still owing, oldest due first, and
 * whatever is left is their credit.
 */
final class ApplyBankTransactionsFormRequest extends FormRequest
{
    public BankTransactionCollection $transactions;

    public function __construct()
    {
        parent::__construct();

        $this->transactions = new BankTransactionCollection([]);
    }

    /**
     * @throws CouldNotFindBankTransaction
     */
    public function authorize(): Response
    {
        Gate::authorize('create', Payment::class);

        $this->transactions = BankTransactionSelection::authorize($this, 'update');

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return BankTransactionSelection::rules();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->transactions as $transaction) {
                $customer = $transaction->getCustomer()->toNullableData();

                if (! $transaction->isPending() || $customer === null) {
                    $validator->errors()->add(BankTransactionSelection::IDS, __('Pick pending lines that have a customer.'));

                    return;
                }

                if ($customer->getCurrency()->toEnum() !== $transaction->getBankAccount()->toData()->getCurrency()->toEnum()) {
                    $validator->errors()->add(BankTransactionSelection::IDS, __(':name is billed in another currency than the account the money came into.', ['name' => $customer->getName()->toString()]));

                    return;
                }
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $teamId = (string) $this->user()?->current_team_id;
        $recorded = 0;

        foreach ($this->transactions as $transaction) {
            $customer = $transaction->getCustomer()->toData();
            $amount = $transaction->getAmountInCents()->toInteger();

            try {
                ApplyBankTransaction::asPayment(
                    $transaction,
                    Payment::new()
                        ->setTeamId($teamId)
                        ->setCustomerId($customer->getId()->toString())
                        ->setAmountInCents($amount)
                        ->setCurrency($customer->getCurrency()->toEnum()->value)
                        ->setPaidOn($transaction->getPostedOn()->toNullableCarbon()?->toDateString())
                        ->setMethod($transaction->getBankAccount()->toData()->getKind()->toEnum() === BankAccountKindEnum::Bpay ? PaymentMethodEnum::Bpay->value : PaymentMethodEnum::BankTransfer->value)
                        ->setReference($transaction->getCrn()->toNullableString())
                        ->setNotes($transaction->getDescription()->toString()),
                    ApplyBankTransaction::oldestInvoicesFirst($customer->getId()->toString(), $teamId, $amount),
                );
                $recorded++;
            } catch (CouldNotApplyBankTransaction) {
                continue;
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count payment recorded.|:count payments recorded.', $recorded)]);

        return back();
    }
}
