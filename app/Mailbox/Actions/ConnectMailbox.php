<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\MailboxStatusEnum;
use App\Mailbox\Properties\Address;
use App\Mailbox\Properties\ConnectedById;
use App\Mailbox\Properties\DisplayName;
use App\Mailbox\Properties\Id;
use App\Mailbox\Properties\IsShared;
use App\Mailbox\Properties\LastError;
use App\Mailbox\Properties\Status;
use App\Mailbox\Properties\SyncedThrough;
use App\Mailbox\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Saves a mailbox someone just signed in to, with the refresh token Microsoft
 * gave, which is encrypted on the way in. Connecting a mailbox the team
 * already has replaces its sign-in and keeps its settings and place in the
 * inbox. A new mailbox is read from now on; mail already in it is left alone.
 */
final readonly class ConnectMailbox
{
    public function __construct(
        private Mailbox $mailbox,
        private string $refreshToken,
    ) {}

    public function __invoke(): Mailbox
    {
        $model = MailboxEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $this->mailbox->getTeamId()->toString())
            ->where(Address::getDatabaseTableColumnName(), $this->mailbox->getAddress()->toString())
            ->first();

        if ($model === null) {
            $model = new MailboxEloquentModel([
                Id::getDatabaseTableColumnName() => Id::generate()->toString(),
                TeamId::getDatabaseTableColumnName() => $this->mailbox->getTeamId()->toString(),
                Address::getDatabaseTableColumnName() => $this->mailbox->getAddress()->toString(),
                SyncedThrough::getDatabaseTableColumnName() => now(),
            ]);
        }

        $model->fill([
            DisplayName::getDatabaseTableColumnName() => $this->mailbox->getDisplayName()->toNullableString(),
            IsShared::getDatabaseTableColumnName() => $this->mailbox->getIsShared()->toBoolean(),
            ConnectedById::getDatabaseTableColumnName() => $this->mailbox->getConnectedById()->toNullableString(),
            Status::getDatabaseTableColumnName() => MailboxStatusEnum::Connected->value,
            LastError::getDatabaseTableColumnName() => null,
        ]);
        $model->refresh_token = $this->refreshToken;
        $model->save();

        return Mailbox::fromEloquentModel($model->refresh());
    }

    public static function fromMailbox(Mailbox $mailbox, string $refreshToken): Mailbox
    {
        return new self($mailbox, $refreshToken)->handle();
    }

    public function handle(): Mailbox
    {
        return $this->__invoke();
    }

    public function execute(): Mailbox
    {
        return $this->__invoke();
    }
}
