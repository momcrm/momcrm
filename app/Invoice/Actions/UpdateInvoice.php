<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Saves an invoice. With line items, they replace the invoice's current ones.
 */
final readonly class UpdateInvoice
{
    /**
     * @param  array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id?: ?string, rental_starts_on?: ?string, rental_ends_on?: ?string}>|null  $items
     */
    public function __construct(
        private Invoice $invoice,
        private ?array $items = null,
    ) {}

    public function __invoke(): Invoice
    {
        return DB::transaction(function (): Invoice {
            InvoiceEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($this->invoice->getId()->toString())
                ?->update($this->invoice->forDatabaseUpdate());

            if ($this->items !== null) {
                SaveInvoiceItems::forInvoice($this->invoice, $this->items);
            }

            return $this->invoice;
        });
    }

    /**
     * @param  array<int, array{description: string, quantity: int, unit_price_in_cents: int, service_id?: ?string, rental_starts_on?: ?string, rental_ends_on?: ?string}>|null  $items
     */
    public static function fromInvoice(Invoice $invoice, ?array $items = null): Invoice
    {
        return new self($invoice, $items)->handle();
    }

    public function handle(): Invoice
    {
        return $this->__invoke();
    }

    public function execute(): Invoice
    {
        return $this->__invoke();
    }
}
