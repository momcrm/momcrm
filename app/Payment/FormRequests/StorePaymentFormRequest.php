<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\BankTransaction\Actions\ApplyBankTransaction;
use App\BankTransaction\Actions\FindBankTransaction;
use App\BankTransaction\BankTransaction;
use App\BankTransaction\Exceptions\CouldNotApplyBankTransaction;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Payment\Actions\RecordPayment;
use App\Payment\Payment;
use App\Payment\Properties\AmountInCents;
use App\Payment\Properties\CustomerId;
use App\Payment\Properties\Method;
use App\Payment\Properties\Notes;
use App\Payment\Properties\PaidOn;
use App\Payment\Properties\Reference;
use App\PaymentAllocation\PaymentAllocationRules;
use App\Shared\Table;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Records a payment. Recorded from a bank feed line (`bank_transaction_id`),
 * it must be for that line's amount, from a customer billed in its
 * account's currency, and the line then shows as applied.
 */
final class StorePaymentFormRequest extends FormRequest
{
    public const string BANK_TRANSACTION_ID = 'bank_transaction_id';

    public BankTransaction $bankTransaction;

    public function __construct()
    {
        parent::__construct();

        $this->bankTransaction = BankTransaction::new();
    }

    /**
     * @throws CouldNotFindBankTransaction
     */
    public function authorize(): Response
    {
        $response = Gate::authorize('create', Payment::class);
        $bankTransactionId = $this->input(self::BANK_TRANSACTION_ID);

        if (is_string($bankTransactionId) && $bankTransactionId !== '') {
            $this->bankTransaction = FindBankTransaction::byIdForTeam($bankTransactionId, $this->user()?->current_team_id);

            if ($this->bankTransaction->isNull()) {
                throw CouldNotFindBankTransaction::notFound();
            }

            Gate::authorize('update', $this->bankTransaction);
        }

        return $response;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            CustomerId::getNameForValidation() => [
                'required',
                'uuid',
                Rule::exists(Table::Customers->value, 'id')->where('team_id', $this->user()?->current_team_id),
            ],
            AmountInCents::getNameForValidation() => AmountInCents::getValidationRulesForCreate($this),
            PaidOn::getNameForValidation() => PaidOn::getValidationRulesForCreate($this),
            Method::getNameForValidation() => Method::getValidationRulesForCreate($this),
            Reference::getNameForValidation() => Reference::getValidationRulesForCreate($this),
            Notes::getNameForValidation() => Notes::getValidationRulesForCreate($this),
            self::BANK_TRANSACTION_ID => ['sometimes', 'nullable', 'uuid'],
            ...PaymentAllocationRules::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            CustomerId::getNameForValidation().'.required' => __('Choose the customer who paid.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose the customer who paid.'),
            AmountInCents::getNameForValidation().'.required' => __('Enter the amount received.'),
            AmountInCents::getNameForValidation().'.integer' => __('Enter the amount received.'),
            AmountInCents::getNameForValidation().'.min' => __('Enter the amount received.'),
            PaidOn::getNameForValidation().'.required' => __('Enter the date it was paid.'),
            ...PaymentAllocationRules::messages(),
        ];
    }

    /**
     * The invoices paid must be this customer's and owed what is applied to
     * them. Whatever isn't applied is the customer's credit.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $customer = $this->customer();
            $amount = Types::intOrZero($this->input(AmountInCents::getNameForValidation()));

            PaymentAllocationRules::check($validator, $customer, $amount, AmountInCents::getNameForValidation());

            if ($this->bankTransaction->isNotNull()) {
                $this->checkBankTransaction($validator, $customer, $amount);
            }
        }];
    }

    /**
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $customer = $this->customer();
        $payment = Payment::new()
            ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, ['allocations', self::BANK_TRANSACTION_ID])))
            ->setCurrency($customer->getCurrency()->toEnum()->value)
            ->setTeamId($this->user()?->current_team_id);
        $allocations = PaymentAllocationRules::allocations($validated);

        try {
            $payment = $this->bankTransaction->isNotNull()
                ? ApplyBankTransaction::asPayment($this->bankTransaction, $payment, $allocations)
                : RecordPayment::fromPayment($payment, $allocations);
        } catch (CouldNotApplyBankTransaction $exception) {
            throw ValidationException::withMessages([self::BANK_TRANSACTION_ID => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $payment->getUnallocatedInCents() > 0
            ? __(':reference recorded. :amount is kept as credit.', ['reference' => $payment->getReferenceNumber(), 'amount' => Number::currency($payment->getUnallocatedInCents() / 100, $payment->getCurrency()->toEnum()->value)])
            : __(':reference recorded.', ['reference' => $payment->getReferenceNumber()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if ($amount !== null) {
            $this->merge([AmountInCents::getNameForValidation() => is_numeric($amount) ? (int) round(((float) $amount) * 100) : $amount]);
        }

        PaymentAllocationRules::normalise($this);
    }

    private function checkBankTransaction(Validator $validator, Customer $customer, int $amount): void
    {
        $currency = $this->bankTransaction->getBankAccount()->toData()->getCurrency()->toEnum();

        if (! $this->bankTransaction->isPending()) {
            $validator->errors()->add(self::BANK_TRANSACTION_ID, __('Only a pending line can be recorded as a payment.'));
        } elseif ($amount !== $this->bankTransaction->getAmountInCents()->toInteger()) {
            $validator->errors()->add(AmountInCents::getNameForValidation(), __('A payment from the bank feed is for the :amount that came in.', [
                'amount' => Number::currency($this->bankTransaction->getAmountInCents()->toInteger() / 100, $currency->value),
            ]));
        } elseif ($customer->getCurrency()->toEnum() !== $currency) {
            $validator->errors()->add(CustomerId::getNameForValidation(), __(':name is billed in :theirs, but this money is in :account.', [
                'name' => $customer->getName()->toString(),
                'theirs' => $customer->getCurrency()->toEnum()->value,
                'account' => $currency->value,
            ]));
        }
    }

    private function customer(): Customer
    {
        return FindCustomer::byIdForTeam(Types::nullableString($this->input(CustomerId::getNameForValidation())), $this->user()?->current_team_id);
    }
}
