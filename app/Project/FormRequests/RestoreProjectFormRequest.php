<?php

declare(strict_types=1);

namespace App\Project\FormRequests;

use App\Project\Actions\ArchiveProject;
use App\Project\Actions\FindProject;
use App\Project\Exceptions\CouldNotFindProject;
use App\Project\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
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
    public function authorize(): bool
    {
        $this->project = FindProject::visibleByIdForTeam((string) $this->route('project'), $this->user()?->current_team_id, (string) $this->user()?->getAuthIdentifier());

        if ($this->project->isNull() || ! $this->project->isArchived()) {
            throw CouldNotFindProject::notFound();
        }

        return true;
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
        ArchiveProject::restore($this->project, userId: (string) $this->user()?->getAuthIdentifier());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name restored.', ['name' => $this->project->getName()->toString()])]);

        return back();
    }
}
