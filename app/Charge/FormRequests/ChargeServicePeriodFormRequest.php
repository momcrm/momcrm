<?php

declare(strict_types=1);

namespace App\Charge\FormRequests;

use App\Charge\Actions\CreateCharge;
use App\Charge\Charge;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Billing\BillingLine;
use App\Invoice\Billing\BillingMonth;
use App\Invoice\Billing\CustomerBillingLines;
use App\Service\Actions\FindService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Single Service Billing, as in Amphora: bills one service's next period
 * starting by the month picked as a line that waits to be combined into an
 * invoice. The period counts as billed from now, so nothing bills it twice.
 */
final class ChargeServicePeriodFormRequest extends FormRequest
{
    public Customer $customer;

    public ?BillingLine $line = null;

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

        return Gate::authorize('create', Charge::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'uuid'],
            'month' => ['required', 'date_format:Y-m'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = FindService::byIdForTeam($this->string('service_id')->toString(), $this->customer->getTeamId()->toString());

            if ($service->isNull() || $service->getCustomerId()->toString() !== $this->customer->getId()->toString()) {
                $validator->errors()->add('service_id', __('Choose one of their services.'));

                return;
            }

            $this->line = CustomerBillingLines::nextForService($service, $this->customer->getCurrency()->toEnum(), BillingMonth::fromString($this->string('month')->toString())->lastDay());

            if ($this->line === null) {
                $validator->errors()->add('service_id', __('This service is already invoiced, or waiting to be, for every period starting by then.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $line = $this->line;

        if ($line !== null) {
            CreateCharge::fromCharge(
                Charge::new()
                    ->setTeamId($this->customer->getTeamId()->toString())
                    ->setCustomerId($this->customer->getId()->toString())
                    ->setServiceId($line->serviceId)
                    ->setDescription($line->description)
                    ->setQuantity($line->quantity)
                    ->setUnitPriceInCents($line->unitPriceInCents)
                    ->setCurrency($this->customer->getCurrency()->toEnum()->value)
                    ->setChargedOn($line->rentalStartsOn ?? CarbonImmutable::today()->toDateString())
                    ->setRentalStartsOn($line->rentalStartsOn)
                    ->setRentalEndsOn($line->rentalEndsOn),
            );

            Inertia::flash('toast', ['type' => 'success', 'message' => __(':description is waiting to be invoiced.', ['description' => $line->description])]);
        }

        return back();
    }
}
