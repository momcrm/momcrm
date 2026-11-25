<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentEloquentModel;
use App\Attachment\Properties\Id;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Http\UploadedFile;

/**
 * Keeps a picture put into an article's body, as a file attached to the
 * article. Unlike other files it records no activity: it is part of the
 * article's text, not something added beside it.
 */
final readonly class StoreArticleImage
{
    public function __construct(
        private UploadedFile $file,
        private Article $article,
        private ?string $uploadedById,
    ) {}

    public function __invoke(): Attachment
    {
        $teamId = $this->article->getTeamId()->toString();
        $id = Id::generate();
        $path = $this->file->storeAs("attachments/{$teamId}", $id->toString().'.'.($this->file->guessExtension() ?? 'bin'), CreateAttachment::disk());

        $model = AttachmentEloquentModel::query()->create(
            Attachment::new()
                ->setId($id)
                ->setTeamId($teamId)
                ->setUploadedById($this->uploadedById)
                ->setName($this->file->getClientOriginalName())
                ->setPath((string) $path)
                ->setSize($this->file->getSize())
                ->setMimeType($this->file->getMimeType() ?? 'application/octet-stream')
                ->setAttachableType(MorphMapKeyEnum::Article)
                ->setAttachableId($this->article->getId())
                ->forDatabaseCreate(),
        );

        return Attachment::fromEloquentModel($model);
    }

    public static function for(UploadedFile $file, Article $article, ?string $uploadedById): Attachment
    {
        return new self($file, $article, $uploadedById)->handle();
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
