<?php

declare(strict_types=1);

namespace App\Charge\FormRequests;

use App\Charge\Actions\CreateCharge;
use App\Charge\Charge;
use App\Charge\ChargeValidationRules;
use App\Charge\Properties\CustomerId;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Charges a customer once for something, in their currency, to go on their
 * next invoice.
 */
final class StoreChargeFormRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::authorize('create', Charge::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = (string) $this->user()?->current_team_id;

        return [
            CustomerId::getNameForValidation() => ['required', 'uuid', Rule::exists(Table::Customers->value, 'id')->where('team_id', $teamId)],
            ...ChargeValidationRules::for($this, $teamId),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...ChargeValidationRules::messages(),
            CustomerId::getNameForValidation().'.required' => __('Choose the customer to charge.'),
            CustomerId::getNameForValidation().'.exists' => __('Choose the customer to charge.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $customer = $this->customer();

        $charge = CreateCharge::fromCharge(
            Charge::new()
                ->setPropertyIfExists(Types::stringKeyed($this->validated()))
                ->setTeamId((string) $this->user()?->current_team_id)
                ->setCurrency($customer->getCurrency()->toEnum()->value),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':description charged. It goes on their next invoice.', ['description' => $charge->getDescription()->toString()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        ChargeValidationRules::prepare($this, (string) $this->user()?->current_team_id, $this->customer()->getCurrency()->toEnum());
    }

    private function customer(): Customer
    {
        return FindCustomer::byIdForTeam(Types::nullableString($this->input(CustomerId::getNameForValidation())), $this->user()?->current_team_id);
    }
}
