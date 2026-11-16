<?php

declare(strict_types=1);

namespace App\Secret\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Secret\Actions\CreateSecret;
use App\Secret\Secret;
use App\Secret\SecretValidationRules;
use App\Secret\SecretValues;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class StoreSecretFormRequest extends FormRequest
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

        return Gate::authorize('create', Secret::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return SecretValidationRules::for($this, $this->customer->getId()->toNullableString());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return SecretValidationRules::messages();
    }

    protected function prepareForValidation(): void
    {
        SecretValues::prepare($this);
    }

    public function process(): RedirectResponse
    {
        $secret = CreateSecret::fromSecret(
            Secret::new()
                ->setPropertyIfExists($this->validated())
                ->setTeamId($this->customer->getTeamId())
                ->setCustomerId($this->customer->getId()),
            SecretValues::from($this->validated()) ?? [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':title saved.', ['title' => $secret->getTitle()->toString()])]);

        return back();
    }
}
