<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\Properties\AutoCreateTickets;
use App\Mailbox\Properties\GroupId;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Saves the settings a team chooses for a mailbox: where its new tickets go
 * and whether it opens tickets at all.
 */
final readonly class UpdateMailbox
{
    public function __construct(
        private Mailbox $mailbox,
    ) {}

    public function __invoke(): Mailbox
    {
        $model = MailboxEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->mailbox->getId()->toString());

        if ($model === null) {
            return $this->mailbox;
        }

        $model->update([
            GroupId::getDatabaseTableColumnName() => $this->mailbox->getGroupId()->toNullableString(),
            AutoCreateTickets::getDatabaseTableColumnName() => $this->mailbox->getAutoCreateTickets()->toBoolean(),
        ]);

        return Mailbox::fromEloquentModel($model->refresh());
    }

    public static function fromMailbox(Mailbox $mailbox): Mailbox
    {
        return new self($mailbox)->handle();
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
