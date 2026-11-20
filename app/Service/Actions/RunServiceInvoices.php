<?php

declare(strict_types=1);

namespace App\Service\Actions;

use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use App\Customer\Properties\BillingType as CustomerBillingType;
use App\Customer\Properties\Status as CustomerStatus;
use App\Invoice\Actions\CreateInvoice;
use App\Invoice\Controllers\CreateInvoiceController;
use App\Invoice\Invoice;
use App\InvoiceItem\Actions\FindInvoiceItem;
use App\Rate\Actions\FindRate;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\CustomerId;
use App\Service\Properties\Name;
use App\Service\Properties\RateId;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\Service;
use App\Service\ServiceBillingPeriod;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceRelationship;
use App\Service\ServiceStatusEnum;
use App\Shared\CurrencyEnum;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Drafts the month's invoices from services, as Amphora's monthly invoice
 * run does: one draft per billable customer, in their currency, with a line
 * for each period of each active service that has started by the day of the
 * run and isn't on an invoice yet. A service whose rate has no price in the
 * customer's currency is left out. Running it again makes nothing new, since
 * a period already on an invoice that wasn't voided counts as billed.
 */
final readonly class RunServiceInvoices
{
    public const int TAX_RATE_BASIS_POINTS = 1000;

    public function __construct(
        private CarbonImmutable $day,
        private ?string $teamId = null,
    ) {}

    /**
     * @return int How many invoices were drafted.
     */
    public function __invoke(): int
    {
        $services = Service::collect($this->billableServices()->get()->all(), 'fromEloquentModel');
        $rates = FindRate::byIds(array_values(array_unique($services->map(fn (Service $service): string => $service->getRateId()->toString())->all())));
        $billedThrough = FindInvoiceItem::billedThroughForServices(array_values($services->map(fn (Service $service): string => $service->getId()->toString())->all()));
        $customers = self::byCustomer($services->all());
        $currencies = FindCustomer::currenciesByIds(array_map(strval(...), array_keys($customers)));
        $drafted = 0;

        foreach ($customers as $customerId => $customerServices) {
            $currency = $currencies[$customerId] ?? CurrencyEnum::default();
            $items = [];

            foreach ($customerServices as $service) {
                $rate = $rates[$service->getRateId()->toString()] ?? null;
                $price = $rate?->priceIn($currency);

                if ($rate === null || $price === null) {
                    continue;
                }

                foreach (ServiceBillingPeriod::dueBy($service, $rate, $price, $billedThrough[$service->getId()->toString()] ?? null, $this->day) as $period) {
                    $items[] = [
                        'description' => self::describe($service, $period),
                        'quantity' => $service->getQuantity()->toNullableInteger() ?? 1,
                        'unit_price_in_cents' => $period->unitPriceInCents,
                        'service_id' => $service->getId()->toString(),
                        'rental_starts_on' => $period->startsOn->toDateString(),
                        'rental_ends_on' => $period->endsOn->toDateString(),
                    ];
                }
            }

            if ($items === []) {
                continue;
            }

            $first = $customerServices[0];

            DB::transaction(fn (): Invoice => CreateInvoice::fromInvoice(
                Invoice::new()
                    ->setTeamId($first->getTeamId())
                    ->setCustomerId($first->getCustomerId())
                    ->setCurrency($currency->value)
                    ->setIssuedOn($this->day)
                    ->setDueOn($this->day->addDays(CreateInvoiceController::DEFAULT_DAYS_TO_PAY))
                    ->setTaxRateBasisPoints(self::TAX_RATE_BASIS_POINTS),
                $items,
            ));

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
     * The services grouped by customer, each group in name order.
     *
     * @param  array<int, Service>  $services
     * @return array<string, non-empty-list<Service>>
     */
    private static function byCustomer(array $services): array
    {
        $groups = [];

        foreach ($services as $service) {
            $groups[$service->getCustomerId()->toString()][] = $service;
        }

        return $groups;
    }

    /**
     * "Backup · 1 Dec 2026 – 31 Dec 2026".
     */
    private static function describe(Service $service, ServiceBillingPeriod $period): string
    {
        return __(':name · :from – :to', [
            'name' => $service->getName()->toString(),
            'from' => $period->startsOn->format('j M Y'),
            'to' => $period->endsOn->format('j M Y'),
        ]);
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
            ->orderBy(CustomerId::getDatabaseTableColumnNameWithTable($services))
            ->orderBy(Name::getDatabaseTableColumnNameWithTable($services));
    }
}
