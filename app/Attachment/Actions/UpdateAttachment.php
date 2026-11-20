<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Attachment\Attachment;
use App\Attachment\AttachmentEloquentModel;
use App\Attachment\Properties\Description;
use App\Attachment\Properties\FolderId;
use App\Attachment\Properties\SharedWithCustomer;

/**
 * Saves a file's description, folder and whether the customer can see it;
 * its stored copy and name stay as they are.
 */
final readonly class UpdateAttachment
{
    public function __construct(
        private Attachment $attachment,
    ) {}

    public function __invoke(): Attachment
    {
        AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->attachment->getId()->toString())
            ?->update([
                Description::getDatabaseTableColumnName() => $this->attachment->getDescription()->toNullableString(),
                FolderId::getDatabaseTableColumnName() => $this->attachment->getFolderId()->toNullableString(),
                SharedWithCustomer::getDatabaseTableColumnName() => $this->attachment->getSharedWithCustomer()->toBoolean(),
            ]);

        return $this->attachment;
    }

    public static function fromAttachment(Attachment $attachment): Attachment
    {
        return new self($attachment)->handle();
    }

    public function handle(): Attachment
    {
        return $this->__invoke();
    }

    public function execute(): Attachment
    {
        return $this->__invoke();
    }
}
