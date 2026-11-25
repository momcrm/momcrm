<?php

declare(strict_types=1);

namespace App\Inbox;

use App\Shared\BrandedMailMessage;
use App\Shared\Types;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The email copy of an inbox notification: who did what, to which ticket or
 * page, with the comment it came with and a link to open it.
 */
final class InboxMail
{
    /**
     * @param  array<string, mixed>  $data  the notification's inbox payload
     */
    public static function fromPayload(array $data): MailMessage
    {
        $title = Types::string($data['title'] ?? '');
        $message = Types::string($data['message'] ?? '');
        $actorName = Types::nullableString($data['actor_name'] ?? null);
        $excerpt = Types::nullableString($data['excerpt'] ?? null);
        $isNumbered = in_array($data['subject_type'] ?? null, ['ticket', 'record'], true);
        $subject = $isNumbered ? __('[#:number] :title', ['number' => Types::intOrZero($data['number'] ?? 0), 'title' => $title]) : $title;
        $what = $actorName === null ? mb_ucfirst($message) : "{$actorName} {$message}";

        $mail = new BrandedMailMessage()
            ->subject($actorName === null && ! $isNumbered ? $title : "{$what}: {$subject}")
            ->greeting(self::plain($actorName === null && ! $isNumbered ? $title : $what));

        if ($actorName === null && ! $isNumbered) {
            $mail->line(self::plain($what));
        } else {
            $mail->line(implode(' · ', array_filter([
                $isNumbered ? '#'.Types::intOrZero($data['number'] ?? 0).' '.self::plain($title) : self::plain($title),
                self::plain(Types::string($data['project_name'] ?? '')),
            ])));
        }

        if ($excerpt !== null && $excerpt !== '') {
            $mail->line(self::plain($excerpt));
        }

        return $mail
            ->action(__('Open in :app', ['app' => config()->string('app.name')]), InboxLink::for($data))
            ->line(__('Choose which notifications you are emailed in [your settings](:url).', ['url' => route('notification-settings.edit')]));
    }

    /**
     * Text someone typed, shown as written: its Markdown characters are
     * escaped so it can't add links or headings to the email.
     */
    public static function plain(string $text): string
    {
        $escaped = (string) preg_replace('/[\\\\`*_\[\]!<>]/', '\\\\$0', $text);

        return (string) preg_replace('/^(\s*)#/m', '$1\\\\#', $escaped);
    }
}
