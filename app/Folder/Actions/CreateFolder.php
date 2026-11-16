<?php

declare(strict_types=1);

namespace App\Folder\Actions;

use App\Folder\Folder;
use App\Folder\FolderEloquentModel;
use App\Folder\Properties\Id;

final readonly class CreateFolder
{
    public function __construct(
        private Folder $folder,
    ) {}

    public function __invoke(): Folder
    {
        if ($this->folder->getId()->isNull()) {
            $this->folder->setId(Id::generate());
        }

        $model = FolderEloquentModel::query()->create($this->folder->forDatabaseCreate());

        return Folder::fromEloquentModel($model->refresh());
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
