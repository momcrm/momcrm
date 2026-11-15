<?php

declare(strict_types=1);

namespace App\Milestone\FormRequests;

use App\Milestone\Actions\FindMilestone;
use App\Milestone\Exceptions\CouldNotFindMilestone;
use App\Milestone\Milestone;
use App\Milestone\MilestoneEloquentModel;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DeleteMilestoneFormRequest extends FormRequest
{
    public Milestone $milestone;

    public function __construct()
    {
        parent::__construct();

        $this->milestone = Milestone::new();
    }

    /**
     * @throws CouldNotFindMilestone
     */
    public function authorize(): Response
    {
        $this->milestone = FindMilestone::visibleByIdForTeam((string) $this->route('milestone'), $this->user()?->current_team_id, (string) $this->user()?->getAuthIdentifier());

        if ($this->milestone->isNull()) {
            throw CouldNotFindMilestone::notFound();
        }

        return Gate::authorize('delete', $this->milestone);
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
        MilestoneEloquentModel::query()->findOrFail($this->milestone->getId()->toString())->delete();

        return back();
    }
}
