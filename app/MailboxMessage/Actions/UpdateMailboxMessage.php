<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateMailboxMessage
{
    public function __construct(
        private MailboxMessage $message,
    ) {}

    public function __invoke(): MailboxMessage
    {
        $model = MailboxMessageEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->message->getId()->toString());

        if ($model === null) {
            return $this->message;
        }

        $model->update($this->message->forDatabaseUpdate());

        return MailboxMessage::fromEloquentModel($model->refresh());
    }

    public static function fromMessage(MailboxMessage $message): MailboxMessage
    {
        return new self($message)->handle();
    }

    public function handle(): MailboxMessage
    {
        return $this->__invoke();
    }

    public function execute(): MailboxMessage
    {
        return $this->__invoke();
    }
}
