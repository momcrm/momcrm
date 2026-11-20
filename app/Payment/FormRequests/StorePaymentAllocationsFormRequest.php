<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Payment\Actions\AllocatePayment;
use App\Payment\Actions\FindPayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\Payment;
use App\PaymentAllocation\PaymentAllocationRules;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Applies what is left of a payment to its customer's invoices.
 */
final class StorePaymentAllocationsFormRequest extends FormRequest
{
    public Payment $payment;

    public function __construct()
    {
        parent::__construct();

        $this->payment = Payment::new();
    }

    /**
     * @throws CouldNotFindPayment
     */
    public function authorize(): Response
    {
        $this->payment = FindPayment::byIdForTeam((string) $this->route('payment'), $this->user()?->current_team_id);

        if ($this->payment->isNull()) {
            throw CouldNotFindPayment::notFound();
        }

        return Gate::authorize('update', $this->payment);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...PaymentAllocationRules::rules(),
            'allocations' => ['required', 'array', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'allocations.required' => __('Choose the invoices to pay.'),
            ...PaymentAllocationRules::messages(),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            PaymentAllocationRules::check(
                $validator,
                FindCustomer::byIdForTeam($this->payment->getCustomerId()->toString(), $this->payment->getTeamId()->toString()),
                $this->payment->getUnallocatedInCents(),
                'allocations',
            );
        }];
    }

    public function process(): RedirectResponse
    {
        AllocatePayment::toInvoices($this->payment, PaymentAllocationRules::allocations($this->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference applied.', ['reference' => $this->payment->getReferenceNumber()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        PaymentAllocationRules::normalise($this);
    }
}
