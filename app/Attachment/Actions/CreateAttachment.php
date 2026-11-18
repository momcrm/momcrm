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
use Illuminate\Support\Facades\Storage;

/**
 * Stores an uploaded file on the private disk under its team's folder and
 * attaches it to a ticket, project or customer (into one of its folders).
 */
final readonly class CreateAttachment
{
    public const string DISK = 'local';

    public function __construct(
        private UploadedFile $file,
        private Model $attachable,
        private ?string $uploadedById = null,
        private ?string $folderId = null,
    ) {}

    public function __invoke(): Attachment
    {
        $teamId = Types::string($this->attachable->getAttribute('team_id'));
        $id = Id::generate();
        $path = $this->file->storeAs("attachments/{$teamId}", $id->toString().'.'.($this->file->guessExtension() ?? 'bin'), self::DISK);

        return self::record(
            Attachment::new()
                ->setId($id)
                ->setTeamId($teamId)
                ->setUploadedById($this->uploadedById)
                ->setFolderId($this->folderId)
                ->setName($this->file->getClientOriginalName())
                ->setPath((string) $path)
                ->setSize($this->file->getSize())
                ->setMimeType($this->file->getMimeType() ?? 'application/octet-stream'),
            $this->attachable,
        );
    }

    /**
     * Stores a file we were handed as bytes, not uploaded by anyone signed
     * in: an attachment on an email. Its name and type are what the sender
     * said they are, so they are tidied and only ever used as labels; files
     * are always served as downloads. The sender is named in the activity,
     * as there is nobody in the team to name.
     */
    public static function fromContents(string $name, string $contents, ?string $mimeType, Model $attachable, ?string $addedByName = null): Attachment
    {
        $teamId = Types::string($attachable->getAttribute('team_id'));
        $id = Id::generate();
        $name = mb_substr(mb_trim(basename(str_replace('\\', '/', $name))), 0, 255);
        $name = $name === '' ? 'attachment' : $name;
        $extension = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $path = "attachments/{$teamId}/{$id->toString()}.".(preg_match('/^[a-z0-9]{1,10}$/', $extension) === 1 ? $extension : 'bin');

        Storage::disk(self::DISK)->put($path, $contents);

        return self::record(
            Attachment::new()
                ->setId($id)
                ->setTeamId($teamId)
                ->setName($name)
                ->setPath($path)
                ->setSize(strlen($contents))
                ->setMimeType(mb_substr($mimeType === null || mb_trim($mimeType) === '' ? 'application/octet-stream' : mb_trim($mimeType), 0, 150)),
            $attachable,
            $addedByName,
        );
    }

    public static function for(UploadedFile $file, Model $attachable, ?string $uploadedById = null, ?string $folderId = null): Attachment
    {
        return new self($file, $attachable, $uploadedById, $folderId)->handle();
    }

    public function handle(): Attachment
    {
        return $this->__invoke();
    }

    public function execute(): Attachment
    {
        return $this->__invoke();
    }

    /**
     * Saves the attachment's row on what it is attached to, and records it.
     */
    private static function record(Attachment $attachment, Model $attachable, ?string $addedByName = null): Attachment
    {
        $uploadedById = $attachment->getUploadedById()->toNullableString();

        $model = AttachmentEloquentModel::query()->create($attachment
            ->setAttachableType(MorphMapKeyEnum::fromModel($attachable))
            ->setAttachableId(Types::string($attachable->getKey()))
            ->forDatabaseCreate());

        RecordActivity::for(
            type: ActivityTypeEnum::AttachmentAdded,
            userId: $uploadedById,
            teamId: $attachment->getTeamId()->toString(),
            subject: $attachable,
            details: [
                'file' => $model->name,
                ...array_filter([
                    'number' => $attachable->getAttribute('number'),
                    'title' => $attachable->getAttribute('title') ?? $attachable->getAttribute('name'),
                    'actor_name' => $uploadedById === null ? $addedByName : null,
                ]),
            ],
        );

        return Attachment::fromEloquentModel($model);
    }
}
