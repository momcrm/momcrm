<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Customer\Actions\FindCustomer;
use App\Rate\Actions\FindRate;
use App\Service\Actions\ChangeServiceRate;
use App\Service\Actions\FindService;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Properties\RateId;
use App\Service\Service;
use App\Service\ServiceValidationRules;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Moves a service to another of the team's rates.
 */
final class ChangeServiceRateFormRequest extends FormRequest
{
    public Service $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = Service::new();
    }

    /**
     * @throws CouldNotFindService
     */
    public function authorize(): Response
    {
        $this->service = FindService::byIdForTeam((string) $this->route('service'), $this->user()?->current_team_id);

        if ($this->service->isNull()) {
            throw CouldNotFindService::notFound();
        }

        return Gate::authorize('update', $this->service);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $teamId = $this->service->getTeamId()->toString();
        $currency = FindCustomer::byIdForTeam($this->service->getCustomerId()->toString(), $teamId)->getCurrency()->toEnum();

        return [
            RateId::getNameForValidation() => ServiceValidationRules::rate($this, $teamId, [$currency]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ServiceValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $rate = FindRate::byIdForTeam($this->string(RateId::getNameForValidation())->toString(), $this->service->getTeamId()->toString());

        ChangeServiceRate::to($this->service, $rate, (string) $this->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is now on :rate.', [
            'name' => $this->service->getName()->toString(),
            'rate' => $rate->getName()->toString(),
        ])]);

        return back();
    }
}
