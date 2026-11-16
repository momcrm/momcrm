<?php

declare(strict_types=1);

namespace App\Folder\Actions;

use App\Folder\Folder;
use App\Folder\FolderEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Renames a folder or moves it under another.
 */
final readonly class UpdateFolder
{
    public function __construct(
        private Folder $folder,
    ) {}

    public function __invoke(): Folder
    {
        FolderEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->folder->getId()->toString())
            ?->update($this->folder->forDatabaseUpdate());

        return $this->folder;
    }

    public static function fromFolder(Folder $folder): Folder
    {
        return new self($folder)->handle();
    }

    public function handle(): Folder
    {
        return $this->__invoke();
    }

    public function execute(): Folder
    {
        return $this->__invoke();
    }
}
