<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

use App\Payment\Actions\FindPayment;
use App\Payment\Actions\UpdatePayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\Payment;
use App\Payment\Properties\AmountInCents;
use App\Payment\Properties\Method;
use App\Payment\Properties\Notes;
use App\Payment\Properties\PaidOn;
use App\Payment\Properties\Reference;
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
 * Changes a payment's amount, when and how it was made, its reference and
 * notes. The amount can't drop below what has been applied to invoices;
 * any more is the customer's credit. What it paid is changed through its
 * allocations.
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
            AmountInCents::getNameForValidation() => ['sometimes', ...AmountInCents::getValidationRulesForCreate($this)],
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
            AmountInCents::getNameForValidation().'.required' => __('Enter the amount received.'),
            AmountInCents::getNameForValidation().'.integer' => __('Enter the amount received.'),
            AmountInCents::getNameForValidation().'.min' => __('Enter the amount received.'),
            PaidOn::getNameForValidation().'.required' => __('Enter the date it was paid.'),
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $amount = $this->input(AmountInCents::getNameForValidation());
            $allocated = $this->payment->getAllocatedInCents()->toInteger();

            if ($validator->errors()->isEmpty() && is_int($amount) && $amount < $allocated) {
                $validator->errors()->add(AmountInCents::getNameForValidation(), __(':allocated of it has been applied to invoices, so it can\'t be less. Take it off an invoice first.', [
                    'allocated' => Number::currency($allocated / 100, $this->payment->getCurrency()->toEnum()->value),
                ]));
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if ($amount !== null) {
            $this->merge([AmountInCents::getNameForValidation() => is_numeric($amount) ? (int) round(((float) $amount) * 100) : $amount]);
        }
    }

    public function process(): RedirectResponse
    {
        $payment = UpdatePayment::fromPayment($this->payment->setPropertyIfExists(Types::stringKeyed($this->validated())));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference saved.', ['reference' => $payment->getReferenceNumber()])]);

        return back();
    }
}
