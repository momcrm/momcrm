<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Attachment\Attachment;
use App\Attachment\AttachmentEloquentModel;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a file and its stored copy.
 */
final readonly class DeleteAttachment
{
    public function __construct(
        private Attachment $attachment,
    ) {}

    public function __invoke(): void
    {
        Storage::disk(CreateAttachment::DISK)->delete($this->attachment->getPath()->toString());

        AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->attachment->getId()->toString())
            ?->delete();
    }

    public static function fromAttachment(Attachment $attachment): void
    {
        new self($attachment)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
