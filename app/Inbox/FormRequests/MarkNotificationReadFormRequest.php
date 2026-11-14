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
 * Marks one notification read, or unread again with `read: false`.
 */
final class MarkNotificationReadFormRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['read' => ['sometimes', 'boolean']];
    }

    public function process(): RedirectResponse
    {
        if ($this->boolean('read', true)) {
            $this->notification?->markAsRead();
        } else {
            $this->notification?->markAsUnread();
        }

        return back();
    }
}
