<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Asset\Actions\CreateAsset;
use App\Asset\Asset;
use App\Asset\AssetStatusEnum;
use App\Choice\Actions\CreateChoice;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\Contact\Actions\CreateContact;
use App\Contact\Contact;
use App\Customer\Actions\CreateCustomer;
use App\Customer\Customer;
use App\Customer\CustomerStatusEnum;
use App\Group\Actions\CreateGroup;
use App\Group\Actions\FindGroup;
use App\Group\Group;
use App\Invoice\Actions\ChangeInvoiceStatus;
use App\Invoice\Actions\CreateInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Location\Actions\CreateLocation;
use App\Location\Location;
use App\Milestone\Actions\CreateMilestone;
use App\Milestone\Milestone;
use App\Project\Actions\CreateProject;
use App\Project\Project;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibilityEnum;
use App\Service\Actions\CreateService;
use App\Service\Service;
use App\Service\ServiceStatusEnum;
use App\Shared\HuePalette;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Actions\MoveTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Fills a team with a few example customers, with contacts, locations, assets,
 * services, tickets and an invoice, plus projects with milestones, labels and
 * a group, so a new user has something to click around.
 *
 * Sample customers and projects are marked as sample; removing them removes
 * their tickets and the rest. Labels and the group stay, as team settings.
 */
final readonly class SeedSampleData
{
    /**
     * @var array<int, array{name: string, email: string, phone: string, location: array{name: string, suburb: string, postcode: string}, contacts: array<int, array{first: string, last: string, position: string, email: string}>, assets: array<int, array{name: string, serial: string}>, service: array{name: string, quantity: int, cents: int}, tickets: array<int, array{title: string, status: string, priority: string, due?: int, labels?: array<int, string>, project?: string}>}>
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
                ['title' => 'Reception printer offline', 'status' => 'In progress', 'priority' => 'High', 'due' => -2, 'labels' => ['Hardware']],
                ['title' => 'Laptop for new starter', 'status' => 'New', 'priority' => 'Normal', 'due' => 5, 'labels' => ['Onboarding', 'Hardware'], 'project' => 'refit'],
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
                ['title' => 'VPN drops every afternoon', 'status' => 'Waiting on customer', 'priority' => 'Urgent', 'due' => 1, 'labels' => ['Network'], 'project' => 'vpn'],
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
                ['title' => "Reset Lena's email password", 'status' => 'Closed', 'priority' => 'Low', 'due' => -6],
            ],
        ],
    ];

    /**
     * Sample projects, keyed so tickets can point at them. A project's customer
     * is named; tickets listed here are internal (no customer).
     *
     * @var array<string, array{name: string, customer: ?string, status: ProjectStatusEnum, hue: int, icon: string, description: string, starts: int, due: int, milestones: array<int, array{name: string, due: int, done?: bool}>, tickets: array<int, array{title: string, status: string, priority: string, due: int, labels: array<int, string>}>}>
     */
    private const array PROJECTS = [
        'refit' => [
            'name' => 'Harbour Dental office refit',
            'customer' => 'Harbour Dental',
            'status' => ProjectStatusEnum::InProgress,
            'hue' => HuePalette::TEAL,
            'icon' => 'building',
            'description' => 'New cabling, workstations and Wi-Fi for the Fremantle clinic.',
            'starts' => -14,
            'due' => 21,
            'milestones' => [
                ['name' => 'Cabling signed off', 'due' => -3, 'done' => true],
                ['name' => 'Workstations live', 'due' => 18],
            ],
            'tickets' => [
                ['title' => 'Run new cabling to surgery 2', 'status' => 'Closed', 'priority' => 'Normal', 'due' => -4, 'labels' => ['Network']],
                ['title' => 'Image six new workstations', 'status' => 'In progress', 'priority' => 'High', 'due' => 7, 'labels' => ['Hardware']],
                ['title' => 'Survey Wi-Fi coverage', 'status' => 'New', 'priority' => 'Normal', 'due' => 12, 'labels' => ['Network']],
            ],
        ],
        'vpn' => [
            'name' => 'Coastal Logistics VPN upgrade',
            'customer' => 'Coastal Logistics',
            'status' => ProjectStatusEnum::AtRisk,
            'hue' => HuePalette::AMBER,
            'icon' => 'shield',
            'description' => 'Replace the depot firewall and move remote staff to the new VPN.',
            'starts' => -7,
            'due' => 10,
            'milestones' => [
                ['name' => 'Firewall swapped', 'due' => 6],
            ],
            'tickets' => [
                ['title' => 'Order replacement firewall', 'status' => 'Waiting on supplier', 'priority' => 'High', 'due' => 3, 'labels' => ['Hardware']],
            ],
        ],
        'docs' => [
            'name' => 'Documentation overhaul',
            'customer' => null,
            'status' => ProjectStatusEnum::Planning,
            'hue' => HuePalette::VIOLET,
            'icon' => 'book',
            'description' => 'Write down how we onboard customers and look after their systems.',
            'starts' => 0,
            'due' => 45,
            'milestones' => [
                ['name' => 'Runbooks drafted', 'due' => 30],
            ],
            'tickets' => [
                ['title' => 'Write the customer onboarding runbook', 'status' => 'New', 'priority' => 'Normal', 'due' => 14, 'labels' => ['Onboarding']],
                ['title' => 'Audit the shared password vault', 'status' => 'New', 'priority' => 'Low', 'due' => 28, 'labels' => []],
            ],
        ],
    ];

    /**
     * @var array<string, int>
     */
    private const array LABELS = [
        'Billing' => HuePalette::VIOLET,
        'Hardware' => HuePalette::AMBER,
        'Network' => HuePalette::BLUE,
        'Onboarding' => HuePalette::GREEN,
    ];

    public function __construct(
        private string $teamId,
        private string $userId,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $labels = $this->seedLabels();
            $groupId = FindGroup::optionsForTeam($this->teamId)->firstWhere('name', 'Service desk')['id']
                ?? CreateGroup::fromGroup(
                    Group::new()->setTeamId($this->teamId)->setName('Service desk')->setHue(HuePalette::TEAL)->setIcon('headset'),
                    leadId: $this->userId,
                )->getId()->toString();

            $customers = [];

            foreach (self::CUSTOMERS as $index => $sample) {
                $customers[$sample['name']] = $this->seedCustomer($sample, sendsInvoice: $index === 0);
            }

            $projects = [];

            foreach (self::PROJECTS as $key => $sample) {
                $customerId = $sample['customer'] === null ? null : $customers[$sample['customer']]['customer'];
                $projects[$key] = $this->seedProject($sample, $customerId, $groupId);
            }

            foreach (self::CUSTOMERS as $sample) {
                foreach ($sample['tickets'] as $sampleTicket) {
                    $this->seedTicket(
                        $sampleTicket,
                        projectId: isset($sampleTicket['project']) ? $projects[$sampleTicket['project']] : null,
                        labels: $labels,
                        customer: $customers[$sample['name']],
                    );
                }
            }

            foreach (self::PROJECTS as $key => $sample) {
                foreach ($sample['tickets'] as $sampleTicket) {
                    $this->seedTicket($sampleTicket, projectId: $projects[$key], labels: $labels);
                }
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
     * @param  array{name: string, email: string, phone: string, location: array{name: string, suburb: string, postcode: string}, contacts: array<int, array{first: string, last: string, position: string, email: string}>, assets: array<int, array{name: string, serial: string}>, service: array{name: string, quantity: int, cents: int}, tickets: array<int, array{title: string, status: string, priority: string, due?: int, labels?: array<int, string>, project?: string}>}  $sample
     * @return array{customer: string, contact: string, location: string}
     */
    private function seedCustomer(array $sample, bool $sendsInvoice): array
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

        return [
            'customer' => $customer->getId()->toString(),
            'contact' => $contacts[0]->getId()->toString(),
            'location' => $location->getId()->toString(),
        ];
    }

    /**
     * @return array<string, string> label ids by name
     */
    private function seedLabels(): array
    {
        $ids = [];

        foreach (array_keys(self::LABELS) as $position => $name) {
            $label = FindChoice::byNameForTeam($this->teamId, ChoiceListEnum::TicketLabel, $name);

            $ids[$name] = ($label->isNull()
                ? CreateChoice::fromChoice(Choice::new()
                    ->setTeamId($this->teamId)
                    ->setListName(ChoiceListEnum::TicketLabel)
                    ->setName($name)
                    ->setHue(self::LABELS[$name])
                    ->setPosition($position))
                : $label)->getId()->toString();
        }

        return $ids;
    }

    /**
     * @param  array{name: string, customer: ?string, status: ProjectStatusEnum, hue: int, icon: string, description: string, starts: int, due: int, milestones: array<int, array{name: string, due: int, done?: bool}>, tickets: array<int, mixed>}  $sample
     */
    private function seedProject(array $sample, ?string $customerId, string $groupId): string
    {
        $today = CarbonImmutable::today();

        $project = CreateProject::fromProject(Project::new()
            ->setTeamId($this->teamId)
            ->setCustomerId($customerId)
            ->setGroupId($groupId)
            ->setLeadId($this->userId)
            ->setName($sample['name'])
            ->setDescription($sample['description'])
            ->setIcon($sample['icon'])
            ->setHue($sample['hue'])
            ->setStatus($sample['status'])
            ->setVisibility(ProjectVisibilityEnum::Open)
            ->setStartsOn($today->addDays($sample['starts']))
            ->setDueOn($today->addDays($sample['due']))
            ->setIsSample(true), createdById: $this->userId);

        foreach ($sample['milestones'] as $milestone) {
            CreateMilestone::fromMilestone(Milestone::new()
                ->setTeamId($this->teamId)
                ->setProjectId($project->getId())
                ->setName($milestone['name'])
                ->setDueOn($today->addDays($milestone['due']))
                ->setCompletedAt(($milestone['done'] ?? false) ? $today->addDays($milestone['due']) : null));
        }

        return $project->getId()->toString();
    }

    /**
     * @param  array{title: string, status: string, priority: string, due?: int, labels?: array<int, string>}  $sample
     * @param  array<string, string>  $labels
     * @param  array{customer: string, contact: string, location: string}|null  $customer
     */
    private function seedTicket(array $sample, ?string $projectId, array $labels, ?array $customer = null): void
    {
        $ticket = CreateTicket::fromTicket(Ticket::new()
            ->setTeamId($this->teamId)
            ->setCustomerId($customer['customer'] ?? null)
            ->setContactId($customer['contact'] ?? null)
            ->setLocationId($customer['location'] ?? null)
            ->setProjectId($projectId)
            ->setAssigneeId($this->userId)
            ->setTitle($sample['title'])
            ->setPriorityId(FindChoice::byNameForTeam($this->teamId, ChoiceListEnum::TicketPriority, $sample['priority'])->getId())
            ->setTypeId(FindChoice::byNameForTeam($this->teamId, ChoiceListEnum::TicketType, $projectId === null ? 'Incident' : 'Task')->getId())
            ->setDepartmentId(FindChoice::byNameForTeam($this->teamId, ChoiceListEnum::TicketDepartment, $projectId === null ? 'Support' : 'Projects')->getId())
            ->setDueOn(isset($sample['due']) ? CarbonImmutable::today()->addDays($sample['due']) : null), openedById: $this->userId);

        TicketEloquentModel::query()
            ->withoutGlobalScopes()
            ->findOrFail($ticket->getId()->toString())
            ->labels()
            ->sync(array_map(fn (string $name): string => $labels[$name], $sample['labels'] ?? []));

        $status = FindChoice::byNameForTeam($this->teamId, ChoiceListEnum::TicketStatus, $sample['status']);

        if ($status->isNotNull() && ! $status->getId()->equalsTo($ticket->getStatusId()->toValue())) {
            MoveTicket::to($ticket, $status->getId()->toString(), PHP_INT_MAX, movedById: $this->userId);
        }
    }
}
