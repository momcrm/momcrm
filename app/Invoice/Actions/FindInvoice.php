<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceCollection;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceRelationship;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Properties\CustomerId;
use App\Invoice\Properties\DueOn;
use App\Invoice\Properties\Id;
use App\Invoice\Properties\IssuedOn;
use App\Invoice\Properties\Number;
use App\Invoice\Properties\PaidInCents;
use App\Invoice\Properties\Status;
use App\Invoice\Properties\TeamId;
use App\InvoiceItem\InvoiceItem;
use App\InvoiceItem\InvoiceItemCollection;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\Properties\InvoiceId;
use App\InvoiceItem\Properties\Position;
use App\PaymentAllocation\Properties\AmountInCents as AllocationAmountInCents;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
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
            ->when($search, fn (Builder $query, string $search) => $query->where(Number::getDatabaseTableColumnName(), Invoice::numberIn($search) ?? 0))
            ->orderByDesc(Number::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            // The open invoice dialog (?invoice=) stays on this page; a null
            // value leaves it out of the next and previous page links.
            ->appends('invoice', null)
            ->through(fn (InvoiceEloquentModel $model): Invoice => Invoice::fromEloquentModel($model));
    }

    /**
     * What each customer owes on invoices that were sent and not yet paid, in
     * cents, after the payments applied to them: everything, what isn't due
     * yet (due today or later) and what is overdue. Customers who owe nothing
     * are left out.
     *
     * @param  array<int, string>|null  $customerIds  null for every customer
     * @return array<string, array{balance_in_cents: int, current_in_cents: int, overdue_in_cents: int}>
     */
    public static function balancesForCustomers(string $teamId, ?array $customerIds = null): array
    {
        $today = CarbonImmutable::today()->toDateString();
        $balances = [];

        self::query($teamId)
            ->with(InvoiceRelationship::Items->value)
            ->where(Status::getDatabaseTableColumnName(), InvoiceStatusEnum::Sent->value)
            ->when($customerIds !== null, fn (Builder $query) => $query->whereIn(CustomerId::getDatabaseTableColumnName(), $customerIds ?? []))
            ->get()
            ->each(function (InvoiceEloquentModel $model) use (&$balances, $today): void {
                $invoice = Invoice::fromEloquentModel($model);
                $total = $invoice->balanceAfter($invoice->totals());
                $customerId = $invoice->getCustomerId()->toString();
                $isOverdue = $invoice->getDueOn()->toCarbon()->toDateString() < $today;

                $balances[$customerId] ??= ['balance_in_cents' => 0, 'current_in_cents' => 0, 'overdue_in_cents' => 0];
                $balances[$customerId]['balance_in_cents'] += $total;
                $balances[$customerId][$isOverdue ? 'overdue_in_cents' : 'current_in_cents'] += $total;
            });

        return $balances;
    }

    /**
     * A customer's sent invoices that still have something owing, oldest due
     * first, as list items: the invoices a payment can be applied to.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function outstandingForCustomer(string $customerId, string $teamId): array
    {
        return self::query($teamId)
            ->with(InvoiceRelationship::Items->value)
            ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
            ->where(Status::getDatabaseTableColumnName(), InvoiceStatusEnum::Sent->value)
            ->orderBy(DueOn::getDatabaseTableColumnName())
            ->orderBy(Number::getDatabaseTableColumnName())
            ->get()
            ->map(fn (InvoiceEloquentModel $model): array => self::listItem(Invoice::fromEloquentModel($model)))
            ->filter(fn (array $item): bool => $item['balance_in_cents'] > 0)
            ->values()
            ->all();
    }

    /**
     * What a customer has been billed: their sent and paid invoices, with
     * their items, oldest issued first. Drafts and voided invoices are left
     * out.
     */
    public static function billedToCustomer(string $customerId, string $teamId): InvoiceCollection
    {
        return Invoice::collect(
            self::query($teamId)
                ->with(InvoiceRelationship::Items->value)
                ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
                ->whereIn(Status::getDatabaseTableColumnName(), [InvoiceStatusEnum::Sent->value, InvoiceStatusEnum::Paid->value])
                ->orderBy(IssuedOn::getDatabaseTableColumnName())
                ->orderBy(Number::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * Invoices of a team by id, with their items and what has been paid,
     * keyed by id.
     *
     * @param  array<int, string>  $ids
     * @return array<string, Invoice>
     */
    public static function byIdsForTeam(array $ids, string $teamId): array
    {
        if ($ids === []) {
            return [];
        }

        return self::query($teamId)
            ->with(InvoiceRelationship::Items->value)
            ->whereIn(Id::getDatabaseTableColumnName(), $ids)
            ->get()
            ->mapWithKeys(fn (InvoiceEloquentModel $model): array => [$model->id => Invoice::fromEloquentModel($model)])
            ->all();
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
        $totals = $invoice->totals();

        return [
            ...$invoice->toSummary(),
            ...$totals->toArray(),
            'balance_in_cents' => $invoice->balanceAfter($totals),
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
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->withSum([
                InvoiceRelationship::Allocations->value.' as '.PaidInCents::getDatabaseTableColumnName() => fn (Builder $query) => $query->withoutGlobalScope(CurrentTeamScope::class),
            ], AllocationAmountInCents::getDatabaseTableColumnName());
    }
}
