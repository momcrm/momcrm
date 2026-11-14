<?php

declare(strict_types=1);

namespace App\Shared;

use Illuminate\Support\Facades\Context;

/**
 * Request details for log, audit and activity rows. Values are only present
 * while handling an HTTP request (AddRequestContext middleware has run).
 */
final class RequestContext
{
    public const string REQUEST_ID = 'request_id';

    public const string USER_ID = 'user_id';

    public const string TEAM_ID = 'team_id';

    public static function requestId(): ?string
    {
        $value = Context::get(self::REQUEST_ID);

        return is_string($value) ? $value : null;
    }

    public static function isHttp(): bool
    {
        return self::requestId() !== null;
    }

    public static function ipAddress(): ?string
    {
        return self::isHttp() ? request()->ip() : null;
    }

    public static function userAgent(): ?string
    {
        return self::isHttp() ? request()->userAgent() : null;
    }

    public static function url(): ?string
    {
        return self::isHttp() ? request()->fullUrl() : null;
    }
}
