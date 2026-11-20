<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\Payment\Actions\FindPayment;
use App\Payment\Actions\UpdatePayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\Payment;
use App\Payment\Properties\Method;
use App\Payment\Properties\Notes;
use App\Payment\Properties\PaidOn;
use App\Payment\Properties\Reference;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Changes when and how a payment was made, its reference and notes. What
 * it paid is changed through its allocations.
 */
final class UpdatePaymentFormRequest extends FormRequest
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
            PaidOn::getNameForValidation() => PaidOn::getValidationRulesForCreate($this),
            Method::getNameForValidation() => Method::getValidationRulesForCreate($this),
            Reference::getNameForValidation() => Reference::getValidationRulesForCreate($this),
            Notes::getNameForValidation() => Notes::getValidationRulesForCreate($this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            PaidOn::getNameForValidation().'.required' => __('Enter the date it was paid.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $payment = UpdatePayment::fromPayment($this->payment->setPropertyIfExists(Types::stringKeyed($this->validated())));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference saved.', ['reference' => $payment->getReferenceNumber()])]);

        return back();
    }
}
