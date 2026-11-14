<?php

declare(strict_types=1);

namespace App\Customer\FormRequests;

use App\Customer\Actions\CreateCustomer;
use App\Customer\Customer;
use App\Customer\Properties\Abn;
use App\Customer\Properties\Email;
use App\Customer\Properties\Name;
use App\Customer\Properties\Notes;
use App\Customer\Properties\Phone;
use App\Customer\Properties\Status;
use App\Customer\Properties\Website;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StoreCustomerFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->current_team_id !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            Abn::getNameForValidation() => Abn::getValidationRulesForCreate($this),
            Email::getNameForValidation() => Email::getValidationRulesForCreate($this),
            Phone::getNameForValidation() => Phone::getValidationRulesForCreate($this),
            Website::getNameForValidation() => Website::getValidationRulesForCreate($this),
            Status::getNameForValidation() => Status::getValidationRulesForCreate($this),
            Notes::getNameForValidation() => Notes::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $customer = CreateCustomer::fromCustomer(
            Customer::new()
                ->setPropertyIfExists($this->validated())
                ->setTeamId($this->user()?->current_team_id)
                ->setIsSample(false),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $customer->getName()->toString()])]);

        return to_route('customers.show', $customer->getId()->toString());
    }
}
