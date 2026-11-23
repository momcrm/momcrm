<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Article\Actions\CreateArticle;
use App\Article\Actions\StoreArticleImage;
use App\Article\Article;
use App\Article\ArticleBody;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleImages;
use App\Article\ArticleStatusEnum;
use App\Article\Properties\Id as ArticleId;
use App\ArticleCategory\Actions\CreateArticleCategory;
use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleCategory\ArticleCategory;
use App\ArticleLink\Actions\CreateArticleLink;
use App\ArticleLink\ArticleLink;
use App\ArticleLink\ArticleLinkTargetEnum;
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
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use App\Location\Actions\CreateLocation;
use App\Location\Location;
use App\Milestone\Actions\CreateMilestone;
use App\Milestone\Milestone;
use App\Payment\Actions\RecordPayment;
use App\Payment\Payment;
use App\Payment\PaymentMethodEnum;
use App\Project\Actions\CreateProject;
use App\Project\Project;
use App\Project\ProjectStatusEnum;
use App\Project\ProjectVisibilityEnum;
use App\Rate\Actions\CreateRate;
use App\Rate\Actions\FindRate;
use App\Rate\Rate;
use App\Rate\RatePrice;
use App\Service\Actions\CreateService;
use App\Service\Service;
use App\Service\ServiceStatusEnum;
use App\Shared\CurrencyEnum;
use App\Shared\HuePalette;
use App\Ticket\Actions\CreateTicket;
use App\Ticket\Actions\MoveTicket;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Fills a team with a few example customers, with contacts, locations, assets,
 * services, tickets and an invoice, plus projects with milestones, labels, a
 * group and a knowledge base article, so a new user has something to click
 * around.
 *
 * Sample customers and projects are marked as sample; removing them removes
 * their tickets, articles and the rest. Labels, article categories and the
 * group stay, as team settings.
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
     * The labels the samples use. New teams start with them (see
     * ChoiceListEnum::defaults()); any the team has since deleted come back.
     *
     * @var array<string, int>
     */
    private const array LABELS = [
        'Billing' => HuePalette::VIOLET,
        'Hardware' => HuePalette::AMBER,
        'Network' => HuePalette::BLUE,
        'Onboarding' => HuePalette::GREEN,
    ];

    /**
     * The sample article's category, as its parent and then itself.
     *
     * @var list<string>
     */
    private const array ARTICLE_CATEGORY = ['Networking', 'Remote access'];

    /**
     * The sample article's body. It uses every element the editor makes, so
     * the article page shows them all; {image} is the diagram's address.
     */
    private const string ARTICLE_BODY = <<<'HTML'
        <p>Each Coastal Logistics depot reaches head office over an <strong>IPsec site-to-site tunnel</strong> on its FortiGate. This article covers the <em>standard</em> setup, how to check the tunnel is healthy, and what to do when it drops. It replaces the <s>old PPTP guide</s>, which no longer applies, and the steps here are <u>the only supported way</u> to set a depot up.</p>
        <p>Questions go to <a href="mailto:network@example.com">network@example.com</a>. The vendor's own reference is the <a href="https://docs.fortinet.com">Fortinet documentation</a>.</p>
        <img src="{image}" alt="Diagram of a depot connected to head office over an IPsec tunnel">
        <h2>Before you start</h2>
        <p>You need:</p>
        <ul>
        <li><p>Admin access to the FortiGate at the depot and at head office</p></li>
        <li><p>The pre-shared key, from the <code>Coastal VPN</code> secret on the customer</p></li>
        <li><p>The depot's addresses:</p><ul><li><p>Public IP, from the internet service</p></li><li><p>Internal subnet, such as <code>10.20.0.0/16</code></p></li></ul></li>
        <li><p>About 20 minutes per depot</p></li>
        </ul>
        <blockquote><p><strong>Change window.</strong> The depots scan freight between 6am and 6pm. Make changes outside those hours, and tell the depot manager first.</p></blockquote>
        <h2>Set up the tunnel</h2>
        <h3>Phase 1</h3>
        <ol>
        <li><p>Open <strong>VPN › IPsec Tunnels</strong> and choose <em>Create new › Custom</em>.</p></li>
        <li><p>Name it after the depot, such as <code>depot-fremantle</code>.</p></li>
        <li><p>Set the remote gateway to head office's static IP.<br>Use the backup address only if the primary is down.</p></li>
        <li><p>Paste the pre-shared key and save.</p></li>
        </ol>
        <h3>Phase 2</h3>
        <p>Carry on from step 5:</p>
        <ol start="5">
        <li><p>Add a phase 2 selector for the depot subnet.</p></li>
        <li><p>Match the settings in the table below exactly.</p></li>
        </ol>
        <table>
        <colgroup><col><col><col></colgroup>
        <thead><tr><th><p>Setting</p></th><th><p>Phase 1</p></th><th><p>Phase 2</p></th></tr></thead>
        <tbody>
        <tr><td><p>Encryption</p></td><td colspan="2"><p>AES256 on both</p></td></tr>
        <tr><td><p>Authentication</p></td><td><p>SHA256</p></td><td><p>SHA256</p></td></tr>
        <tr><td><p>DH group</p></td><td rowspan="2"><p>14</p></td><td><p>14</p></td></tr>
        <tr><td><p>PFS</p></td><td><p>On</p></td></tr>
        <tr><td><p>Key lifetime</p></td><td><p>86400 s</p></td><td><p>43200 s</p></td></tr>
        </tbody>
        </table>
        <h4>Firewall policies</h4>
        <p>Add two policies, one each way, between the internal interface and the tunnel. Leave NAT <strong>off</strong>.</p>
        <h2>Check it is working</h2>
        <p>From the FortiGate CLI, list the tunnel:</p>
        <pre><code>diagnose vpn tunnel list name depot-fremantle
        # look for: status=up  and  bytes in/out increasing
        execute ping-options source 10.20.0.1
        execute ping 10.10.0.10</code></pre>
        <p>If the output shows <code>status=up</code> and the ping replies, the tunnel is healthy.</p>
        <hr>
        <h2>Troubleshooting</h2>
        <table>
        <thead><tr><th><p>Symptom</p></th><th><p>Likely cause</p></th><th><p>Fix</p></th></tr></thead>
        <tbody>
        <tr><td><p>Tunnel never comes up</p></td><td><p>Pre-shared key or phase 1 mismatch</p></td><td><p>Re-paste the key; compare phase 1 settings on both ends</p></td></tr>
        <tr><td><p>Up, but no traffic</p></td><td><p>Missing firewall policy or route</p></td><td><p>Add both policies and a static route to the remote subnet</p></td></tr>
        <tr><td><p>Drops every afternoon</p></td><td><p>Key lifetime or ISP lease</p></td><td><p>Set DPD to on-idle; check the ISP's DHCP lease time</p></td></tr>
        </tbody>
        </table>
        <p>Still stuck? Raise a ticket against the depot's <strong>Office firewall</strong> asset and attach the output of the commands above.</p>
        HTML;

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

            $tickets = [];

            foreach (self::CUSTOMERS as $sample) {
                foreach ($sample['tickets'] as $sampleTicket) {
                    $tickets[$sampleTicket['title']] = $this->seedTicket(
                        $sampleTicket,
                        projectId: isset($sampleTicket['project']) ? $projects[$sampleTicket['project']] : null,
                        labels: $labels,
                        customer: $customers[$sample['name']],
                    );
                }
            }

            foreach (self::PROJECTS as $key => $sample) {
                foreach ($sample['tickets'] as $sampleTicket) {
                    $tickets[$sampleTicket['title']] = $this->seedTicket($sampleTicket, projectId: $projects[$key], labels: $labels);
                }
            }

            $coastal = $customers['Coastal Logistics'];

            $this->seedArticle($coastal['customer'], $labels, [
                [ArticleLinkTargetEnum::Ticket, $tickets['VPN drops every afternoon']],
                [ArticleLinkTargetEnum::Ticket, $tickets['Order replacement firewall']],
                [ArticleLinkTargetEnum::Asset, $coastal['assets']['Office firewall']],
                [ArticleLinkTargetEnum::Service, $coastal['service']],
            ]);

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
     * The team's rate for a sample service, added to the price list the first
     * time. Rates are the team's own, so they stay when the sample data goes.
     *
     * @param  array{name: string, quantity: int, cents: int}  $service
     */
    private function rateFor(array $service): Rate
    {
        $existing = FindRate::listForTeam($this->teamId, withArchived: true)
            ->first(fn (Rate $rate): bool => $rate->getName()->toString() === $service['name']);

        if ($existing->isNotNull()) {
            return $existing;
        }

        return CreateRate::fromRate(Rate::new()
            ->setTeamId($this->teamId)
            ->setName($service['name'])
            ->setPrices([new RatePrice(CurrencyEnum::default(), $service['cents'])->toArray()])
            ->setBillingPeriodInMonths(1)
            ->setProratesFirstMonth(true));
    }

    /**
     * @param  array{name: string, email: string, phone: string, location: array{name: string, suburb: string, postcode: string}, contacts: array<int, array{first: string, last: string, position: string, email: string}>, assets: array<int, array{name: string, serial: string}>, service: array{name: string, quantity: int, cents: int}, tickets: array<int, array{title: string, status: string, priority: string, due?: int, labels?: array<int, string>, project?: string}>}  $sample
     * @return array{customer: string, contact: string, location: string, assets: array<string, string>, service: string}
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

        $assets = [];

        foreach ($sample['assets'] as $asset) {
            $assets[$asset['name']] = CreateAsset::fromAsset(Asset::new()
                ->setTeamId($this->teamId)
                ->setCustomerId($customer->getId())
                ->setLocationId($location->getId())
                ->setName($asset['name'])
                ->setSerialNumber($asset['serial'])
                ->setStatus(AssetStatusEnum::Active))->getId()->toString();
        }

        $service = CreateService::fromService(Service::new()
            ->setTeamId($this->teamId)
            ->setCustomerId($customer->getId())
            ->setLocationId($location->getId())
            ->setContactId($contacts[0]->getId())
            ->setRateId($this->rateFor($sample['service'])->getId())
            ->setName($sample['service']['name'])
            ->setQuantity($sample['service']['quantity'])
            ->setBillingStartsOn(CarbonImmutable::today()->startOfMonth())
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

            $invoice = ChangeInvoiceStatus::to($invoice, InvoiceStatusEnum::Sent);

            // Half paid, so the invoice shows what's still owing and the
            // customer has a payment to open.
            $invoiceId = $invoice->getId()->toString();
            $half = intdiv(FindInvoice::byIdsForTeam([$invoiceId], $this->teamId)[$invoiceId]->totals()->totalInCents, 2);

            RecordPayment::fromPayment(
                Payment::new()
                    ->setTeamId($this->teamId)
                    ->setCustomerId($customer->getId())
                    ->setCurrency($invoice->getCurrency()->toEnum()->value)
                    ->setPaidOn(CarbonImmutable::today())
                    ->setMethod(PaymentMethodEnum::BankTransfer)
                    ->setReference('EFT 1042')
                    ->setAmountInCents($half),
                [$invoiceId => $half],
            );
        }

        return [
            'customer' => $customer->getId()->toString(),
            'contact' => $contacts[0]->getId()->toString(),
            'location' => $location->getId()->toString(),
            'assets' => $assets,
            'service' => $service->getId()->toString(),
        ];
    }

    /**
     * @return array<string, string> label ids by name
     */
    private function seedLabels(): array
    {
        $ids = [];

        foreach (array_keys(self::LABELS) as $position => $name) {
            $label = FindChoice::byNameForTeam($this->teamId, ChoiceListEnum::Label, $name);

            $ids[$name] = ($label->isNull()
                ? CreateChoice::fromChoice(Choice::new()
                    ->setTeamId($this->teamId)
                    ->setListName(ChoiceListEnum::Label)
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
     * @param  array{customer: string, contact: string, location: string, assets: array<string, string>, service: string}|null  $customer
     * @return string the ticket's id
     */
    private function seedTicket(array $sample, ?string $projectId, array $labels, ?array $customer = null): string
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

        return $ticket->getId()->toString();
    }

    /**
     * A published customer article for Coastal Logistics with a diagram,
     * labels and a category, attached to its tickets, asset and service. It
     * is due for review, so the reminder shows too.
     *
     * @param  array<string, string>  $labels
     * @param  list<array{0: ArticleLinkTargetEnum, 1: string}>  $links
     */
    private function seedArticle(string $customerId, array $labels, array $links): void
    {
        $today = CarbonImmutable::today();
        $articleId = ArticleId::generate()->toString();

        $image = StoreArticleImage::for(
            new UploadedFile(resource_path('sample-data/vpn-diagram.png'), 'vpn-diagram.png', 'image/png', test: true),
            Article::new()->setId($articleId)->setTeamId($this->teamId),
            $this->userId,
        );
        $body = ArticleBody::sanitise(str_replace(
            '{image}',
            ArticleImages::path($articleId, $image->getId()->toString()),
            self::ARTICLE_BODY,
        ));

        $article = CreateArticle::fromArticle(Article::new()
            ->setId($articleId)
            ->setTeamId($this->teamId)
            ->setCustomerId($customerId)
            ->setCategoryId($this->articleCategoryId())
            ->setAuthorId($this->userId)
            ->setEditedById($this->userId)
            ->setTitle('Site-to-site VPN: setup, checks and troubleshooting')
            ->setSummary('How Coastal Logistics depots connect to head office over IPsec, how to tell the tunnel is healthy, and what to do when it drops.')
            ->setBody($body)
            ->setBodyText(ArticleBody::text($body))
            ->setStatus(ArticleStatusEnum::Published)
            ->setClassification(ArticleClassificationEnum::Customer)
            ->setVersionMajor(1)
            ->setVersionMinor(0)
            ->setPublishedAt($today->subDays(120))
            ->setLastReviewedOn($today->subDays(95))
            ->setNextReviewOn($today->subDays(5)));

        ArticleEloquentModel::query()
            ->withoutGlobalScopes()
            ->findOrFail($article->getId()->toString())
            ->labels()
            ->sync([$labels['Network'], $labels['Onboarding']]);

        foreach ($links as [$target, $recordId]) {
            CreateArticleLink::fromArticleLink(ArticleLink::new()
                ->setTeamId($this->teamId)
                ->setArticleId($articleId)
                ->setLinkableType($target)
                ->setLinkableId($recordId)
                ->setCreatedById($this->userId));
        }
    }

    /**
     * The id of the sample article's category, adding it and its parent the
     * first time.
     */
    private function articleCategoryId(): string
    {
        $categories = FindArticleCategory::listForTeam($this->teamId);
        $parentId = null;

        foreach (self::ARTICLE_CATEGORY as $name) {
            $existing = $categories->first(fn (ArticleCategory $category): bool => $category->getName()->toString() === $name
                && $category->getParentId()->toNullableString() === $parentId);

            $parentId = ($existing->isNotNull()
                ? $existing
                : CreateArticleCategory::fromArticleCategory(ArticleCategory::new()
                    ->setTeamId($this->teamId)
                    ->setParentId($parentId)
                    ->setName($name)))->getId()->toString();
        }

        return (string) $parentId;
    }
}
