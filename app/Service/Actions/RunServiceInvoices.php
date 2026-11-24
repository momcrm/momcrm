<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Charge\Actions\FindCharge;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use App\Customer\Properties\BillingType as CustomerBillingType;
use App\Customer\Properties\Status as CustomerStatus;
use App\Invoice\Actions\DraftCustomerInvoice;
use App\Invoice\Billing\CustomerBillingLines;
use App\Invoice\Invoice;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\CustomerId;
use App\Service\Properties\RateId;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceRelationship;
use App\Service\ServiceStatusEnum;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Drafts the month's invoices, as Amphora's monthly invoice run does: one
 * draft per billable customer (active, billed automatically or by hand) with
 * what they are due by the day of the run (CustomerBillingLines). Running it
 * again makes nothing new, since what is on an invoice that wasn't voided
 * counts as billed. Each draft is due by its customer's payment terms.
 */
final readonly class RunServiceInvoices
{
    public function __construct(
        private CarbonImmutable $day,
        private ?string $teamId = null,
    ) {}

    /**
     * @return int How many invoices were drafted.
     */
    public function __invoke(): int
    {
        $lines = CustomerBillingLines::forCustomers($this->billableCustomerIds(), $this->day);
        $customers = FindCustomer::byIdsForTeams(array_map(strval(...), array_keys($lines)));
        $teams = [];
        $drafted = 0;

        foreach ($lines as $customerId => $customerLines) {
            $customer = $customers[$customerId] ?? null;

            if ($customer === null) {
                continue;
            }

            $teamId = $customer->getTeamId()->toString();
            $team = $teams[$teamId] ??= FindTeam::byId($teamId);

            // A suspended team is read-only. What it is owed is drafted by the
            // first run after it is reactivated.
            if ($team->isSuspended()) {
                continue;
            }

            DB::transaction(fn (): Invoice => DraftCustomerInvoice::from($customer, $team, $customerLines, $this->day));

            $drafted++;
        }

        return $drafted;
    }

    /**
     * Drafts invoices for every team, or one team when given.
     */
    public static function on(CarbonImmutable $day, ?string $teamId = null): int
    {
        return new self($day->startOfDay(), $teamId)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }

    /**
     * The customers the run bills by its day, of its team when it has one:
     * active ones billed automatically or by hand, with a service that has
     * started billing or a one-off charge made by then.
     *
     * @return list<string>
     */
    public function billableCustomerIds(): array
    {
        $ids = Types::stringList($this->billableServices()
            ->distinct()
            ->pluck(CustomerId::getDatabaseTableColumnNameWithTable(Table::Services->value))
            ->all());

        return array_values(array_unique([...$ids, ...array_map(strval(...), array_keys(FindCharge::customersWithChargesBy($this->day, $this->teamId)))]));
    }

    /**
     * Active services on a rate, not archived, that have started billing, of
     * active customers billed automatically or by hand.
     *
     * @return Builder<ServiceEloquentModel>
     */
    private function billableServices(): Builder
    {
        $services = Table::Services->value;

        return ServiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->when($this->teamId, fn (Builder $query, string $teamId) => $query->where(TeamId::getDatabaseTableColumnNameWithTable($services), $teamId))
            ->where(Status::getDatabaseTableColumnNameWithTable($services), ServiceStatusEnum::Active->value)
            ->whereNull(ArchivedAt::getDatabaseTableColumnNameWithTable($services))
            ->whereNotNull(RateId::getDatabaseTableColumnNameWithTable($services))
            ->whereDate(BillingStartsOn::getDatabaseTableColumnNameWithTable($services), '<=', $this->day->toDateString())
            ->whereHas(ServiceRelationship::Customer->value, fn (Builder $query) => $query
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(CustomerStatus::getDatabaseTableColumnName(), CustomerStatusEnum::Active->value)
                ->whereIn(CustomerBillingType::getDatabaseTableColumnName(), [CustomerBillingTypeEnum::Automatic->value, CustomerBillingTypeEnum::Manual->value]))
            ->orderBy(CustomerId::getDatabaseTableColumnNameWithTable($services));
    }
}
