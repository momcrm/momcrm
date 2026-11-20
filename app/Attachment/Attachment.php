<?php

declare(strict_types=1);

namespace App\Attachment;

use App\Attachment\Factories\AttachmentFactory;
use App\Attachment\Properties\AttachableId;
use App\Attachment\Properties\AttachableType;
use App\Attachment\Properties\CreatedAt;
use App\Attachment\Properties\Description;
use App\Attachment\Properties\FolderId;
use App\Attachment\Properties\Id;
use App\Attachment\Properties\MimeType;
use App\Attachment\Properties\Name;
use App\Attachment\Properties\Path;
use App\Attachment\Properties\SharedWithCustomer;
use App\Attachment\Properties\Size;
use App\Attachment\Properties\TeamId;
use App\Attachment\Properties\UpdatedAt;
use App\Attachment\Properties\UploadedByContactId;
use App\Attachment\Properties\UploadedById;
use App\Attachment\Properties\Uploader;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<AttachmentEloquentModel>
 */
final class Attachment extends AbstractData
{
    public function __construct(
        public AttachableId $attachableId,
        public AttachableType $attachableType,
        public CreatedAt $createdAt,
        public Description $description,
        public FolderId $folderId,
        public Id $id,
        public MimeType $mimeType,
        public Name $name,
        public Path $path,
        public Size $size,
        public SharedWithCustomer $sharedWithCustomer,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public UploadedById $uploadedById,
        public UploadedByContactId $uploadedByContactId,
        public Uploader $uploader,
    ) {}

    public static function getFactory(): AttachmentFactory
    {
        return AttachmentEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): AttachmentCollection
    {
        if ($items instanceof AttachmentCollection) {
            return $items;
        }

        return AttachmentCollection::make(parent::collect($items, $method)->all());
    }

    public function getAttachableId(): AttachableId
    {
        return $this->attachableId;
    }

    public function setAttachableId(mixed $attachableId): self
    {
        $this->attachableId = new AttachableId($attachableId instanceof AbstractValue ? $attachableId->toValue() : $attachableId);

        return $this;
    }

    public function getAttachableType(): AttachableType
    {
        return $this->attachableType;
    }

    public function setAttachableType(mixed $attachableType): self
    {
        $this->attachableType = new AttachableType($attachableType instanceof AbstractValue ? $attachableType->toValue() : $attachableType);

        return $this;
    }

    public function getSharedWithCustomer(): SharedWithCustomer
    {
        return $this->sharedWithCustomer;
    }

    public function setSharedWithCustomer(mixed $sharedWithCustomer): self
    {
        $this->sharedWithCustomer = new SharedWithCustomer($sharedWithCustomer instanceof AbstractValue ? $sharedWithCustomer->toValue() : $sharedWithCustomer);

        return $this;
    }

    public function getUploadedByContactId(): UploadedByContactId
    {
        return $this->uploadedByContactId;
    }

    public function setUploadedByContactId(mixed $uploadedByContactId): self
    {
        $this->uploadedByContactId = new UploadedByContactId($uploadedByContactId instanceof AbstractValue ? $uploadedByContactId->toValue() : $uploadedByContactId);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getMimeType(): MimeType
    {
        return $this->mimeType;
    }

    public function setMimeType(mixed $mimeType): self
    {
        $this->mimeType = new MimeType($mimeType instanceof AbstractValue ? $mimeType->toValue() : $mimeType);

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(mixed $name): self
    {
        $this->name = new Name($name instanceof AbstractValue ? $name->toValue() : $name);

        return $this;
    }

    public function getPath(): Path
    {
        return $this->path;
    }

    public function setPath(mixed $path): self
    {
        $this->path = new Path($path instanceof AbstractValue ? $path->toValue() : $path);

        return $this;
    }

    public function getSize(): Size
    {
        return $this->size;
    }

    public function setSize(mixed $size): self
    {
        $this->size = new Size($size instanceof AbstractValue ? $size->toValue() : $size);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getUploadedById(): UploadedById
    {
        return $this->uploadedById;
    }

    public function setUploadedById(mixed $uploadedById): self
    {
        $this->uploadedById = new UploadedById($uploadedById instanceof AbstractValue ? $uploadedById->toValue() : $uploadedById);

        return $this;
    }

    public function getUploader(): Uploader
    {
        return $this->uploader;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(mixed $description): self
    {
        $this->description = new Description($description instanceof AbstractValue ? $description->toValue() : $description);

        return $this;
    }

    public function getFolderId(): FolderId
    {
        return $this->folderId;
    }

    public function setFolderId(mixed $folderId): self
    {
        $this->folderId = new FolderId($folderId instanceof AbstractValue ? $folderId->toValue() : $folderId);

        return $this;
    }
}
