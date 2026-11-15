<?php

declare(strict_types=1);

namespace App\Asset\FormRequests;

use App\Asset\Actions\CreateAsset;
use App\Asset\Asset;
use App\Asset\Properties\Description;
use App\Asset\Properties\LocationId;
use App\Asset\Properties\Name;
use App\Asset\Properties\SerialNumber;
use App\Asset\Properties\Status;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Shared\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StoreAssetFormRequest extends FormRequest
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

        return Gate::authorize('create', Asset::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForCreate($this),
            SerialNumber::getNameForValidation() => SerialNumber::getValidationRulesForCreate($this),
            LocationId::getNameForValidation() => [
                ...LocationId::getValidationRulesForCreate($this),
                Rule::exists(Table::Locations->value, 'id')->where('customer_id', $this->customer->getId()->toString()),
            ],
            Status::getNameForValidation() => Status::getValidationRulesForCreate($this),
            Description::getNameForValidation() => Description::getValidationRulesForCreate($this),
        ];
    }

    public function process(): RedirectResponse
    {
        $asset = CreateAsset::fromAsset(
            Asset::new()
                ->setPropertyIfExists($this->validated())
                ->setCustomerId($this->customer->getId())
                ->setTeamId($this->customer->getTeamId()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $asset->getName()->toString()])]);

        return to_route('customers.show', $this->customer->getId()->toString());
    }
}
