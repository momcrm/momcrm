<?php

declare(strict_types=1);

namespace App\Choice\FormRequests;

use App\Choice\Actions\DeleteChoice;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceAccess;
use App\Choice\Exceptions\CouldNotFindChoice;
use App\Team\Exceptions\CouldNotViewTeam;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Validator;
use Inertia\Inertia;

/**
 * Deletes a choice for good. Statuses and priorities must be archived and
 * unused first, so no ticket is left without one; a label can go at any time,
 * coming off the tickets that have it.
 */
final class DeleteChoiceFormRequest extends FormRequest
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

        if ($this->choice->isNull() || (! $this->choice->isArchived() && ! $this->choice->getListName()->toEnum()->isOptional())) {
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

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->choice->getListName()->toEnum()->isOptional()) {
                return;
            }

            $usage = FindChoice::usageCountsForTeam($this->choice->getTeamId()->toString(), $this->choice->getListName()->toEnum())[$this->choice->getId()->toString()] ?? 0;

            if ($usage > 0) {
                $validator->errors()->add('choice', trans_choice(':name is still used by :count record, so it stays archived.|:name is still used by :count records, so it stays archived.', $usage, [
                    'name' => $this->choice->getName()->toString(),
                    'count' => $usage,
                ]));
            }
        }];
    }

    public function process(): RedirectResponse
    {
        DeleteChoice::fromChoice($this->choice);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->choice->getName()->toString()])]);

        return back();
    }
}
