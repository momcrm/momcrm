<?php

declare(strict_types=1);

namespace App\Rate\FormRequests;

use App\Rate\Actions\CreateRate;
use App\Rate\Rate;
use App\Rate\RateAccess;
use App\Rate\RateValidationRules;
use App\Shared\Types;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Adds a rate to the team's price list.
 */
final class StoreRateFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        return RateAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return RateValidationRules::for($this, (string) $this->user()?->current_team_id);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return RateValidationRules::messages();
    }

    public function process(): RedirectResponse
    {
        $rate = CreateRate::fromRate(
            Rate::new()
                ->setPropertyIfExists(Types::stringKeyed($this->validated()))
                ->setTeamId((string) $this->user()?->current_team_id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name added.', ['name' => $rate->getName()->toString()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        RateValidationRules::prepare($this);
    }
}
