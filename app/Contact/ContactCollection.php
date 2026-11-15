<?php

declare(strict_types=1);

namespace App\Contact;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Contact>
 */
final class ContactCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Contact::class;
    }
}
