<?php

declare(strict_types=1);

namespace App\Connector\Types\Imap;

use Mom\Connectors\CouldNotReachConnector;
use Symfony\Component\Mime\Email;

/**
 * The IMAP and SMTP servers of mailboxes that are connected by typing in
 * where they are. Everything that touches the network for them goes through
 * here, so tests can stand in for the servers.
 */
interface MailServers
{
    /**
     * Signs in to the IMAP server and opens the inbox.
     *
     * @throws CouldNotReachConnector that needs a reconnect when the server refuses the username and password
     */
    public function inbox(MailServerSettings $settings): ImapInbox;

    /**
     * Signs in to the SMTP server and leaves again, to find out whether mail
     * can be sent through it.
     *
     * @throws CouldNotReachConnector
     */
    public function checkSmtp(MailServerSettings $settings): void;

    /**
     * Sends an email through the SMTP server, once.
     *
     * @throws CouldNotReachConnector
     */
    public function send(MailServerSettings $settings, Email $email): void;
}
