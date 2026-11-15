<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceRelationship;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\InvoiceTotals;
use App\Invoice\Properties\CustomerId;
use App\Invoice\Properties\Id;
use App\Invoice\Properties\Number;
use App\Invoice\Properties\Status;
use App\Invoice\Properties\TeamId;
use App\InvoiceItem\InvoiceItem;
use App\InvoiceItem\InvoiceItemCollection;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\Properties\InvoiceId;
use App\InvoiceItem\Properties\Position;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindInvoice
{
    public static function byIdForTeam(?string $id, ?string $teamId): Invoice
    {
        if ($id === null || $teamId === null) {
            return Invoice::new();
        }

        return Invoice::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * Invoices newest first, optionally with one status or for one customer.
     *
     * @return LengthAwarePaginator<int, Invoice>
     */
    public static function paginateForTeam(string $teamId, ?InvoiceStatusEnum $status = null, ?string $search = null, ?string $customerId = null, int $perPage = 25): LengthAwarePaginator
    {
        return self::query($teamId)
            ->with([InvoiceRelationship::Customer->value, InvoiceRelationship::Items->value])
            ->when($status, fn (Builder $query, InvoiceStatusEnum $status) => $query->where(Status::getDatabaseTableColumnName(), $status->value))
            ->when($customerId, fn (Builder $query, string $customerId) => $query->where(CustomerId::getDatabaseTableColumnName(), $customerId))
            ->when($search, fn (Builder $query, string $search) => $query->where(Number::getDatabaseTableColumnName(), (int) preg_replace('/\D/', '', $search)))
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (InvoiceEloquentModel $model): Invoice => Invoice::fromEloquentModel($model));
    }

    public static function itemsFor(Invoice $invoice): InvoiceItemCollection
    {
        $items = InvoiceItemEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(InvoiceId::getDatabaseTableColumnName(), $invoice->getId()->toString())
            ->orderBy(Position::getDatabaseTableColumnName())
            ->get();

        return InvoiceItem::collect($items->all(), 'fromEloquentModel');
    }

    /**
     * An invoice with its totals and customer, for lists. Expects the invoice
     * to have been found with its items and customer loaded.
     *
     * @return array<string, mixed>
     */
    public static function listItem(Invoice $invoice): array
    {
        return [
            ...$invoice->toSummary(),
            ...InvoiceTotals::fromItems($invoice->getItems()->toDataCollection(), $invoice->getTaxRateBasisPoints()->toInteger())->toArray(),
            'customer_name' => $invoice->getCustomer()->toNullableData()?->getName()->toString(),
        ];
    }

    /**
     * @return Builder<InvoiceEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return InvoiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
