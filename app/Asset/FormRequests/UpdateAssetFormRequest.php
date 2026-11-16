<?php

declare(strict_types=1);

namespace App\Asset\FormRequests;

use App\Asset\Actions\FindAsset;
use App\Asset\Actions\UpdateAsset;
use App\Asset\Asset;
use App\Asset\AssetEloquentModel;
use App\Asset\Exceptions\CouldNotFindAsset;
use App\Asset\Properties\Description;
use App\Asset\Properties\LocationId;
use App\Asset\Properties\Name;
use App\Asset\Properties\SerialNumber;
use App\Asset\Properties\Status;
use App\Choice\LabelTargetEnum;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Shared\Table;
use App\Shared\Types;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class UpdateAssetFormRequest extends FormRequest
{
    public Asset $asset;

    public Customer $customer;

    public function __construct()
    {
        parent::__construct();

        $this->asset = Asset::new();
        $this->customer = Customer::new();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(LabelTargetEnum::sentEmpty($this));
    }

    /**
     * @throws CouldNotFindAsset
     */
    public function authorize(): Response
    {
        $teamId = $this->user()?->current_team_id;
        $this->asset = FindAsset::byIdForTeam((string) $this->route('asset'), $teamId);

        if ($this->asset->isNull()) {
            throw CouldNotFindAsset::notFound();
        }

        $this->customer = FindCustomer::byIdForTeam($this->asset->getCustomerId()->toString(), $teamId);

        return Gate::authorize('update', $this->asset);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            Name::getNameForValidation() => Name::getValidationRulesForUpdate($this),
            SerialNumber::getNameForValidation() => SerialNumber::getValidationRulesForUpdate($this),
            LocationId::getNameForValidation() => [
                ...LocationId::getValidationRulesForUpdate($this),
                Rule::exists(Table::Locations->value, 'id')->where('customer_id', $this->customer->getId()->toString()),
            ],
            Status::getNameForValidation() => Status::getValidationRulesForUpdate($this),
            Description::getNameForValidation() => Description::getValidationRulesForUpdate($this),
            ...LabelTargetEnum::Asset->rules($this->asset->getTeamId()->toString(), $this->asset->getId()->toString()),
        ];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        $asset = UpdateAsset::fromAsset(
            $this->asset
                ->setPropertyIfExists(Types::stringKeyed(Arr::except($validated, LabelTargetEnum::FIELD))),
        );

        LabelTargetEnum::syncFrom($validated, AssetEloquentModel::query()->findOrFail($asset->getId()->toString())->labels());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $asset->getName()->toString()])]);

        return to_route('customers.assets', $this->customer->getId()->toString());
    }
}
