<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Rate\Actions\FindRate;
use App\Service\Actions\ChangeServiceRate;
use App\Service\Actions\UpdateService;
use App\Service\BulkServiceSelection;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Properties\AutoRenew;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\Quantity;
use App\Service\Properties\RateId;
use App\Service\Service;
use App\Service\ServiceCollection;
use App\Service\ServiceValidationRules;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Sets the rate, quantity, billing period or auto-renew of several services
 * at once. Each service is authorized as if it were changed on its own, and
 * if any one is refused, none change. A new rate is recorded on each service
 * as its own rate change.
 */
final class BulkUpdateServicesFormRequest extends FormRequest
{
    /**
     * The fields a bulk change can set.
     *
     * @var array<int, string>
     */
    public const array FIELDS = ['rate_id', 'quantity', 'billing_period_in_months', 'auto_renew'];

    public ServiceCollection $services;

    public function __construct()
    {
        parent::__construct();

        $this->services = new ServiceCollection([]);
    }

    /**
     * @throws CouldNotFindService
     */
    public function authorize(): Response
    {
        $this->services = BulkServiceSelection::load($this);

        foreach ($this->services as $service) {
            Gate::authorize('update', $service);
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...BulkServiceSelection::rules(),
            RateId::getNameForValidation() => ['sometimes', ...ServiceValidationRules::rate($this, (string) $this->user()?->current_team_id)],
            Quantity::getNameForValidation() => ['sometimes', ...Quantity::getValidationRulesForCreate($this)],
            BillingPeriodInMonths::getNameForValidation() => ['sometimes', ...BillingPeriodInMonths::getValidationRulesForCreate($this)],
            AutoRenew::getNameForValidation() => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ServiceValidationRules::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(self::FIELDS)) {
                $validator->errors()->add(BulkServiceSelection::SERVICE_IDS, __('Choose what to change on the selected services.'));
            }

            if ($this->boolean(AutoRenew::getNameForValidation()) && $this->services->contains(fn (Service $service): bool => $service->isArchived() || ! $service->isActive())) {
                $validator->errors()->add(AutoRenew::getNameForValidation(), __('Only active services can renew by themselves. Turn the others on first.'));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        $validated = $this->validated();
        $changes = Types::stringKeyed(Arr::only($validated, [Quantity::getNameForValidation(), BillingPeriodInMonths::getNameForValidation(), AutoRenew::getNameForValidation()]));
        $rateId = $validated[RateId::getNameForValidation()] ?? null;
        $rate = is_string($rateId) ? FindRate::byIdForTeam($rateId, (string) $this->user()?->current_team_id) : null;
        $userId = (string) $this->user()?->id;

        DB::transaction(function () use ($changes, $rate, $userId): void {
            $this->services->each(function (Service $service) use ($changes, $rate, $userId): void {
                if ($changes !== []) {
                    UpdateService::fromService($service->setPropertyIfExists($changes));
                }

                if ($rate !== null) {
                    ChangeServiceRate::to($service, $rate, $userId);
                }
            });
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice('{1} 1 service updated.|[2,*] :count services updated.', $this->services->count())]);

        return back();
    }
}
