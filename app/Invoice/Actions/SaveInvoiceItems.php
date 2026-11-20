<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\InvoiceItem\InvoiceItem;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\Properties\Id;
use App\InvoiceItem\Properties\InvoiceId;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Replaces an invoice's line items with the given ones, in order.
 */
final readonly class SaveInvoiceItems
{
    /**
     * @param  array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id?: ?string, charge_id?: ?string, rental_starts_on?: ?string, rental_ends_on?: ?string}>  $items
     */
    public function __construct(
        private Invoice $invoice,
        private array $items,
    ) {}

    public function __invoke(): void
    {
        InvoiceItemEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(InvoiceId::getDatabaseTableColumnName(), $this->invoice->getId()->toString())
            ->get()
            ->each(fn (InvoiceItemEloquentModel $item): ?bool => $item->delete());

        foreach (array_values($this->items) as $position => $item) {
            $invoiceItem = InvoiceItem::new()
                ->setId(Id::generate())
                ->setTeamId($this->invoice->getTeamId())
                ->setInvoiceId($this->invoice->getId())
                ->setServiceId($item['service_id'] ?? null)
                ->setChargeId($item['charge_id'] ?? null)
                ->setPosition($position)
                ->setDescription($item['description'])
                ->setQuantity($item['quantity'])
                ->setUnitPriceInCents($item['unit_price_in_cents'])
                ->setRentalStartsOn($item['rental_starts_on'] ?? null)
                ->setRentalEndsOn($item['rental_ends_on'] ?? null);

            InvoiceItemEloquentModel::query()->create($invoiceItem->forDatabaseCreate());
        }
    }

    /**
     * @param  array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id?: ?string, charge_id?: ?string, rental_starts_on?: ?string, rental_ends_on?: ?string}>  $items
     */
    public static function forInvoice(Invoice $invoice, array $items): void
    {
        new self($invoice, $items)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
