<?php

declare(strict_types=1);

namespace App\TeamUser;

use App\Shared\Table;
use App\TeamUser\Properties\Id;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $team_id
 * @property string $user_id
 * @property string $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class TeamUserEloquentModel extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function getTable(): string
    {
        return Table::TeamUser->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }
}
