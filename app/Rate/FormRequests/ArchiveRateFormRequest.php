<?php

declare(strict_types=1);

namespace App\Rate\FormRequests;

use App\Rate\Actions\ArchiveRate;
use App\Rate\Actions\FindRate;
use App\Rate\Exceptions\CouldNotFindRate;
use App\Rate\Rate;
use App\Rate\RateAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Archives a rate so it is no longer offered for new services. Services
 * already on it keep it.
 */
final class ArchiveRateFormRequest extends FormRequest
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

        if ($this->rate->isNull() || $this->rate->isArchived()) {
            throw CouldNotFindRate::notFound();
        }

        return RateAccess::authorize($this);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        ArchiveRate::archive($this->rate);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name archived.', ['name' => $this->rate->getName()->toString()])]);

        return back();
    }
}
