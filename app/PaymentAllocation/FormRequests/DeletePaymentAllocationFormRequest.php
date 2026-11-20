<?php

declare(strict_types=1);

namespace App\PaymentAllocation\FormRequests;

use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\PaymentAllocation\Actions\ChangePaymentAllocation;
use App\PaymentAllocation\Exceptions\CouldNotFindPaymentAllocation;
use App\PaymentAllocation\PaymentAllocation;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Takes a payment off one invoice. The money becomes the customer's credit
 * and the invoice is owed again.
 */
final class DeletePaymentAllocationFormRequest extends FormRequest
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
        return [];
    }

    public function process(): RedirectResponse
    {
        ChangePaymentAllocation::remove($this->allocation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':payment taken off :invoice. It is now credit.', [
            'payment' => $this->payment->getReferenceNumber(),
            'invoice' => $this->allocation->getInvoice()->toNullableData()?->getReference(),
        ])]);

        return back();
    }
}
