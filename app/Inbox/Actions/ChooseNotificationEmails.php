<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Inbox\InboxKindEnum;
use App\User\Actions\UpdateUser;
use App\User\User;

/**
 * Saves which kinds of inbox notification someone is also emailed.
 */
final readonly class ChooseNotificationEmails
{
    /**
     * @param  list<InboxKindEnum>  $kinds
     */
    public function __construct(
        private User $user,
        private array $kinds,
    ) {}

    public function __invoke(): User
    {
        return UpdateUser::fromUser($this->user->setEmailedNotifications(
            array_map(fn (InboxKindEnum $kind): string => $kind->value, $this->kinds),
        ));
    }

    /**
     * @param  list<InboxKindEnum>  $kinds
     */
    public static function forUser(User $user, array $kinds): User
    {
        return new self($user, $kinds)->handle();
    }

    public function handle(): User
    {
        return $this->__invoke();
    }

    public function execute(): User
    {
        return $this->__invoke();
    }
}
