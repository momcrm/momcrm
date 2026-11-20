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
use App\Team\Scopes\CurrentTeamScope;

/**
 * What the invoice form needs: customers with the currency they are billed
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
        $currencies = FindCustomer::currenciesByIds(array_values($customers->map(fn (array $customer): string => $customer['id'])->all()));

        return [
            'customers' => $customers
                ->map(fn (array $customer): array => [...$customer, 'currency' => ($currencies[$customer['id']] ?? CurrencyEnum::default())->value])
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
