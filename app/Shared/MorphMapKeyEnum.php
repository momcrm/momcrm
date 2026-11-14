<?php

declare(strict_types=1);

namespace App\Shared;

use App\Activity\ActivityEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Passkey\PasskeyEloquentModel;
use App\Shared\Traits\Enum;
use App\Team\TeamEloquentModel;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Model;

/**
 * The morph map: one alias per Eloquent model, enforced in AppServiceProvider.
 *
 * The values are stored in polymorphic columns (audit_logs.auditable_type,
 * activities.subject_type), so never change an existing value. Case names
 * may be renamed freely. Every new EloquentModel needs a case here.
 */
enum MorphMapKeyEnum: string
{
    use Enum;

    case Activity = 'activity';
    case AuditLog = 'audit_log';
    case Contact = 'contact';
    case Customer = 'customer';
    case Passkey = 'passkey';
    case Team = 'team';
    case TeamInvitation = 'team_invitation';
    case TeamUser = 'team_user';
    case User = 'user';

    /**
     * The alias => model class map for Relation::enforceMorphMap().
     *
     * @return array<string, class-string<Model>>
     */
    public static function map(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $key): array => [$key->value => $key->modelClass()])
            ->all();
    }

    /**
     * The alias of a model, as stored in polymorphic columns.
     */
    public static function fromModel(Model $model): self
    {
        return self::from($model->getMorphClass());
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Activity => ActivityEloquentModel::class,
            self::AuditLog => AuditLogEloquentModel::class,
            self::Contact => ContactEloquentModel::class,
            self::Customer => CustomerEloquentModel::class,
            self::Passkey => PasskeyEloquentModel::class,
            self::Team => TeamEloquentModel::class,
            self::TeamInvitation => TeamInvitationEloquentModel::class,
            self::TeamUser => TeamUserEloquentModel::class,
            self::User => UserEloquentModel::class,
        };
    }
}
