<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Service\Actions\FindService;
use App\Service\Actions\UpdateService;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Properties\AutoRenew;
use App\Service\Service;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Turns a service's auto-renew on or off. Only an active service that isn't
 * archived can renew by itself.
 */
final class SetServiceAutoRenewFormRequest extends FormRequest
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

        return Gate::authorize('update', $this->service);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            AutoRenew::getNameForValidation() => [
                'required',
                'boolean',
                ...($this->boolean(AutoRenew::getNameForValidation()) && ($this->service->isArchived() || ! $this->service->isActive()) ? ['declined'] : []),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            AutoRenew::getNameForValidation().'.declined' => __('Turn the service on before it can renew by itself.'),
        ];
    }

    public function process(): RedirectResponse
    {
        $isOn = $this->boolean(AutoRenew::getNameForValidation());
        UpdateService::fromService($this->service->setAutoRenew($isOn));

        Inertia::flash('toast', ['type' => 'success', 'message' => $isOn
            ? __(':name will renew by itself.', ['name' => $this->service->getName()->toString()])
            : __(':name will no longer renew by itself.', ['name' => $this->service->getName()->toString()])]);

        return back();
    }
}
