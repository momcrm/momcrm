<?php

declare(strict_types=1);

namespace App\Team\FormRequests;

use App\Team\Actions\FindTeam;
use App\Team\Actions\UpdateTeam;
use App\Team\Exceptions\CouldNotUpdateTeam;
use App\Team\Properties\BillingAddress;
use App\Team\Properties\BillingEmail;
use App\Team\Properties\BillingName;
use App\Team\Properties\BillingPhone;
use App\Team\Properties\BpayBillerCode;
use App\Team\Properties\DaysToPay;
use App\Team\Properties\InvoicePrefix;
use App\Team\Properties\PaymentDetails;
use App\Team\Properties\TaxId;
use App\Team\Properties\TaxIdLabel;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class UpdateBillingDetailsFormRequest extends FormRequest
{
    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    /**
     * @throws CouldNotUpdateTeam
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($this->team->isNull()) {
            throw CouldNotUpdateTeam::notFound();
        }

        return Gate::authorize('manageBilling', $this->team);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            BillingName::getNameForValidation() => BillingName::getValidationRulesForUpdate($this),
            TaxIdLabel::getNameForValidation() => TaxIdLabel::getValidationRulesForUpdate($this),
            TaxId::getNameForValidation() => TaxId::getValidationRulesForUpdate($this),
            BillingEmail::getNameForValidation() => BillingEmail::getValidationRulesForUpdate($this),
            BillingPhone::getNameForValidation() => BillingPhone::getValidationRulesForUpdate($this),
            BillingAddress::getNameForValidation() => BillingAddress::getValidationRulesForUpdate($this),
            PaymentDetails::getNameForValidation() => PaymentDetails::getValidationRulesForUpdate($this),
            DaysToPay::getNameForValidation() => DaysToPay::getValidationRulesForUpdate($this),
            BpayBillerCode::getNameForValidation() => BpayBillerCode::getValidationRulesForUpdate($this),
            InvoicePrefix::getNameForValidation() => InvoicePrefix::getValidationRulesForUpdate($this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            TaxIdLabel::getNameForValidation().'.required_with' => __('Say what your tax ID is called, e.g. ABN or VAT.'),
            BpayBillerCode::getNameForValidation().'.regex' => __('A BPAY biller code is 3 to 10 digits.'),
            InvoicePrefix::getNameForValidation().'.regex' => __('Use letters, numbers and - / _ . # only, with no spaces.'),
        ];
    }

    public function process(): RedirectResponse
    {
        UpdateTeam::fromTeam($this->team->setPropertyIfExists($this->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Billing details saved.')]);

        return to_route('billing.edit');
    }
}
