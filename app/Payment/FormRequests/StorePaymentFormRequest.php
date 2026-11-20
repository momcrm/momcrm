<?php

declare(strict_types=1);

namespace App\Payment\FormRequests;

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
use Illuminate\Validation\Validator;
use Inertia\Inertia;

final class StorePaymentFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', Payment::class);
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
     * them, and the whole amount must go to invoices.
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

            $allocated = array_sum(PaymentAllocationRules::allocations($this->all()));

            if ($validator->errors()->isEmpty() && $allocated < $amount) {
                $validator->errors()->add(AmountInCents::getNameForValidation(), __('Apply all of it to invoices: :left is left over.', [
                    'left' => Number::currency(($amount - $allocated) / 100, $customer->getCurrency()->toEnum()->value),
                ]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $customer = $this->customer();

        $payment = RecordPayment::fromPayment(
            Payment::new()
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, 'allocations')))
                ->setCurrency($customer->getCurrency()->toEnum()->value)
                ->setTeamId($this->user()?->current_team_id),
            PaymentAllocationRules::allocations($validated),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':reference recorded.', ['reference' => $payment->getReferenceNumber()])]);

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

    private function customer(): Customer
    {
        return FindCustomer::byIdForTeam(Types::nullableString($this->input(CustomerId::getNameForValidation())), $this->user()?->current_team_id);
    }
}
