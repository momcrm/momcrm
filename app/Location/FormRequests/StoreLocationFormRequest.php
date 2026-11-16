<?php

declare(strict_types=1);

namespace App\Location\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Location\Actions\CreateLocation;
use App\Location\Location;
use App\Location\Properties\Country;
use App\Location\Properties\IsPrimary;
use App\Location\Properties\Name;
use App\Location\Properties\Postcode;
use App\Location\Properties\State;
use App\Location\Properties\StreetOne;
use App\Location\Properties\StreetTwo;
use App\Location\Properties\Suburb;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class StoreLocationFormRequest extends FormRequest
{
    public Customer $customer;

    public function __construct()
    {
        parent::__construct();

        $this->customer = Customer::new();
    }

    /**
     * @throws CouldNotFindCustomer
     */
    public function authorize(): Response
    {
        $this->customer = FindCustomer::byIdForTeam((string) $this->route('customer'), $this->user()?->current_team_id);

        if ($this->customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return Gate::authorize('create', Location::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            StreetOne::getNameForValidation() => StreetOne::getValidationRulesForCreate($this),
            StreetTwo::getNameForValidation() => StreetTwo::getValidationRulesForCreate($this),
            Suburb::getNameForValidation() => Suburb::getValidationRulesForCreate($this),
            State::getNameForValidation() => State::getValidationRulesForCreate($this),
            Postcode::getNameForValidation() => Postcode::getValidationRulesForCreate($this),
            Country::getNameForValidation() => Country::getValidationRulesForCreate($this),
            IsPrimary::getNameForValidation() => IsPrimary::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $location = CreateLocation::fromLocation(
            Location::new()
                ->setPropertyIfExists($this->validated())
                ->setIsPrimary($this->boolean(IsPrimary::getNameForValidation()))
                ->setCustomerId($this->customer->getId())
                ->setTeamId($this->customer->getTeamId()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $location->getName()->toString()])]);

        return to_route('customers.locations', $this->customer->getId()->toString());
    }
}
