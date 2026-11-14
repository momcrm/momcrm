<?php

declare(strict_types=1);

namespace App\Inbox\FormRequests;

use App\Inbox\Actions\FindNotifications;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Marks every unread notification in the current team read.
 */
final class MarkAllNotificationsReadFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof UserEloquentModel;
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
        FindNotifications::query($user)->whereNull('read_at')->update(['read_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All caught up.')]);

        return back();
    }
}
