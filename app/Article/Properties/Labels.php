<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Choice\ChoiceCollection;
use App\Shared\Abstracts\AbstractRelatedDataCollection;

/**
 * The article's labels (its "label" choices), when loaded.
 */
final class Labels extends AbstractRelatedDataCollection
{
    public static function getName(): string
    {
        return 'labels';
    }

    public static function collectionClass(): string
    {
        return ChoiceCollection::class;
    }

    public function toDataCollection(): ChoiceCollection
    {
        $collection = parent::toDataCollection();

        return $collection instanceof ChoiceCollection ? $collection : new ChoiceCollection;
    }
}
