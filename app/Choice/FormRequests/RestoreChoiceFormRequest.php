<?php

declare(strict_types=1);

namespace App\Choice\FormRequests;

use App\Choice\Actions\ArchiveChoice;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceAccess;
use App\Choice\Exceptions\CouldNotFindChoice;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Brings an archived choice back, so it is offered again.
 */
final class RestoreChoiceFormRequest extends FormRequest
{
    public Choice $choice;

    public function __construct()
    {
        parent::__construct();

        $this->choice = Choice::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindChoice
     */
    public function authorize(): Response
    {
        $team = ChoiceAccess::team($this);
        $this->choice = FindChoice::byIdForTeam((string) $this->route('choice'), $team->getId()->toString());

        if (! $this->choice->isArchived()) {
            throw CouldNotFindChoice::notFound();
        }

        return ChoiceAccess::authorize($this);
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
        ArchiveChoice::restore($this->choice);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name restored.', ['name' => $this->choice->getName()->toString()])]);

        return back();
    }
}
