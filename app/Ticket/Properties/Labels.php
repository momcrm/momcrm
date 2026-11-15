<?php

declare(strict_types=1);

namespace App\Ticket\Properties;

use App\Label\LabelCollection;
use App\Shared\Abstracts\AbstractRelatedDataCollection;

/**
 * The ticket's labels, when loaded.
 */
final class Labels extends AbstractRelatedDataCollection
{
    public static function getName(): string
    {
        return 'labels';
    }

    public static function collectionClass(): string
    {
        return LabelCollection::class;
    }

    public function toDataCollection(): LabelCollection
    {
        $collection = parent::toDataCollection();

        return $collection instanceof LabelCollection ? $collection : new LabelCollection;
    }
}
