<?php

declare(strict_types=1);

namespace App\Mailbox\Actions;

use App\Connector\Actions\FindConnector;
use App\Connector\Connector;
use App\Connector\ConnectorTypeEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Clients\MicrosoftMailClient;
use App\Mailbox\Contracts\MailClient;
use App\Mailbox\Mailbox;
use App\Mailbox\MicrosoftGraph;

/**
 * The mail client for a mailbox, picked by the type of connector it goes
 * through. This is the one place that knows which service is which.
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
        return match ($connector->getType()->toEnum()) {
            ConnectorTypeEnum::Microsoft => new MicrosoftMailClient(MicrosoftGraph::forConnector($connector, $sharedAddress)),
        };
    }
}
