<?php

declare(strict_types=1);

namespace App\Inbox\FormRequests;

use App\Inbox\Actions\ChooseNotificationEmails;
use App\Inbox\InboxKindEnum;
use App\Shared\Types;
use App\User\Properties\EmailedNotifications;
use App\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class UpdateNotificationEmailsFormRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            EmailedNotifications::getNameForValidation() => ['present', 'array'],
            EmailedNotifications::getNameForValidation().'.*' => ['string', Rule::enum(InboxKindEnum::class)],
        ];
    }

    public function process(): RedirectResponse
    {
        $chosen = Types::stringList($this->validated()[EmailedNotifications::getNameForValidation()]);

        ChooseNotificationEmails::forUser(
            User::fromEloquentModel($this->user()),
            array_values(array_filter(InboxKindEnum::cases(), fn (InboxKindEnum $kind): bool => in_array($kind->value, $chosen, true))),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification emails saved.')]);

        return to_route('notification-settings.edit');
    }
}
