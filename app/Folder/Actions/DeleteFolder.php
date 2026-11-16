<?php

declare(strict_types=1);

namespace App\Folder\Actions;

use App\Attachment\AttachmentEloquentModel;
use App\Attachment\Properties\FolderId;
use App\Folder\Folder;
use App\Folder\FolderEloquentModel;
use App\Folder\Properties\Id;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a folder and the folders inside it. Their files are kept and go
 * back to the customer's top level, as staff-vue does.
 */
final readonly class DeleteFolder
{
    public function __construct(
        private Folder $folder,
    ) {}

    public function __invoke(): void
    {
        $ids = FindFolder::withDescendantIds($this->folder);

        DB::transaction(function () use ($ids): void {
            AttachmentEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereIn(FolderId::getDatabaseTableColumnName(), $ids)
                ->get()
                ->each(fn (AttachmentEloquentModel $file): bool => $file->update([FolderId::getDatabaseTableColumnName() => null]));

            $folders = FolderEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereIn(Id::getDatabaseTableColumnName(), $ids)
                ->get()
                ->keyBy(Id::getDatabaseTableColumnName());

            foreach (array_reverse($ids) as $id) {
                $folders->get($id)?->delete();
            }
        });
    }

    public static function fromFolder(Folder $folder): void
    {
        new self($folder)->handle();
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
