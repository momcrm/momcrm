<?php

declare(strict_types=1);

namespace App\MailboxMessage\Controllers;

use App\MailboxMessage\Actions\FindMailboxMessage;
use App\MailboxMessage\EmailHtml;
use App\MailboxMessage\Exceptions\CouldNotFindMailboxMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * An email on a ticket as its sender laid it out, for the frame the ticket
 * shows it in. Only people who can see the ticket get it.
 *
 * The sender wrote this page, so it is cleaned and served boxed in: it runs
 * nothing, cannot reach the app even when opened on its own, and loads
 * nothing from elsewhere. Pictures from the web load only with `?pictures=1`,
 * because fetching one tells the sender their email was opened.
 */
final class ShowOriginalEmailController
{
    /**
     * @throws CouldNotFindMailboxMessage
     */
    public function __invoke(Request $request, string $message): Response
    {
        $html = FindMailboxMessage::htmlVisibleOnTicket($message, $request->user()?->current_team_id, (string) $request->user()?->id);

        if ($html === null) {
            throw CouldNotFindMailboxMessage::notFound();
        }

        return response(EmailHtml::toPage($html), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => EmailHtml::policy($request->boolean('pictures')),
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
