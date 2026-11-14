<?php

declare(strict_types=1);

namespace App\Attachment\Factories;

use App\Attachment\AttachmentEloquentModel;
use App\Attachment\Properties\AttachableId;
use App\Attachment\Properties\AttachableType;
use App\Attachment\Properties\MimeType;
use App\Attachment\Properties\Name;
use App\Attachment\Properties\Path;
use App\Attachment\Properties\Size;
use App\Attachment\Properties\TeamId;
use App\Attachment\Properties\UploadedById;
use App\Shared\MorphMapKeyEnum;
use App\Ticket\TicketEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<AttachmentEloquentModel>
 */
final class AttachmentFactory extends Factory
{
    protected $model = AttachmentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            AttachableType::getDatabaseTableColumnName() => MorphMapKeyEnum::Ticket->value,
            AttachableId::getDatabaseTableColumnName() => TicketEloquentModel::factory(),
            TeamId::getDatabaseTableColumnName() => fn (array $attributes): string => (string) TicketEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($attributes[AttachableId::getDatabaseTableColumnName()])
                ->value('team_id'),
            UploadedById::getDatabaseTableColumnName() => null,
            Name::getDatabaseTableColumnName() => 'invoice.pdf',
            Path::getDatabaseTableColumnName() => 'attachments/'.fake()->uuid().'.pdf',
            Size::getDatabaseTableColumnName() => 2048,
            MimeType::getDatabaseTableColumnName() => 'application/pdf',
        ];
    }

    /**
     * An attachment on the given ticket or project, in its team.
     */
    public function on(Model $attachable): self
    {
        return $this->state([
            AttachableType::getDatabaseTableColumnName() => $attachable->getMorphClass(),
            AttachableId::getDatabaseTableColumnName() => $attachable->getKey(),
            TeamId::getDatabaseTableColumnName() => $attachable->getAttribute('team_id'),
        ]);
    }
}
