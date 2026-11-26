<?php

declare(strict_types=1);

namespace App\Mailbox\Contracts;

use Mom\Connectors\Connection;
use Mom\Connectors\CouldNotReachConnector;

/**
 * What a connector type implements when its service hands a mailbox's mail
 * over only once it has been told to, such as by a routing rule, and keeps
 * doing so until it is told to stop. A mailbox at such a service is set up
 * as it is added and taken down as it is removed.
 */
interface SetsUpMailboxes
{
    /**
     * Has the service hand an address's mail over from now on. Doing it
     * again for an address that is already set up changes nothing.
     *
     * @throws CouldNotReachConnector whose message says what to put right
     */
    public function setUpMailbox(Connection $connection, string $address): void;

    /**
     * Has the service stop handing an address's mail over. It leaves alone
     * what it did not set up.
     *
     * @throws CouldNotReachConnector
     */
    public function tearDownMailbox(Connection $connection, string $address): void;
}
