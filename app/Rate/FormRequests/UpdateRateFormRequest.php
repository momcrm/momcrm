<?php

declare(strict_types=1);

namespace App\Rate\FormRequests;

use App\Rate\Actions\FindRate;
use App\Rate\Actions\UpdateRate;
use App\Rate\Exceptions\CouldNotFindRate;
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
 * Changes a rate's name, prices or billing period.
 */
final class UpdateRateFormRequest extends FormRequest
{
    public Rate $rate;

    public function __construct()
    {
        parent::__construct();

        $this->rate = Rate::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindRate
     */
    public function authorize(): Response
    {
        $this->rate = FindRate::byIdForTeam((string) $this->route('rate'), RateAccess::team($this)->getId()->toString());

        if ($this->rate->isNull()) {
            throw CouldNotFindRate::notFound();
        }

        return RateAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return RateValidationRules::for($this, $this->rate->getTeamId()->toString(), $this->rate->getId()->toString());
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
        $rate = UpdateRate::fromRate($this->rate->setPropertyIfExists(Types::stringKeyed($this->validated())));

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name saved.', ['name' => $rate->getName()->toString()])]);

        return back();
    }

    protected function prepareForValidation(): void
    {
        RateValidationRules::prepare($this);
    }
}
