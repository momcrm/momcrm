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
use App\Team\Scopes\CurrentTeamScope;

/**
 * What the invoice form needs: customers, and each customer's active services
 * so a line item can be filled from one, priced for one billing period.
 */
final class InvoiceFormOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function forTeam(string $teamId): array
    {
        return [
            'customers' => FindCustomer::optionsForTeam($teamId),
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
