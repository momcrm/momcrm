<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Service\Actions\DeleteService;
use App\Service\Actions\FindService;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Service;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeleteServiceFormRequest extends FormRequest
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

        if ($this->service->isNull()) {
            throw CouldNotFindService::notFound();
        }

        return Gate::authorize('delete', $this->service);
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
        DeleteService::fromService($this->service);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->service->getName()->toString()])]);

        return to_route('customers.show', $this->service->getCustomerId()->toString());
    }
}
