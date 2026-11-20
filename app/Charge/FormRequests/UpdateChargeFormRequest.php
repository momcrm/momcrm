<?php

declare(strict_types=1);

namespace App\Charge\FormRequests;

use App\Charge\Actions\FindCharge;
use App\Charge\Actions\UpdateCharge;
use App\Charge\Charge;
use App\Charge\ChargeValidationRules;
use App\Charge\Exceptions\CouldNotFindCharge;
use App\Shared\Types;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Changes a charge that hasn't been billed yet.
 */
final class UpdateChargeFormRequest extends FormRequest
{
    public Charge $charge;

    public function __construct()
    {
        parent::__construct();

        $this->charge = Charge::new();
    }

    /**
     * @throws CouldNotFindCharge
     */
    public function authorize(): Response
    {
        $this->charge = FindCharge::byIdForTeam((string) $this->route('charge'), $this->user()?->current_team_id);

        if ($this->charge->isNull()) {
            throw CouldNotFindCharge::notFound();
        }

        return Gate::authorize('update', $this->charge);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ChargeValidationRules::for($this, $this->charge->getTeamId()->toString());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ChargeValidationRules::messages();
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $billedOn = FindCharge::billedInvoices([$this->charge->getId()->toString()])[$this->charge->getId()->toString()] ?? null;

            if ($billedOn !== null) {
                $validator->errors()->add('charge', __('It is on :reference. Change it there, or void that invoice first.', ['reference' => $billedOn['reference']]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        UpdateCharge::fromCharge($this->charge->setPropertyIfExists(Types::stringKeyed($this->validated())));

        return back();
    }

    protected function prepareForValidation(): void
    {
        $charge = FindCharge::byIdForTeam((string) $this->route('charge'), $this->user()?->current_team_id);

        ChargeValidationRules::prepare($this, $charge->getTeamId()->toString(), $charge->getCurrency()->toEnum());
    }
}
