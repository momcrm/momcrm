<?php

declare(strict_types=1);

namespace App\Charge\Actions;

use App\Charge\Charge;
use App\Charge\ChargeCollection;
use App\Charge\ChargeEloquentModel;
use App\Charge\Properties\ChargedOn;
use App\Charge\Properties\Currency;
use App\Charge\Properties\CustomerId;
use App\Charge\Properties\Id;
use App\Charge\Properties\TeamId;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerEloquentModel;
use App\Customer\CustomerStatusEnum;
use App\Customer\Properties\BillingType as CustomerBillingType;
use App\Customer\Properties\Id as CustomerKey;
use App\Customer\Properties\Status as CustomerStatus;
use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Properties\Status;
use App\InvoiceItem\Properties\ChargeId as ItemChargeId;
use App\InvoiceItem\Properties\InvoiceId as ItemInvoiceId;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use App\Shared\Types;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class FindCharge
{
    public static function byIdForTeam(?string $id, ?string $teamId): Charge
    {
        if ($id === null || $teamId === null) {
            return Charge::new();
        }

        return Charge::fromEloquentModel(self::query($teamId)->where(Id::getDatabaseTableColumnName(), $id)->first());
    }

    /**
     * A customer's charges, newest first.
     */
    public static function forCustomer(string $customerId, string $teamId): ChargeCollection
    {
        return Charge::collect(
            self::query($teamId)
                ->where(CustomerId::getDatabaseTableColumnName(), $customerId)
                ->orderByDesc(ChargedOn::getDatabaseTableColumnName())
                ->orderByDesc(Id::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * The charges of the customers given that aren't on an invoice yet, in
     * the currency given, oldest first, keyed by customer. Only charges made
     * by the day given, when given.
     *
     * @param  list<string>  $customerIds
     * @return array<string, list<Charge>>
     */
    public static function unbilledForCustomers(array $customerIds, CurrencyEnum $currency, ?CarbonImmutable $by = null): array
    {
        if ($customerIds === []) {
            return [];
        }

        $charges = Charge::collect(
            ChargeEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereIn(CustomerId::getDatabaseTableColumnName(), $customerIds)
                ->where(Currency::getDatabaseTableColumnName(), $currency->value)
                ->when($by !== null, fn (Builder $query) => $query->whereDate(ChargedOn::getDatabaseTableColumnName(), '<=', (string) $by?->toDateString()))
                ->orderBy(ChargedOn::getDatabaseTableColumnName())
                ->orderBy(Id::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );

        $billed = self::billedInvoices(array_values($charges->map(fn (Charge $charge): string => $charge->getId()->toString())->all()));
        $unbilled = [];

        foreach ($charges as $charge) {
            if (! isset($billed[$charge->getId()->toString()])) {
                $unbilled[$charge->getCustomerId()->toString()][] = $charge;
            }
        }

        return $unbilled;
    }

    /**
     * The invoice each charge is on, for those on one that isn't voided,
     * keyed by charge id.
     *
     * @param  list<string>  $chargeIds
     * @return array<string, array{id: string, reference: string}>
     */
    public static function billedInvoices(array $chargeIds): array
    {
        if ($chargeIds === []) {
            return [];
        }

        $items = Table::InvoiceItems->value;
        $invoices = Table::Invoices->value;

        $invoiceByCharge = DB::table($items)
            ->join($invoices, "{$invoices}.id", '=', ItemInvoiceId::getDatabaseTableColumnNameWithTable($items))
            ->whereIn(ItemChargeId::getDatabaseTableColumnNameWithTable($items), $chargeIds)
            ->where(Status::getDatabaseTableColumnNameWithTable($invoices), '!=', InvoiceStatusEnum::Void->value)
            ->pluck(ItemInvoiceId::getDatabaseTableColumnNameWithTable($items), ItemChargeId::getDatabaseTableColumnNameWithTable($items))
            ->mapWithKeys(fn (mixed $invoiceId, mixed $chargeId): array => [Types::string($chargeId) => Types::string($invoiceId)])
            ->all();

        $references = InvoiceEloquentModel::query()
            ->withoutGlobalScopes()
            ->whereKey(array_values(array_unique($invoiceByCharge)))
            ->get()
            ->mapWithKeys(fn (InvoiceEloquentModel $model): array => [$model->id => Invoice::fromEloquentModel($model)->getReference()])
            ->all();

        return array_map(fn (string $invoiceId): array => ['id' => $invoiceId, 'reference' => $references[$invoiceId] ?? ''], $invoiceByCharge);
    }

    /**
     * The customers, with their team, who have charges made by the day given
     * that may not be billed yet: active ones billed automatically or by
     * hand, of one team when given. Billed charges are weeded out later.
     *
     * @return array<string, string> team id, keyed by customer id
     */
    public static function customersWithChargesBy(CarbonImmutable $day, ?string $teamId = null): array
    {
        return ChargeEloquentModel::query()
            ->withoutGlobalScopes()
            ->when($teamId, fn (Builder $query, string $team) => $query->where(TeamId::getDatabaseTableColumnName(), $team))
            ->whereDate(ChargedOn::getDatabaseTableColumnName(), '<=', $day->toDateString())
            ->whereIn(CustomerId::getDatabaseTableColumnName(), CustomerEloquentModel::query()
                ->withoutGlobalScopes()
                ->select(CustomerKey::getDatabaseTableColumnName())
                ->where(CustomerStatus::getDatabaseTableColumnName(), CustomerStatusEnum::Active->value)
                ->whereIn(CustomerBillingType::getDatabaseTableColumnName(), [CustomerBillingTypeEnum::Automatic->value, CustomerBillingTypeEnum::Manual->value]))
            ->distinct()
            ->pluck(TeamId::getDatabaseTableColumnName(), CustomerId::getDatabaseTableColumnName())
            ->mapWithKeys(fn (mixed $team, mixed $customer): array => [Types::string($customer) => Types::string($team)])
            ->all();
    }

    /**
     * @return Builder<ChargeEloquentModel>
     */
    private static function query(string $teamId): Builder
    {
        return ChargeEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId);
    }
}
