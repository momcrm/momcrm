<?php

declare(strict_types=1);

namespace App\Mailbox;

use App\Connector\Connector;
use App\Mailbox\Factories\MailboxFactory;
use App\Mailbox\Properties\Address;
use App\Mailbox\Properties\AutoCreateTickets;
use App\Mailbox\Properties\ConnectorId;
use App\Mailbox\Properties\CreatedAt;
use App\Mailbox\Properties\DisplayName;
use App\Mailbox\Properties\GroupId;
use App\Mailbox\Properties\Id;
use App\Mailbox\Properties\IsShared;
use App\Mailbox\Properties\LastError;
use App\Mailbox\Properties\LastSyncedAt;
use App\Mailbox\Properties\SyncedThrough;
use App\Mailbox\Properties\TeamId;
use App\Mailbox\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A mailbox whose mail becomes tickets. It is read and sent through a
 * connector, which holds the sign-in; the mailbox keeps what is about tickets.
 *
 * @extends AbstractData<MailboxEloquentModel>
 */
final class Mailbox extends AbstractData
{
    public function __construct(
        public Address $address,
        public AutoCreateTickets $autoCreateTickets,
        public ConnectorId $connectorId,
        public CreatedAt $createdAt,
        public DisplayName $displayName,
        public GroupId $groupId,
        public Id $id,
        public IsShared $isShared,
        public LastError $lastError,
        public LastSyncedAt $lastSyncedAt,
        public SyncedThrough $syncedThrough,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): MailboxFactory
    {
        return MailboxEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): MailboxCollection
    {
        if ($items instanceof MailboxCollection) {
            return $items;
        }

        return MailboxCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the email settings page shows. Whether the mailbox works
     * depends on the connector it goes through.
     *
     * @return array{id: string, address: string, display_name: ?string, is_shared: bool, status: string, connector_id: string, connector_name: string, connector_type: ?string, group_id: ?string, auto_create_tickets: bool, last_synced_at: ?string, last_error: ?string}
     */
    public function toListItem(Connector $connector): array
    {
        return [
            'id' => $this->getId()->toString(),
            'address' => $this->getAddress()->toString(),
            'display_name' => $this->getDisplayName()->toNullableString(),
            'is_shared' => $this->getIsShared()->toBoolean(),
            'status' => $connector->getStatus()->toEnum()->value,
            'connector_id' => $this->getConnectorId()->toString(),
            'connector_name' => $connector->getName()->toString(),
            'connector_type' => $connector->isNull() ? null : $connector->connectorType()->label(),
            'group_id' => $this->getGroupId()->toNullableString(),
            'auto_create_tickets' => $this->getAutoCreateTickets()->toBoolean(),
            'last_synced_at' => $this->getLastSyncedAt()->toNullableCarbon()?->toIso8601String(),
            'last_error' => $this->getLastError()->toNullableString() ?? $connector->getLastError()->toNullableString(),
        ];
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(mixed $address): self
    {
        $this->address = new Address($address instanceof AbstractValue ? $address->toValue() : $address);

        return $this;
    }

    public function getAutoCreateTickets(): AutoCreateTickets
    {
        return $this->autoCreateTickets;
    }

    public function setAutoCreateTickets(mixed $autoCreateTickets): self
    {
        $this->autoCreateTickets = new AutoCreateTickets($autoCreateTickets instanceof AbstractValue ? $autoCreateTickets->toValue() : $autoCreateTickets);

        return $this;
    }

    public function getConnectorId(): ConnectorId
    {
        return $this->connectorId;
    }

    public function setConnectorId(mixed $connectorId): self
    {
        $this->connectorId = new ConnectorId($connectorId instanceof AbstractValue ? $connectorId->toValue() : $connectorId);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getDisplayName(): DisplayName
    {
        return $this->displayName;
    }

    public function setDisplayName(mixed $displayName): self
    {
        $this->displayName = new DisplayName($displayName instanceof AbstractValue ? $displayName->toValue() : $displayName);

        return $this;
    }

    public function getGroupId(): GroupId
    {
        return $this->groupId;
    }

    public function setGroupId(mixed $groupId): self
    {
        $this->groupId = new GroupId($groupId instanceof AbstractValue ? $groupId->toValue() : $groupId);

        return $this;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getIsShared(): IsShared
    {
        return $this->isShared;
    }

    public function setIsShared(mixed $isShared): self
    {
        $this->isShared = new IsShared($isShared instanceof AbstractValue ? $isShared->toValue() : $isShared);

        return $this;
    }

    public function getLastError(): LastError
    {
        return $this->lastError;
    }

    public function setLastError(mixed $lastError): self
    {
        $this->lastError = new LastError($lastError instanceof AbstractValue ? $lastError->toValue() : $lastError);

        return $this;
    }

    public function getLastSyncedAt(): LastSyncedAt
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(mixed $lastSyncedAt): self
    {
        $this->lastSyncedAt = new LastSyncedAt($lastSyncedAt instanceof AbstractValue ? $lastSyncedAt->toValue() : $lastSyncedAt);

        return $this;
    }

    public function getSyncedThrough(): SyncedThrough
    {
        return $this->syncedThrough;
    }

    public function setSyncedThrough(mixed $syncedThrough): self
    {
        $this->syncedThrough = new SyncedThrough($syncedThrough instanceof AbstractValue ? $syncedThrough->toValue() : $syncedThrough);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
