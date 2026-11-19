<?php

declare(strict_types=1);

namespace App\Service;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Contact\Actions\FindContact;
use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\Properties\ServiceId as InvoiceItemServiceId;
use App\Location\Actions\FindLocation;
use App\Service\Actions\FindService;
use App\Service\Exceptions\CouldNotFindService;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Http\Request;

/**
 * A service opened on its own page, as Amphora opens a company service: the
 * record each section works on, and the header and nav every section shares
 * (its name, customer, status, what you may do and how many invoice lines
 * and linked records it has).
 */
final readonly class ServiceWorkspace
{
    private function __construct(
        public Service $service,
        public Customer $customer,
        public string $teamId,
        public string $userId,
    ) {}

    /**
     * @throws CouldNotFindService
     */
    public static function open(Request $request, string $serviceId): self
    {
        $teamId = (string) $request->user()?->current_team_id;
        $service = FindService::byIdForTeam($serviceId, $teamId);

        if ($service->isNull()) {
            throw CouldNotFindService::notFound();
        }

        return new self($service, FindCustomer::byIdForTeam($service->getCustomerId()->toString(), $teamId), $teamId, (string) $request->user()?->id);
    }

    public function serviceId(): string
    {
        return $this->service->getId()->toString();
    }

    /**
     * The props every section shares, under `workspace`.
     *
     * @return array{workspace: array{service: array<string, mixed>, customer: array{id: string, name: string}, location: ?array{id: string, name: string}, contact: ?array{id: string, name: string}, can: array<string, bool>, counts: array{linked: int, invoices: int}}}
     */
    public function props(Request $request): array
    {
        $location = FindLocation::byIdForTeam($this->service->getLocationId()->toNullableString(), $this->teamId);
        $contact = FindContact::byIdForTeam($this->service->getContactId()->toNullableString(), $this->teamId);

        return [
            'workspace' => [
                'service' => ServiceCollection::make([$this->service])->toSummaries()[0],
                'customer' => ['id' => $this->customer->getId()->toString(), 'name' => $this->customer->getName()->toString()],
                'location' => $location->isNull() ? null : ['id' => $location->getId()->toString(), 'name' => $location->getName()->toString()],
                'contact' => $contact->isNull() ? null : ['id' => $contact->getId()->toString(), 'name' => $contact->getFullName()],
                'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Service, $this->service),
                'counts' => [
                    'linked' => array_sum(array_column($this->linkedRecords(), 'count')),
                    'invoices' => InvoiceItemEloquentModel::query()
                        ->withoutGlobalScope(CurrentTeamScope::class)
                        ->where(InvoiceItemServiceId::getDatabaseTableColumnName(), $this->serviceId())
                        ->count(),
                ],
            ],
        ];
    }

    /**
     * What points at the service through record-link custom fields.
     *
     * @return list<array{key: string, source: string, field: string, count: int, records: list<array{id: string, title: string, url: string}>}>
     */
    public function linkedRecords(): array
    {
        return FindLinkedRecords::to($this->teamId, $this->userId, CustomFieldRelationEnum::Service, $this->serviceId());
    }
}
