<?php

declare(strict_types=1);

namespace App\Inbox\FormRequests;

use App\Inbox\Actions\FindNotifications;
use App\Inbox\Actions\MarkNotificationRead;
use App\Inbox\Exceptions\CouldNotFindNotification;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Marks one notification read, or unread again with `read: false`.
 */
final class MarkNotificationReadFormRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['read' => ['sometimes', 'boolean']];
    }

    public function process(): RedirectResponse
    {
        /** @var UserEloquentModel $user */
        $user = $this->user();
        MarkNotificationRead::for($user, (string) $this->route('notification'), $this->boolean('read', true));

        return back();
    }
}
