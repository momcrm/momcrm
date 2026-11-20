<?php

declare(strict_types=1);

namespace App\Invoice;

use App\Customer\Actions\FindCustomer;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\Name;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\Service;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceStatusEnum;
use App\Shared\CurrencyEnum;
use App\Team\Actions\FindTeam;
use App\Team\Properties\DaysToPay;
use App\Team\Scopes\CurrentTeamScope;

/**
 * What the invoice form needs: customers with the currency they are billed
 * in and the days they have to pay,
 * in, and each customer's active services so a line item can be filled from
 * one, priced for one billing period in that currency.
 */
final class InvoiceFormOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function forTeam(string $teamId): array
    {
        $customers = FindCustomer::optionsForTeam($teamId);
        $ids = array_values($customers->map(fn (array $customer): string => $customer['id'])->all());
        $currencies = FindCustomer::currenciesByIds($ids);
        $daysToPay = FindCustomer::daysToPayByIds($ids);
        $teamDaysToPay = FindTeam::byId($teamId)->getDaysToPay()->toNullableInteger() ?? DaysToPay::DEFAULT;

        return [
            'customers' => $customers
                ->map(fn (array $customer): array => [
                    ...$customer,
                    'currency' => ($currencies[$customer['id']] ?? CurrencyEnum::default())->value,
                    'days_to_pay' => $daysToPay[$customer['id']] ?? $teamDaysToPay,
                ])
                ->values()
                ->all(),
            'services' => Service::collect(
                ServiceEloquentModel::query()
                    ->withoutGlobalScope(CurrentTeamScope::class)
                    ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                    ->where(Status::getDatabaseTableColumnName(), ServiceStatusEnum::Active->value)
                    ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                    ->orderBy(Name::getDatabaseTableColumnName())
                    ->get()
                    ->all(),
                'fromEloquentModel',
            )->toSummaries(),
        ];
    }
}
