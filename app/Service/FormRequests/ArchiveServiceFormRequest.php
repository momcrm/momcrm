<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Service\Actions\ArchiveService;
use App\Service\Actions\FindService;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Service;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Puts a service away in the archive.
 */
final class ArchiveServiceFormRequest extends FormRequest
{
    public Service $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = Service::new();
    }

    /**
     * @throws CouldNotFindService
     */
    public function authorize(): Response
    {
        $this->service = FindService::byIdForTeam((string) $this->route('service'), $this->user()?->current_team_id);

        if ($this->service->isNull() || $this->service->isArchived()) {
            throw CouldNotFindService::notFound();
        }

        return Gate::authorize('archive', $this->service);
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
        ArchiveService::archive($this->service, (string) $this->user()?->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name archived.', ['name' => $this->service->getName()->toString()])]);

        return back();
    }
}
