<?php

declare(strict_types=1);

namespace App\Shared;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A notification email that can say which team it comes from and show the
 * one thing it is about, such as a code or an amount owed, above the text.
 *
 * Without a sender the email carries the app's own logo and name.
 */
final class BrandedMailMessage extends MailMessage
{
    /**
     * Puts the team's name in the header, footer and default sign-off, for
     * emails a team sends its customers.
     */
    public function sentBy(string $name): static
    {
        $this->viewData['sender'] = $name;

        return $this;
    }

    /**
     * Shows a value large, under the greeting. A code is spaced out in a
     * fixed-width font so it is easy to read and type.
     */
    public function highlight(string $label, string $value, ?string $detail = null, bool $isCode = false): static
    {
        $this->viewData['highlight'] = [
            'label' => $label,
            'value' => $value,
            'detail' => $detail,
            'isCode' => $isCode,
        ];

        return $this;
    }
}
