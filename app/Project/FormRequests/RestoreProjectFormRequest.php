<?php

declare(strict_types=1);

namespace App\Project\FormRequests;

use App\Project\Actions\ArchiveProject;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class RestoreProjectFormRequest extends FormRequest
{
    public Project $project;

    public function __construct()
    {
        parent::__construct();

        $this->project = Project::new();
    }

    /**
     * @throws CouldNotFindProject
     */
    public function authorize(): Response
    {
        $this->project = FindProject::visibleByIdForTeam((string) $this->route('project'), $this->user()?->current_team_id, (string) $this->user()?->id);

        if ($this->project->isNull() || ! $this->project->isArchived()) {
            throw CouldNotFindProject::notFound();
        }

        return Gate::authorize('restore', $this->project);
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
        ArchiveProject::restore($this->project, userId: (string) $this->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name restored.', ['name' => $this->project->getName()->toString()])]);

        return back();
    }
}
