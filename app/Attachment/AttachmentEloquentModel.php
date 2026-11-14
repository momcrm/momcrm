<?php

declare(strict_types=1);

namespace App\Attachment;

use App\Attachment\Factories\AttachmentFactory;
use App\Attachment\Properties\AttachableId;
use App\Attachment\Properties\AttachableType;
use App\Attachment\Properties\Id;
use App\Attachment\Properties\Size;
use App\Attachment\Properties\UploadedById;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A file uploaded to a ticket or a project, kept on the private disk.
 *
 * @property string $attachable_id
 * @property string $attachable_type
 * @property CarbonImmutable|null $created_at
 * @property string $id
 * @property string $mime_type
 * @property string $name
 * @property string $path
 * @property int $size
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 * @property string|null $uploaded_by_id
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class AttachmentEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::Attachments->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo(
            name: AttachmentRelationship::Attachable->value,
            type: AttachableType::getDatabaseTableColumnName(),
            id: AttachableId::getDatabaseTableColumnName(),
        );
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: UploadedById::getDatabaseTableColumnName(),
            relation: AttachmentRelationship::Uploader->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Size::getDatabaseTableColumnName() => 'integer',
        ];
    }

    protected static function newFactory(): AttachmentFactory
    {
        return AttachmentFactory::new();
    }
}
