<?php

declare(strict_types=1);

namespace App\Charge\FormRequests;

use App\Charge\Actions\DeleteCharge;
use App\Charge\Actions\FindCharge;
use App\Charge\Charge;
use App\Charge\Exceptions\CouldNotFindCharge;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Deletes a charge that hasn't been billed.
 */
final class DeleteChargeFormRequest extends FormRequest
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

        return Gate::authorize('delete', $this->charge);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $billedOn = FindCharge::billedInvoices([$this->charge->getId()->toString()])[$this->charge->getId()->toString()] ?? null;

            if ($billedOn !== null) {
                $validator->errors()->add('charge', __('It is on :reference. Void that invoice first.', ['reference' => $billedOn['reference']]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        DeleteCharge::fromCharge($this->charge);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':description deleted.', ['description' => $this->charge->getDescription()->toString()])]);

        return back();
    }
}
