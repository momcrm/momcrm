<?php

declare(strict_types=1);

namespace App\Inbox\FormRequests;

use App\Inbox\Actions\DeleteNotification;
use App\Inbox\Actions\FindNotifications;
use App\Inbox\Exceptions\CouldNotFindNotification;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Clears a notification from the list.
 */
final class DeleteNotificationFormRequest extends FormRequest
{
    /**
     * @throws CouldNotFindNotification
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $exists = $user instanceof UserEloquentModel
            && FindNotifications::existsForUser($user, (string) $this->route('notification'));

        if (! $exists) {
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
        /** @var UserEloquentModel $user */
        $user = $this->user();
        DeleteNotification::for($user, (string) $this->route('notification'));

        return back();
    }
}
