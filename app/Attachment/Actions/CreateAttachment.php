<?php

declare(strict_types=1);

namespace App\Attachment\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Attachment\Attachment;
use App\Attachment\AttachmentEloquentModel;
use App\Attachment\Properties\Id;
use App\Shared\MorphMapKeyEnum;
use App\Shared\Types;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Stores an uploaded file on the private disk under its team's folder and
 * attaches it to a ticket or project.
 */
final readonly class CreateAttachment
{
    public const string DISK = 'local';

    public function __construct(
        private UploadedFile $file,
        private Model $attachable,
        private ?string $uploadedById = null,
    ) {}

    public function __invoke(): Attachment
    {
        $teamId = Types::string($this->attachable->getAttribute('team_id'));
        $id = Id::generate();
        $path = $this->file->storeAs("attachments/{$teamId}", $id->toString().'.'.($this->file->guessExtension() ?? 'bin'), self::DISK);

        $model = AttachmentEloquentModel::query()->create(Attachment::new()
            ->setId($id)
            ->setTeamId($teamId)
            ->setAttachableType(MorphMapKeyEnum::fromModel($this->attachable))
            ->setAttachableId(Types::string($this->attachable->getKey()))
            ->setUploadedById($this->uploadedById)
            ->setName($this->file->getClientOriginalName())
            ->setPath((string) $path)
            ->setSize($this->file->getSize())
            ->setMimeType($this->file->getMimeType() ?? 'application/octet-stream')
            ->forDatabaseCreate());

        RecordActivity::for(
            type: ActivityTypeEnum::AttachmentAdded,
            userId: $this->uploadedById,
            teamId: $teamId,
            subject: $this->attachable,
            details: [
                'file' => $model->name,
                ...array_filter([
                    'number' => $this->attachable->getAttribute('number'),
                    'title' => $this->attachable->getAttribute('title') ?? $this->attachable->getAttribute('name'),
                ]),
            ],
        );

        return Attachment::fromEloquentModel($model);
    }

    public static function for(UploadedFile $file, Model $attachable, ?string $uploadedById = null): Attachment
    {
        return new self($file, $attachable, $uploadedById)->handle();
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
