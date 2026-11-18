<?php

declare(strict_types=1);

namespace App\Connector\Types\Imap;

use App\Connector\Exceptions\CouldNotReachConnector;
use App\Connector\Sdk\PublicHost;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Email;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\IMAP;

/**
 * Real IMAP and SMTP servers: IMAP through webklex/php-imap, SMTP through
 * Symfony Mailer. Every connection checks the host first, because the host is
 * whatever someone typed in.
 */
final class WebklexMailServers implements MailServers
{
    private const int TIMEOUT_IN_SECONDS = 20;

    public function inbox(MailServerSettings $settings): ImapInbox
    {
        self::guard($settings->imapHost);

        try {
            $client = new ClientManager(['options' => ['fetch' => IMAP::FT_PEEK]])->make([
                'host' => $settings->imapHost,
                'port' => $settings->imapPort,
                'protocol' => 'imap',
                'encryption' => $settings->imapEncryption === MailServerSettings::ENCRYPTION_NONE ? false : $settings->imapEncryption,
                'validate_cert' => true,
                'username' => $settings->imapUsername,
                'password' => $settings->imapPassword,
                'timeout' => self::TIMEOUT_IN_SECONDS,
            ]);

            try {
                $client->connect();
            } catch (ConnectionFailedException $exception) {
                throw CouldNotReachConnector::unavailable(ImapConnector::COMPANY, $exception);
            } catch (AuthFailedException|ImapBadRequestException|ImapServerErrorException|ResponseException) {
                // The server was reached and answered the sign-in with a refusal.
                throw CouldNotReachConnector::needsReconnect(ImapConnector::COMPANY);
            }

            $status = (array) $client->getConnection()->examineFolder('INBOX')->validatedData();
            $uidValidity = $status['uidvalidity'] ?? 0;

            return new WebklexImapInbox($client, is_numeric($uidValidity) ? (int) $uidValidity : 0);
        } catch (Throwable $exception) {
            throw WebklexImapInbox::failure($exception);
        }
    }

    public function checkSmtp(MailServerSettings $settings): void
    {
        $transport = self::transport($settings);

        try {
            $transport->start();
            $transport->stop();
        } catch (TransportExceptionInterface $exception) {
            throw self::smtpFailure($exception);
        }
    }

    public function send(MailServerSettings $settings, Email $email): void
    {
        try {
            self::transport($settings)->send($email);
        } catch (TransportExceptionInterface $exception) {
            throw self::smtpFailure($exception);
        }
    }

    /**
     * @throws CouldNotReachConnector
     */
    private static function transport(MailServerSettings $settings): EsmtpTransport
    {
        self::guard($settings->smtpHost);

        $transport = new EsmtpTransport($settings->smtpHost, $settings->smtpPort, $settings->smtpEncryption === MailServerSettings::ENCRYPTION_SSL);

        match ($settings->smtpEncryption) {
            MailServerSettings::ENCRYPTION_STARTTLS => $transport->setRequireTls(true),
            MailServerSettings::ENCRYPTION_NONE => $transport->setAutoTls(false),
            default => null,
        };

        $stream = $transport->getStream();

        if ($stream instanceof SocketStream) {
            $stream->setTimeout(self::TIMEOUT_IN_SECONDS);
        }

        return $transport->setUsername($settings->smtpUsername)->setPassword($settings->smtpPassword);
    }

    /**
     * @throws CouldNotReachConnector
     */
    private static function guard(string $host): void
    {
        if (! PublicHost::allows($host)) {
            throw CouldNotReachConnector::refused((string) __(':host is not a mail server MomCRM may connect to.', ['host' => $host]));
        }
    }

    private static function smtpFailure(TransportExceptionInterface $exception): CouldNotReachConnector
    {
        return str_starts_with($exception->getMessage(), 'Failed to authenticate')
            ? CouldNotReachConnector::needsReconnect(ImapConnector::COMPANY)
            : CouldNotReachConnector::unavailable(ImapConnector::COMPANY, $exception);
    }
}
