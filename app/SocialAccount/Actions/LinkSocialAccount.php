<?php

declare(strict_types=1);

namespace App\SocialAccount\Actions;

use App\Auth\AuthSession;
use App\SocialAccount\Properties\Id;
use App\SocialAccount\Properties\Provider;
use App\SocialAccount\Properties\ProviderUserId;
use App\SocialAccount\SocialAccount;
use App\SocialAccount\SocialAccountEloquentModel;
use App\SocialAccount\SocialProviderEnum;
use App\User\UserEloquentModel;
use Illuminate\Contracts\Session\Session;

/**
 * Links a Google or Microsoft account to a MomCRM user, so next time that
 * account signs straight in.
 */
final readonly class LinkSocialAccount
{
    public function __construct(
        private UserEloquentModel $user,
        private SocialProviderEnum $provider,
        private string $providerUserId,
        private ?string $email,
    ) {}

    public function __invoke(): void
    {
        $exists = SocialAccountEloquentModel::query()
            ->where(Provider::getDatabaseTableColumnName(), $this->provider->value)
            ->where(ProviderUserId::getDatabaseTableColumnName(), $this->providerUserId)
            ->exists();

        if ($exists) {
            return;
        }

        $account = SocialAccount::new()
            ->setId(Id::generate())
            ->setUserId($this->user->id)
            ->setProvider($this->provider)
            ->setProviderUserId($this->providerUserId)
            ->setEmail($this->email);

        SocialAccountEloquentModel::query()->create($account->forDatabaseCreate());
    }

    public static function forUser(UserEloquentModel $user, SocialProviderEnum $provider, string $providerUserId, ?string $email): void
    {
        new self($user, $provider, $providerUserId, $email)->handle();
    }

    /**
     * Links the account waiting in the session, when its email is the user's.
     */
    public static function fromSession(Session $session, UserEloquentModel $user): void
    {
        $pending = $session->pull(AuthSession::PENDING_SOCIAL);

        if (! is_array($pending) || mb_strtolower((string) ($pending['email'] ?? '')) !== mb_strtolower($user->email)) {
            return;
        }

        $provider = SocialProviderEnum::tryFrom((string) ($pending['provider'] ?? ''));

        if ($provider !== null && is_string($pending['provider_user_id'] ?? null)) {
            self::forUser($user, $provider, $pending['provider_user_id'], $user->email);
        }
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
