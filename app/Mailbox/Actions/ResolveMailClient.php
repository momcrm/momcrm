<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Connector\Actions\FindConnector;
use App\Connector\Connector;
use App\Mailbox\Mailbox;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\ProvidesMail;

/**
 * The mail client for a mailbox, which the type of connector it goes through
 * provides. Nothing here knows one mail service from another.
 */
final class ResolveMailClient
{
    /**
     * @throws CouldNotReachConnector
     */
    public static function forMailbox(Mailbox $mailbox): MailClient
    {
        $connector = FindConnector::byId($mailbox->getConnectorId()->toNullableString());

        if ($connector->isNull()) {
            throw CouldNotReachConnector::refused((string) __('The account this mailbox went through is no longer connected.'));
        }

        return self::forConnector(
            $connector,
            $mailbox->getIsShared()->toBoolean() ? $mailbox->getAddress()->toString() : null,
        );
    }

    /**
     * Through a connected account: its own mailbox, or a shared mailbox it
     * can open when an address is given.
     *
     * @throws CouldNotReachConnector
     */
    public static function forConnector(Connector $connector, ?string $sharedAddress = null): MailClient
    {
        $type = $connector->connectorType();

        if (! $type instanceof ProvidesMail) {
            throw CouldNotReachConnector::refused((string) __(':type accounts cannot be used for email.', ['type' => $type->label()]));
        }

        return $type->mailClient($connector, $sharedAddress);
    }
}
