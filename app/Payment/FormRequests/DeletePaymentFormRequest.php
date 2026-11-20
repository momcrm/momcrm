<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\Payment\Actions\DeletePayment;
use App\Payment\Actions\FindPayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\Payment;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Uri;
use Inertia\Inertia;

/**
 * Deletes a payment for good, with what it paid.
 */
final class DeletePaymentFormRequest extends FormRequest
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

        return Gate::authorize('delete', $this->payment);
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
        DeletePayment::fromPayment($this->payment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference deleted.', ['reference' => $this->payment->getReferenceNumber()])]);

        // Back to the page it was deleted from, without the payment open
        // over it (?payment=), which is gone.
        return redirect()->to((string) Uri::of(url()->previous())->withoutQuery(['payment']));
    }
}
