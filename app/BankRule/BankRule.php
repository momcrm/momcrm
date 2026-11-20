<?php

declare(strict_types=1);

namespace App\BankRule;

use App\BankRule\Factories\BankRuleFactory;
use App\BankRule\Properties\Contains;
use App\BankRule\Properties\CreatedAt;
use App\BankRule\Properties\Customer;
use App\BankRule\Properties\CustomerId;
use App\BankRule\Properties\Id;
use App\BankRule\Properties\Kind;
use App\BankRule\Properties\TeamId;
use App\BankRule\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A team's rule for bank feed deposits: one whose description contains its
 * text is from a customer, or is hidden. Rules are applied before anything
 * is guessed.
 *
 * @extends AbstractData<BankRuleEloquentModel>
 */
final class BankRule extends AbstractData
{
    public function __construct(
        public Contains $contains,
        public CreatedAt $createdAt,
        public Customer $customer,
        public CustomerId $customerId,
        public Id $id,
        public Kind $kind,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): BankRuleFactory
    {
        return BankRuleEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): BankRuleCollection
    {
        if ($items instanceof BankRuleCollection) {
            return $items;
        }

        return BankRuleCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * Whether the description contains the rule's text, ignoring case and
     * how the words are spaced.
     */
    public function appliesTo(string $description): bool
    {
        $text = self::normalise($this->getContains()->toString());

        return $text !== '' && str_contains(self::normalise($description), $text);
    }

    /**
     * @return array{id: string, kind: string, contains: string, customer_id: ?string, customer_name: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'kind' => $this->getKind()->toEnum()->value,
            'contains' => $this->getContains()->toString(),
            'customer_id' => $this->getCustomerId()->toNullableString(),
            'customer_name' => $this->getCustomer()->toNullableData()?->getName()->toString(),
        ];
    }

    private static function normalise(string $value): string
    {
        return mb_trim((string) preg_replace('/\s+/', ' ', mb_strtolower($value)));
    }

    public function getContains(): Contains
    {
        return $this->contains;
    }

    public function setContains(mixed $contains): self
    {
        $this->contains = new Contains($contains instanceof AbstractValue ? $contains->toValue() : $contains);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function setCustomerId(mixed $customerId): self
    {
        $this->customerId = new CustomerId($customerId instanceof AbstractValue ? $customerId->toValue() : $customerId);

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

    public function getKind(): Kind
    {
        return $this->kind;
    }

    public function setKind(mixed $kind): self
    {
        $this->kind = new Kind($kind instanceof AbstractValue ? $kind->toValue() : $kind);

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
