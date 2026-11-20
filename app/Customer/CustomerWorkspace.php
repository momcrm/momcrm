<?php

declare(strict_types=1);

namespace App\Customer;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Asset\Actions\FindAsset;
use App\Attachment\Actions\FindAttachment;
use App\Contact\Actions\FindContact;
use App\Contract\Actions\FindContract;
use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\Actions\FindInvoice;
use App\Location\Actions\FindLocation;
use App\Payment\Actions\FindPayment;
use App\Project\Actions\FindProject;
use App\Secret\Actions\FindSecret;
use App\Service\Actions\FindService;
use App\Ticket\Actions\FindTicket;
use Illuminate\Http\Request;

/**
 * A customer opened in its own workspace: the record each tab works on, and
 * the header and nav every tab shares (name, type, what you may do, and how
 * many of each record the customer has).
 */
final readonly class CustomerWorkspace
{
    private function __construct(
        public Customer $customer,
        public string $teamId,
        public string $userId,
    ) {}

    /**
     * @throws CouldNotFindCustomer
     */
    public static function open(Request $request, string $customerId): self
    {
        $teamId = (string) $request->user()?->current_team_id;
        $customer = FindCustomer::byIdForTeam($customerId, $teamId);

        if ($customer->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return new self($customer, $teamId, (string) $request->user()?->id);
    }

    public function customerId(): string
    {
        return $this->customer->getId()->toString();
    }

    /**
     * The props every workspace tab shares, under `workspace`.
     *
     * @return array{workspace: array{customer: array<string, mixed>, custom_field_labels: array<string, string>, can: array<string, bool>, counts: array<string, int>}}
     */
    public function props(Request $request): array
    {
        return [
            'workspace' => [
                'customer' => $this->customer->toSummary(),
                'custom_field_labels' => CustomFieldRelationLabels::for($this->customer->getTeamId()->toString(), CustomFieldTargetEnum::Customer, $this->customer->getCustomFields()->toValues()),
                'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Customer, $this->customer),
                'counts' => $this->counts(),
            ],
        ];
    }

    /**
     * How many of each record the customer has, for the nav. Tickets count
     * only the open ones.
     *
     * @return array<string, int>
     */
    public function counts(): array
    {
        $customerId = $this->customerId();

        return [
            'tickets' => FindTicket::paginateForTeam($this->teamId, FindTicket::FILTER_OPEN, customerId: $customerId, perPage: 1)->total(),
            'projects' => FindProject::forCustomer($this->teamId, $this->userId, $customerId)->count(),
            'contacts' => FindContact::forCustomer($customerId, $this->teamId)->count(),
            'locations' => FindLocation::forCustomer($customerId, $this->teamId)->count(),
            'assets' => FindAsset::forCustomer($customerId, $this->teamId)->count(),
            'services' => FindService::forCustomer($customerId, $this->teamId)->withoutArchived()->count(),
            'files' => FindAttachment::countForCustomer($customerId),
            'contracts' => FindContract::countForCustomer($customerId, $this->teamId),
            'secrets' => FindSecret::countForCustomer($customerId, $this->teamId),
            'invoices' => FindInvoice::paginateForTeam($this->teamId, customerId: $customerId, perPage: 1)->total(),
            'payments' => FindPayment::countForCustomer($customerId, $this->teamId),
        ];
    }
}
