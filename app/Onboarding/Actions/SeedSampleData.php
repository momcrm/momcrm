<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Asset\Actions\CreateAsset;
use App\Asset\Asset;
use App\Asset\AssetStatusEnum;
use App\Contact\Actions\CreateContact;
use App\Contact\Contact;
use App\Customer\Actions\CreateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerStatusEnum;
use App\Invoice\Actions\ChangeInvoiceStatus;
use App\Invoice\Actions\CreateInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Location\Actions\CreateLocation;
use App\Location\Location;
use App\Service\Actions\CreateService;
use App\Service\Service;
use App\Service\ServiceStatusEnum;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketPriorityEnum;
use App\Ticket\TicketStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Fills a team with a few example customers, with contacts, locations, assets,
 * services, tickets and an invoice, so a new user has something to click around.
 *
 * Every sample customer is marked as sample; removing them removes the rest.
 */
final readonly class SeedSampleData
{
    /**
     * @var array<int, array{name: string, email: string, phone: string, location: array{name: string, suburb: string, postcode: string}, contacts: array<int, array{first: string, last: string, position: string, email: string}>, assets: array<int, array{name: string, serial: string}>, service: array{name: string, quantity: int, cents: int}, tickets: array<int, array{title: string, status: TicketStatusEnum, priority: TicketPriorityEnum}>}>
     */
    private const array CUSTOMERS = [
        [
            'name' => 'Harbour Dental',
            'email' => 'reception@harbourdental.example',
            'phone' => '08 9430 1100',
            'location' => ['name' => 'Fremantle clinic', 'suburb' => 'Fremantle', 'postcode' => '6160'],
            'contacts' => [
                ['first' => 'Priya', 'last' => 'Shah', 'position' => 'Practice manager', 'email' => 'priya@harbourdental.example'],
                ['first' => 'Tom', 'last' => 'Nguyen', 'position' => 'Dentist', 'email' => 'tom@harbourdental.example'],
            ],
            'assets' => [
                ['name' => 'Reception PC', 'serial' => 'HD-PC-0142'],
                ['name' => 'X-ray workstation', 'serial' => 'HD-XR-0007'],
            ],
            'service' => ['name' => 'Managed IT support', 'quantity' => 6, 'cents' => 4500],
            'tickets' => [
                ['title' => 'Reception printer offline', 'status' => TicketStatusEnum::InProgress, 'priority' => TicketPriorityEnum::High],
                ['title' => 'Laptop for new starter', 'status' => TicketStatusEnum::New, 'priority' => TicketPriorityEnum::Normal],
            ],
        ],
        [
            'name' => 'Coastal Logistics',
            'email' => 'ops@coastallogistics.example',
            'phone' => '08 9350 2200',
            'location' => ['name' => 'Welshpool depot', 'suburb' => 'Welshpool', 'postcode' => '6106'],
            'contacts' => [
                ['first' => 'Mark', 'last' => 'Ellis', 'position' => 'Operations manager', 'email' => 'mark@coastallogistics.example'],
            ],
            'assets' => [
                ['name' => 'Office firewall', 'serial' => 'CL-FW-2210'],
            ],
            'service' => ['name' => 'Microsoft 365 Business Standard', 'quantity' => 12, 'cents' => 1890],
            'tickets' => [
                ['title' => 'VPN drops every afternoon', 'status' => TicketStatusEnum::WaitingOnCustomer, 'priority' => TicketPriorityEnum::Urgent],
            ],
        ],
        [
            'name' => 'Bright Spark Electrical',
            'email' => 'hello@brightspark.example',
            'phone' => '0400 123 456',
            'location' => ['name' => 'Head office', 'suburb' => 'Osborne Park', 'postcode' => '6017'],
            'contacts' => [
                ['first' => 'Lena', 'last' => 'Brooks', 'position' => 'Owner', 'email' => 'lena@brightspark.example'],
            ],
            'assets' => [],
            'service' => ['name' => 'Cloud backup', 'quantity' => 1, 'cents' => 2500],
            'tickets' => [
                ['title' => "Reset Lena's email password", 'status' => TicketStatusEnum::Closed, 'priority' => TicketPriorityEnum::Low],
            ],
        ],
    ];

    public function __construct(
        private string $teamId,
        private string $userId,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            foreach (self::CUSTOMERS as $index => $sample) {
                $this->seedCustomer($sample, sendsInvoice: $index === 0);
            }

            RecordActivity::for(type: ActivityTypeEnum::SampleDataAdded, userId: $this->userId, teamId: $this->teamId);
        });
    }

    public static function forTeam(string $teamId, string $userId): void
    {
        new self($teamId, $userId)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }

    /**
     * @param  array{name: string, email: string, phone: string, location: array{name: string, suburb: string, postcode: string}, contacts: array<int, array{first: string, last: string, position: string, email: string}>, assets: array<int, array{name: string, serial: string}>, service: array{name: string, quantity: int, cents: int}, tickets: array<int, array{title: string, status: TicketStatusEnum, priority: TicketPriorityEnum}>}  $sample
     */
    private function seedCustomer(array $sample, bool $sendsInvoice): void
    {
        $customer = CreateCustomer::fromCustomer(Customer::new()
            ->setTeamId($this->teamId)
            ->setName($sample['name'])
            ->setEmail($sample['email'])
            ->setPhone($sample['phone'])
            ->setStatus(CustomerStatusEnum::Active)
            ->setIsSample(true));

        $location = CreateLocation::fromLocation(Location::new()
            ->setTeamId($this->teamId)
            ->setCustomerId($customer->getId())
            ->setName($sample['location']['name'])
            ->setSuburb($sample['location']['suburb'])
            ->setState('WA')
            ->setPostcode($sample['location']['postcode'])
            ->setCountry('Australia')
            ->setIsPrimary(true));

        $contacts = [];

        foreach ($sample['contacts'] as $index => $person) {
            $contacts[] = CreateContact::fromContact(Contact::new()
                ->setTeamId($this->teamId)
                ->setCustomerId($customer->getId())
                ->setFirstName($person['first'])
                ->setLastName($person['last'])
                ->setPosition($person['position'])
                ->setEmail($person['email'])
                ->setIsPrimary($index === 0));
        }

        foreach ($sample['assets'] as $asset) {
            CreateAsset::fromAsset(Asset::new()
                ->setTeamId($this->teamId)
                ->setCustomerId($customer->getId())
                ->setLocationId($location->getId())
                ->setName($asset['name'])
                ->setSerialNumber($asset['serial'])
                ->setStatus(AssetStatusEnum::Active));
        }

        $service = CreateService::fromService(Service::new()
            ->setTeamId($this->teamId)
            ->setCustomerId($customer->getId())
            ->setLocationId($location->getId())
            ->setName($sample['service']['name'])
            ->setQuantity($sample['service']['quantity'])
            ->setUnitPriceInCents($sample['service']['cents'])
            ->setBillingPeriodInMonths(1)
            ->setStartsOn(CarbonImmutable::today()->startOfMonth())
            ->setStatus(ServiceStatusEnum::Active));

        foreach ($sample['tickets'] as $sampleTicket) {
            $ticket = CreateTicket::fromTicket(Ticket::new()
                ->setTeamId($this->teamId)
                ->setCustomerId($customer->getId())
                ->setContactId($contacts[0]->getId())
                ->setLocationId($location->getId())
                ->setAssigneeId($this->userId)
                ->setTitle($sampleTicket['title'])
                ->setPriority($sampleTicket['priority']), openedById: $this->userId);

            if ($sampleTicket['status'] !== TicketStatusEnum::New) {
                UpdateTicket::fromTicket($ticket->setStatus($sampleTicket['status']), updatedById: $this->userId);
            }
        }

        if ($sendsInvoice) {
            $invoice = CreateInvoice::fromInvoice(
                Invoice::new()
                    ->setTeamId($this->teamId)
                    ->setCustomerId($customer->getId())
                    ->setIssuedOn(CarbonImmutable::today())
                    ->setDueOn(CarbonImmutable::today()->addDays(14))
                    ->setTaxRateBasisPoints(1000),
                [[
                    'description' => $sample['service']['name'],
                    'quantity' => $sample['service']['quantity'],
                    'unit_price_in_cents' => $sample['service']['cents'],
                    'service_id' => $service->getId()->toString(),
                ]],
            );

            ChangeInvoiceStatus::to($invoice, InvoiceStatusEnum::Sent);
        }
    }
}
