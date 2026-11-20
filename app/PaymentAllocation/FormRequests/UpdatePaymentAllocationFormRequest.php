<?php

declare(strict_types=1);

namespace App\PaymentAllocation\FormRequests;

use App\Invoice\Actions\FindInvoice;
use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\PaymentAllocation\Actions\ChangePaymentAllocation;
use App\PaymentAllocation\Exceptions\CouldNotFindPaymentAllocation;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\Properties\AmountInCents;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Changes how much of a payment goes to one invoice: no more than is owing
 * on the invoice or left of the payment.
 */
final class UpdatePaymentAllocationFormRequest extends FormRequest
{
    public PaymentAllocation $allocation;

    public Payment $payment;

    public function __construct()
    {
        parent::__construct();

        $this->allocation = PaymentAllocation::new();
        $this->payment = Payment::new();
    }

    /**
     * @throws CouldNotFindPaymentAllocation
     */
    public function authorize(): Response
    {
        $teamId = $this->user()?->current_team_id;
        $this->allocation = FindPayment::allocationByIdForTeam((string) $this->route('allocation'), $teamId);

        if ($this->allocation->isNull()) {
            throw CouldNotFindPaymentAllocation::notFound();
        }

        $this->payment = FindPayment::byIdForTeam($this->allocation->getPaymentId()->toString(), $teamId);

        return Gate::authorize('update', $this->payment);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            AmountInCents::getNameForValidation() => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            AmountInCents::getNameForValidation().'.required' => __('Enter how much goes to this invoice.'),
            AmountInCents::getNameForValidation().'.integer' => __('Enter how much goes to this invoice.'),
            AmountInCents::getNameForValidation().'.min' => __('Enter how much goes to this invoice, or take it off instead.'),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $amount = Types::int($this->input(AmountInCents::getNameForValidation()));
            $current = $this->allocation->getAmountInCents()->toInteger();
            $invoice = FindInvoice::byIdsForTeam([$this->allocation->getInvoiceId()->toString()], $this->payment->getTeamId()->toString())[$this->allocation->getInvoiceId()->toString()] ?? null;
            $money = fn (int $cents): string => (string) Number::currency($cents / 100, $this->payment->getCurrency()->toEnum()->value);

            $owing = $invoice === null ? 0 : $invoice->balanceAfter($invoice->totals()) + $current;
            $left = $this->payment->getUnallocatedInCents() + $current;

            if ($amount > $owing) {
                $validator->errors()->add(AmountInCents::getNameForValidation(), __('Only :owing is owing on :reference.', ['owing' => $money($owing), 'reference' => $invoice?->getReference()]));
            } elseif ($amount > $left) {
                $validator->errors()->add(AmountInCents::getNameForValidation(), __('Only :left of :payment is left to apply.', ['left' => $money($left), 'payment' => $this->payment->getReferenceNumber()]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        ChangePaymentAllocation::to($this->allocation, Types::int($this->validated(AmountInCents::getNameForValidation())));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference saved.', ['reference' => $this->payment->getReferenceNumber()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if ($amount !== null) {
            $this->merge([AmountInCents::getNameForValidation() => is_numeric($amount) ? (int) round(((float) $amount) * 100) : $amount]);
        }
    }
}
