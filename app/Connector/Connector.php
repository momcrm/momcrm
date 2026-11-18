<?php

declare(strict_types=1);

namespace App\Connector;

use App\Connector\Factories\ConnectorFactory;
use App\Connector\Properties\Account;
use App\Connector\Properties\ConnectedById;
use App\Connector\Properties\CreatedAt;
use App\Connector\Properties\Id;
use App\Connector\Properties\LastError;
use App\Connector\Properties\Name;
use App\Connector\Properties\Settings;
use App\Connector\Properties\Status;
use App\Connector\Properties\TeamId;
use App\Connector\Properties\Type;
use App\Connector\Properties\UpdatedAt;
use App\Connector\Sdk\ConnectorRegistry;
use App\Connector\Sdk\ConnectorType;
use App\Connector\Sdk\ConnectsByForm;
use App\Connector\Sdk\ConnectsBySignIn;
use App\Connector\Sdk\SignInOption;
use App\Shared\Types;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * An outside account a team has connected. The credentials that reach it are
 * deliberately not on this object, so it can be listed, logged and sent to
 * pages without giving them away: the actions that use them read them.
 *
 * @extends AbstractData<ConnectorEloquentModel>
 */
final class Connector extends AbstractData
{
    public function __construct(
        public Account $account,
        public ConnectedById $connectedById,
        public CreatedAt $createdAt,
        public Id $id,
        public LastError $lastError,
        public Name $name,
        public Settings $settings,
        public Status $status,
        public TeamId $teamId,
        public Type $type,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ConnectorFactory
    {
        return ConnectorEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ConnectorCollection
    {
        if ($items instanceof ConnectorCollection) {
            return $items;
        }

        return ConnectorCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * Whether the connector can be used right now.
     */
    public function isConnected(): bool
    {
        return $this->getStatus()->toEnum() === ConnectorStatusEnum::Connected;
    }

    /**
     * What kind of account this is, and so what it can do. A type that is
     * no longer installed is stood in for by one that can do nothing.
     */
    public function connectorType(): ConnectorType
    {
        return ConnectorRegistry::instance()->get($this->getType()->toNullableString());
    }

    public function hasCapability(ConnectorCapabilityEnum $capability): bool
    {
        return $this->connectorType()->provides($capability);
    }

    /**
     * Whether an option was chosen when the account was signed in to.
     */
    public function hasOption(string $option): bool
    {
        return ($this->getSettings()->toArray()[$option] ?? false) === true;
    }

    /**
     * The options chosen at sign-in, each in a word or two.
     *
     * @return list<string>
     */
    public function options(): array
    {
        $type = $this->connectorType();

        if (! $type instanceof ConnectsBySignIn) {
            return [];
        }

        return array_values(array_map(
            fn (SignInOption $option): string => $option->summary ?? $option->label,
            array_filter($type->signInOptions(), fn (SignInOption $option): bool => $this->hasOption($option->key)),
        ));
    }

    /**
     * The fields the connectors page shows.
     *
     * @return array{id: string, type: string, type_label: string, name: string, account: string, status: string, capabilities: list<string>, options: list<string>, last_error: ?string, uses_sign_in: bool, settings: array<string, mixed>|null, connected_at: ?string}
     */
    public function toListItem(): array
    {
        $type = $this->connectorType();

        return [
            'id' => $this->getId()->toString(),
            'type' => $type->key(),
            'type_label' => $type->label(),
            'name' => $this->getName()->toString(),
            'account' => $this->getAccount()->toString(),
            'status' => $this->getStatus()->toEnum()->value,
            'capabilities' => array_map(fn (ConnectorCapabilityEnum $capability): string => $capability->label(), $type->capabilities()),
            'options' => $this->options(),
            'last_error' => $this->getLastError()->toNullableString(),
            'uses_sign_in' => $type instanceof ConnectsBySignIn,
            // Only what was typed into a form to connect, to fill it again. Never the secrets.
            'settings' => $type instanceof ConnectsByForm ? Types::stringKeyed($this->getSettings()->toArray()) : null,
            'connected_at' => $this->getCreatedAt()->toNullableCarbon()?->toIso8601String(),
        ];
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(mixed $account): self
    {
        $this->account = new Account($account instanceof AbstractValue ? $account->toValue() : $account);

        return $this;
    }

    public function getConnectedById(): ConnectedById
    {
        return $this->connectedById;
    }

    public function setConnectedById(mixed $connectedById): self
    {
        $this->connectedById = new ConnectedById($connectedById instanceof AbstractValue ? $connectedById->toValue() : $connectedById);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
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

    public function getLastError(): LastError
    {
        return $this->lastError;
    }

    public function setLastError(mixed $lastError): self
    {
        $this->lastError = new LastError($lastError instanceof AbstractValue ? $lastError->toValue() : $lastError);

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(mixed $name): self
    {
        $this->name = new Name($name instanceof AbstractValue ? $name->toValue() : $name);

        return $this;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function setSettings(mixed $settings): self
    {
        $this->settings = new Settings($settings instanceof AbstractValue ? $settings->toValue() : $settings);

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(mixed $status): self
    {
        $this->status = new Status($status instanceof AbstractValue ? $status->toValue() : $status);

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

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(mixed $type): self
    {
        $this->type = new Type($type instanceof AbstractValue ? $type->toValue() : $type);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
