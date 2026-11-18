<?php

declare(strict_types=1);

namespace App\Connector\Exceptions;

use App\Connector\ConnectorTypeEnum;
use RuntimeException;
use Throwable;

/**
 * The outside service would not let us in through a connector. The message is
 * written for the team's admins, who see it in settings.
 */
final class CouldNotReachConnector extends RuntimeException
{
    /**
     * Whether trying again later may work without anyone doing anything.
     */
    public bool $isTemporary = false;

    /**
     * What we have stored is gone or no longer accepted, so only connecting
     * the account again will fix it.
     */
    public static function needsReconnect(ConnectorTypeEnum $type): self
    {
        return new self((string) __(':company no longer accepts the sign-in for this connector. Connect it again.', [
            'company' => $type->company(),
        ]));
    }

    /**
     * The service could not be reached or answered with an error that may pass.
     */
    public static function unavailable(ConnectorTypeEnum $type, ?Throwable $previous = null): self
    {
        $exception = new self((string) __(':company could not be reached. It will be tried again.', [
            'company' => $type->company(),
        ]), previous: $previous);
        $exception->isTemporary = true;

        return $exception;
    }

    /**
     * The service refused the request, for example because the connected
     * account may not do what was asked.
     */
    public static function refused(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
