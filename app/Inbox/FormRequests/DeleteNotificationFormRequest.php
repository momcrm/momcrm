<?php

declare(strict_types=1);

namespace App\Inbox\FormRequests;

use App\Inbox\Actions\FindNotifications;
use App\Inbox\Exceptions\CouldNotFindNotification;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Clears a notification from the list.
 */
final class DeleteNotificationFormRequest extends FormRequest
{
    public ?DatabaseNotification $notification = null;

    /**
     * @throws CouldNotFindNotification
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $this->notification = $user instanceof UserEloquentModel
            ? FindNotifications::byIdForUser($user, (string) $this->route('notification'))
            : null;

        if ($this->notification === null) {
            throw CouldNotFindNotification::notFound();
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
        $this->notification?->delete();

        return back();
    }
}
