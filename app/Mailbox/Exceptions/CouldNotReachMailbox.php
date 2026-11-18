<?php

declare(strict_types=1);

namespace App\Mailbox\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Microsoft would not let us into a mailbox. The message is written for the
 * team's admins, who see it on the email settings page.
 */
final class CouldNotReachMailbox extends RuntimeException
{
    /**
     * The stored sign-in is gone or no longer accepted, so only connecting the
     * mailbox again will fix it.
     */
    public static function needsReconnect(): self
    {
        return new self('Microsoft no longer accepts the sign-in for this mailbox. Connect it again.');
    }

    /**
     * Microsoft could not be reached or answered with an error that may pass.
     */
    public static function unavailable(?Throwable $previous = null): self
    {
        return new self('Microsoft could not be reached. It will be tried again.', previous: $previous);
    }

    /**
     * Microsoft refused the request, for example because the signed-in person
     * has no access to the mailbox.
     */
    public static function refused(string $reason, ?Throwable $previous = null): self
    {
        return new self($reason, previous: $previous);
    }
}
