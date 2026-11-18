<?php

declare(strict_types=1);

namespace App\Environment;

use App\Environment\Exceptions\CouldNotChangeEnvironmentVariable;
use App\Environment\Exceptions\CouldNotFindEnvironmentVariable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * The subject the environment pages authorise against, and the rules for
 * which variables they hide or refuse to touch. There is no model: the
 * variables live in the `.env` file.
 */
final class EnvironmentVariables
{
    /**
     * Changing the app key invalidates every encrypted value and session, and
     * reading it would let someone forge them, so it is neither shown nor edited.
     *
     * @var list<string>
     */
    public const array LOCKED_KEYS = ['APP_KEY'];

    /**
     * Values whose key mentions one of these are never listed, only revealed
     * one at a time.
     */
    private const string SECRET_PATTERN = '/KEY|SECRET|PASSWORD|PASSWD|TOKEN|DSN|WEBHOOK|PRIVATE|CREDENTIAL|SALT|GUID/';

    private const string CHANGED_AT_CACHE_KEY = 'environment:changed_at';

    private const string APPLIED_AT_CACHE_KEY = 'environment:applied_at';

    /**
     * Stops a request about a variable that is locked or not in the file.
     *
     * @throws CouldNotChangeEnvironmentVariable
     * @throws CouldNotFindEnvironmentVariable
     */
    public static function ensureEditable(EnvironmentFile $file, string $key): void
    {
        if (self::isLocked($key)) {
            throw CouldNotChangeEnvironmentVariable::locked($key);
        }

        if (! $file->has($key)) {
            throw CouldNotFindEnvironmentVariable::notFound($key);
        }
    }

    public static function isSecret(string $key): bool
    {
        return preg_match(self::SECRET_PATTERN, mb_strtoupper($key)) === 1;
    }

    public static function isLocked(string $key): bool
    {
        return in_array($key, self::LOCKED_KEYS, true);
    }

    /**
     * A variable as the page shows it: a secret's value is left out, and only
     * whether it is empty is said.
     *
     * @return array{key: string, value: ?string, is_secret: bool, is_locked: bool, is_empty: bool}
     */
    public static function present(string $key, string $value): array
    {
        $hidden = self::isSecret($key) || self::isLocked($key);

        return [
            'key' => $key,
            'value' => $hidden ? null : $value,
            'is_secret' => $hidden,
            'is_locked' => self::isLocked($key),
            'is_empty' => $value === '',
        ];
    }

    /**
     * When a change made on the page last landed in the file.
     */
    public static function changedAt(): ?CarbonImmutable
    {
        return self::timestamp(self::CHANGED_AT_CACHE_KEY);
    }

    /**
     * When the file was last applied to the cached config and the workers.
     */
    public static function appliedAt(): ?CarbonImmutable
    {
        return self::timestamp(self::APPLIED_AT_CACHE_KEY);
    }

    /**
     * Whether a change made on the page has not been applied yet. Edits made
     * over SSH are not tracked.
     */
    public static function hasPendingChanges(): bool
    {
        $changedAt = self::changedAt();
        $appliedAt = self::appliedAt();

        return $changedAt !== null && ($appliedAt === null || $changedAt->greaterThan($appliedAt));
    }

    public static function markChanged(): void
    {
        Cache::forever(self::CHANGED_AT_CACHE_KEY, CarbonImmutable::now()->getTimestampMs());
    }

    public static function markApplied(): void
    {
        Cache::forever(self::APPLIED_AT_CACHE_KEY, CarbonImmutable::now()->getTimestampMs());
    }

    private static function timestamp(string $cacheKey): ?CarbonImmutable
    {
        $timestamp = Cache::get($cacheKey);

        return is_int($timestamp) ? CarbonImmutable::createFromTimestampMs($timestamp) : null;
    }
}
