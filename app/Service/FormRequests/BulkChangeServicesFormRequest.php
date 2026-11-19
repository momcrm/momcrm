<?php

declare(strict_types=1);

namespace App\Service\FormRequests;

use App\Service\Actions\ArchiveService;
use App\Service\Actions\ChangeServiceStatus;
use App\Service\BulkServiceSelection;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\Service;
use App\Service\ServiceBulkActionEnum;
use App\Service\ServiceCollection;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Turns several services on or off, or archives or restores them, from the
 * bar the services list shows once services are picked. Each service is
 * authorized as if it were changed on its own, and if any one is refused,
 * none change. Services already that way are left as they are; archived
 * services are not turned on or off.
 */
final class BulkChangeServicesFormRequest extends FormRequest
{
    public const string ACTION = 'action';

    public ServiceCollection $services;

    public function __construct()
    {
        parent::__construct();

        $this->services = new ServiceCollection([]);
    }

    /**
     * @throws CouldNotFindService
     */
    public function authorize(): Response
    {
        $this->services = BulkServiceSelection::load($this);
        $action = ServiceBulkActionEnum::tryFrom($this->string(self::ACTION)->toString());

        if ($action === null) {
            return Response::allow();
        }

        foreach ($this->services as $service) {
            Gate::authorize($action->ability(), $service);
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...BulkServiceSelection::rules(),
            self::ACTION => ['required', Rule::enum(ServiceBulkActionEnum::class)],
        ];
    }

    public function process(): RedirectResponse
    {
        $action = ServiceBulkActionEnum::from($this->string(self::ACTION)->toString());
        $userId = (string) $this->user()?->id;

        DB::transaction(function () use ($action, $userId): void {
            $this->services->each(fn (Service $service): Service => match ($action) {
                ServiceBulkActionEnum::Activate => $service->isArchived() ? $service : ChangeServiceStatus::activate($service, $userId),
                ServiceBulkActionEnum::Deactivate => $service->isArchived() ? $service : ChangeServiceStatus::deactivate($service, $userId),
                ServiceBulkActionEnum::Archive => ArchiveService::archive($service, $userId),
                ServiceBulkActionEnum::Restore => ArchiveService::restore($service, $userId),
            });
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $action->message($this->services->count())]);

        return back();
    }
}
