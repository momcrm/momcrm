<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\Properties\Id;
use App\Mailbox\Properties\SyncedThrough;

/**
 * Adds a mailbox to a team. It is read from now on: mail already in it is
 * left alone.
 */
final readonly class CreateMailbox
{
    public function __construct(
        private Mailbox $mailbox,
    ) {}

    public function __invoke(): Mailbox
    {
        if ($this->mailbox->getId()->isNull()) {
            $this->mailbox->setId(Id::generate());
        }

        $model = MailboxEloquentModel::query()->create([
            ...$this->mailbox->forDatabaseCreate(),
            SyncedThrough::getDatabaseTableColumnName() => now(),
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
