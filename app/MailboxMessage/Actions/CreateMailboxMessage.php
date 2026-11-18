<?php

declare(strict_types=1);

namespace App\MailboxMessage\Actions;

use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\MailboxMessage\Properties\Id;

final readonly class CreateMailboxMessage
{
    public function __construct(
        private MailboxMessage $message,
    ) {}

    public function __invoke(): MailboxMessage
    {
        if ($this->message->getId()->isNull()) {
            $this->message->setId(Id::generate());
        }

        $model = MailboxMessageEloquentModel::query()->create($this->message->forDatabaseCreate());

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
