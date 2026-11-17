<?php

declare(strict_types=1);

namespace App\Location\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Location\Actions\FindLocation;
use App\Location\Actions\UpdateLocation;
use App\Location\Exceptions\CouldNotFindLocation;
use App\Location\Location;
use App\Location\Properties\Country;
use App\Location\Properties\IsPrimary;
use App\Location\Properties\Name;
use App\Location\Properties\Postcode;
use App\Location\Properties\State;
use App\Location\Properties\StreetOne;
use App\Location\Properties\StreetTwo;
use App\Location\Properties\Suburb;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateLocationFormRequest extends FormRequest
{
    public Location $location;

    public Customer $customer;

    public function __construct()
    {
        parent::__construct();

        $this->location = Location::new();
        $this->customer = Customer::new();
    }

    /**
     * @throws CouldNotFindLocation
     */
    public function authorize(): Response
    {
        $teamId = $this->user()?->current_team_id;
        $this->location = FindLocation::byIdForTeam((string) $this->route('location'), $teamId);

        if ($this->location->isNull()) {
            throw CouldNotFindLocation::notFound();
        }

        $this->customer = FindCustomer::byIdForTeam($this->location->getCustomerId()->toString(), $teamId);

        return Gate::authorize('update', $this->location);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForUpdate($this),
            StreetOne::getNameForValidation() => StreetOne::getValidationRulesForUpdate($this),
            StreetTwo::getNameForValidation() => StreetTwo::getValidationRulesForUpdate($this),
            Suburb::getNameForValidation() => Suburb::getValidationRulesForUpdate($this),
            State::getNameForValidation() => State::getValidationRulesForUpdate($this),
            Postcode::getNameForValidation() => Postcode::getValidationRulesForUpdate($this),
            Country::getNameForValidation() => Country::getValidationRulesForUpdate($this),
            IsPrimary::getNameForValidation() => IsPrimary::getValidationRulesForUpdate($this),
            ...CustomFieldValueRules::for($this->location->getTeamId()->toString(), CustomFieldTargetEnum::Location, $this->location->getCustomFields()),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return CustomFieldValueRules::attributes($this->location->getTeamId()->toString(), CustomFieldTargetEnum::Location);
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $location = UpdateLocation::fromLocation(
            $this->location
                ->setCustomFields(CustomFieldValueRules::merge($this->location->getTeamId()->toString(), CustomFieldTargetEnum::Location, $this->location->getCustomFields(), $validated[CustomFieldValues::getNameForValidation()] ?? []))
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, CustomFieldValues::getNameForValidation())))
                ->setIsPrimary($this->boolean(IsPrimary::getNameForValidation())),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $location->getName()->toString()])]);

        return to_route('customers.locations', $this->customer->getId()->toString());
    }
}
