<?php

declare(strict_types=1);

namespace App\SocialAccount\Actions;

use App\SocialAccount\Properties\Provider;
use App\SocialAccount\Properties\ProviderUserId;
use App\SocialAccount\SocialAccountEloquentModel;
use App\SocialAccount\SocialAccountRelationship;
use App\SocialAccount\SocialProviderEnum;
use App\User\User;
use App\User\UserEloquentModel;

final class FindSocialAccount
{
    /**
     * The MomCRM user a Google or Microsoft account is linked to; empty when none is.
     */
    public static function userFor(SocialProviderEnum $provider, string $providerUserId): User
    {
        $account = SocialAccountEloquentModel::query()
            ->with(SocialAccountRelationship::User->value)
            ->where(Provider::getDatabaseTableColumnName(), $provider->value)
            ->where(ProviderUserId::getDatabaseTableColumnName(), $providerUserId)
            ->first();

        $user = $account?->getRelationValue(SocialAccountRelationship::User->value);

        return User::fromEloquentModel($user instanceof UserEloquentModel ? $user : null);
    }
}
