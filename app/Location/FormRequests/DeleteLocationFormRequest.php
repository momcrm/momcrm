<?php

declare(strict_types=1);

namespace App\Location\FormRequests;

use App\Location\Actions\DeleteLocation;
use App\Location\Actions\FindLocation;
use App\Location\Exceptions\CouldNotFindLocation;
use App\Location\Location;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteLocationFormRequest extends FormRequest
{
    public Location $location;

    public function __construct()
    {
        parent::__construct();

        $this->location = Location::new();
    }

    /**
     * @throws CouldNotFindLocation
     */
    public function authorize(): Response
    {
        $this->location = FindLocation::byIdForTeam((string) $this->route('location'), $this->user()?->current_team_id);

        if ($this->location->isNull()) {
            throw CouldNotFindLocation::notFound();
        }

        return Gate::authorize('delete', $this->location);
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
        DeleteLocation::fromLocation($this->location);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->location->getName()->toString()])]);

        return to_route('customers.show', $this->location->getCustomerId()->toString());
    }
}
