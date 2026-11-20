<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Customer\Properties\Name as CustomerName;
use App\Payment\Payment;
use App\Payment\PaymentCollection;
use App\Payment\PaymentEloquentModel;
use App\Payment\PaymentFilters;
use App\Payment\PaymentRelationship;
use App\Payment\Properties\AllocatedInCents;
use App\Payment\Properties\CustomerId;
use App\Payment\Properties\Id;
use App\Payment\Properties\Method;
use App\Payment\Properties\Notes;
use App\Payment\Properties\Number;
use App\Payment\Properties\PaidOn;
use App\Payment\Properties\Reference;
use App\Payment\Properties\TeamId;
use App\PaymentAllocation\PaymentAllocation;
use App\PaymentAllocation\PaymentAllocationCollection;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\PaymentAllocation\PaymentAllocationRelationship;
use App\PaymentAllocation\Properties\AmountInCents as AllocationAmountInCents;
use App\PaymentAllocation\Properties\CreatedAt as AllocationCreatedAt;
use App\PaymentAllocation\Properties\InvoiceId as AllocationInvoiceId;
use App\PaymentAllocation\Properties\PaymentId as AllocationPaymentId;
use App\PaymentAllocation\Properties\TeamId as AllocationTeamId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class FindPayment
{
    /**
     * The columns a payments list can be sorted by.
     */
    public const array SORTS = [
        'number' => 'number',
        'paid_on' => 'paid_on',
        'amount_in_cents' => 'amount_in_cents',
    ];

    public static function byIdForTeam(?string $id, ?string $teamId): Payment
    {
        if ($id === null || $teamId === null) {
            return Payment::new();
        }

        return Payment::fromEloquentModel(self::query($teamId)
            ->with(PaymentRelationship::Customer->value)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->first());
    }

    /**
     * A page of the team's payments, with their customer, filtered and
     * sorted as asked.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public static function paginateForTeam(string $teamId, PaymentFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        return self::filtered($teamId, $filters)
            ->paginate($perPage)
            ->withQueryString()
            // The open payment dialog (?payment=) stays on this page; a null
            // value leaves it out of the next and previous page links.
            ->appends('payment', null)
            ->through(fn (PaymentEloquentModel $model): Payment => Payment::fromEloquentModel($model));
    }

    /**
     * Every payment the filters match, for an export.
     */
    public static function allForTeam(string $teamId, PaymentFilters $filters): PaymentCollection
    {
        return Payment::collect(self::filtered($teamId, $filters)->get()->all(), 'fromEloquentModel');
    }

    /**
     * How many payments a customer has made.
     */
    public static function countForCustomer(string $customerId, string $teamId): int
    {
        return self::query($teamId)->where(CustomerId::getDatabaseTableColumnName(), $customerId)->count();
    }

    /**
     * The invoices a payment was applied to, in the order it was applied.
     */
    public static function allocationsFor(Payment $payment): PaymentAllocationCollection
    {
        return PaymentAllocation::collect(
            self::allocationQuery($payment->getTeamId()->toString())
                ->with(PaymentAllocationRelationship::Invoice->value)
                ->where(AllocationPaymentId::getDatabaseTableColumnName(), $payment->getId()->toString())
                ->orderBy(AllocationCreatedAt::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * The payments applied to an invoice, oldest first.
     */
    public static function allocationsForInvoice(string $invoiceId, string $teamId): PaymentAllocationCollection
    {
        return PaymentAllocation::collect(
            self::allocationQuery($teamId)
                ->with(PaymentAllocationRelationship::Payment->value)
                ->where(AllocationInvoiceId::getDatabaseTableColumnName(), $invoiceId)
                ->orderBy(AllocationCreatedAt::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * @return Builder<PaymentEloquentModel>
     */
    private static function filtered(string $teamId, PaymentFilters $filters): Builder
    {
        $search = $filters->search;

        return self::query($teamId)
            ->with(PaymentRelationship::Customer->value)
            ->when($filters->customerId, fn (Builder $query, string $customerId) => $query->where(CustomerId::getDatabaseTableColumnName(), $customerId))
            ->when($filters->method, fn (Builder $query, $method) => $query->where(Method::getDatabaseTableColumnName(), $method->value))
            ->when($filters->paidFrom, fn (Builder $query, string $from) => $query->whereDate(PaidOn::getDatabaseTableColumnName(), '>=', $from))
            ->when($filters->paidTo, fn (Builder $query, string $to) => $query->whereDate(PaidOn::getDatabaseTableColumnName(), '<=', $to))
            ->when($search, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $digits = preg_replace('/\D/', '', $search);

                $query->where(Reference::getDatabaseTableColumnName(), 'like', "%{$search}%")
                    ->orWhere(Notes::getDatabaseTableColumnName(), 'like', "%{$search}%")
                    ->orWhereHas(PaymentRelationship::Customer->value, fn (Builder $customer) => $customer
                        ->withoutGlobalScope(CurrentTeamScope::class)
                        ->where(CustomerName::getDatabaseTableColumnName(), 'like', "%{$search}%"));

                if ($digits !== '' && $digits !== null) {
                    $query->orWhere(Number::getDatabaseTableColumnName(), (int) $digits);
                }
            }))
            ->orderBy(self::SORTS[$filters->sort] ?? self::SORTS['paid_on'], $filters->direction)
            ->orderBy(Number::getDatabaseTableColumnName(), $filters->direction);
    }

    /**
     * A team's payments, each with how much of it has been applied to
     * invoices.
     *
     * @return Builder<PaymentEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return PaymentEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->withSum([
                PaymentRelationship::Allocations->value.' as '.AllocatedInCents::getDatabaseTableColumnName() => fn (Builder $query) => $query->withoutGlobalScope(CurrentTeamScope::class),
            ], AllocationAmountInCents::getDatabaseTableColumnName());
    }

    /**
     * @return Builder<PaymentAllocationEloquentModel>
     */
    private static function allocationQuery(string $teamId): Builder
    {
        return PaymentAllocationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(AllocationTeamId::getDatabaseTableColumnName(), $teamId);
    }
}
