<?php

declare(strict_types=1);

namespace App\Invoice\Properties;

use App\InvoiceItem\InvoiceItem;
use App\InvoiceItem\InvoiceItemEloquentModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Mom\Data\AbstractInteger;

/**
 * The sum of the line items, before tax. Read from the eager-loaded items
 * when they are loaded; not a column.
 */
final class ItemsSubtotalInCents extends AbstractInteger
{
    public static bool $hasDBColumn = false;

    public static function getName(): string
    {
        return 'items_subtotal_in_cents';
    }

    public static function fromEloquentModel(Model $model): static
    {
        $items = $model->relationLoaded('items') ? $model->getRelation('items') : null;

        return new self(value: $items instanceof Collection
            ? $items->sum(fn (Model $item): int => $item instanceof InvoiceItemEloquentModel ? InvoiceItem::fromEloquentModel($item)->getLineTotalInCents() : 0)
            : null)->setData($model);
    }
}
