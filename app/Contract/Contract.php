<?php

declare(strict_types=1);

namespace App\Contract;

use App\Contract\Factories\ContractFactory;
use App\Contract\Properties\CreatedAt;
use App\Contract\Properties\CustomerId;
use App\Contract\Properties\Description;
use App\Contract\Properties\EndsOn;
use App\Contract\Properties\Id;
use App\Contract\Properties\Notes;
use App\Contract\Properties\PurchaseOrder;
use App\Contract\Properties\SignedOn;
use App\Contract\Properties\StartsOn;
use App\Contract\Properties\Status;
use App\Contract\Properties\TeamId;
use App\Contract\Properties\Title;
use App\Contract\Properties\Type;
use App\Contract\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * An agreement with a customer, such as an MSA or SLA. Its signed copies are
 * attachments on it.
 *
 * @extends AbstractData<ContractEloquentModel>
 */
final class Contract extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public Description $description,
        public EndsOn $endsOn,
        public Id $id,
        public Notes $notes,
        public PurchaseOrder $purchaseOrder,
        public SignedOn $signedOn,
        public StartsOn $startsOn,
        public Status $status,
        public TeamId $teamId,
        public Title $title,
        public Type $type,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ContractFactory
    {
        return ContractEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ContractCollection
    {
        if ($items instanceof ContractCollection) {
            return $items;
        }

        return ContractCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * @return array{id: string, customer_id: string, title: string, type: string, status: string, signed_on: ?string, starts_on: ?string, ends_on: ?string, purchase_order: ?string, description: ?string, notes: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'title' => $this->getTitle()->toString(),
            'type' => $this->getType()->toEnum()->value,
            'status' => $this->getStatus()->toEnum()->value,
            'signed_on' => $this->getSignedOn()->toNullableCarbon()?->toDateString(),
            'starts_on' => $this->getStartsOn()->toNullableCarbon()?->toDateString(),
            'ends_on' => $this->getEndsOn()->toNullableCarbon()?->toDateString(),
            'purchase_order' => $this->getPurchaseOrder()->toNullableString(),
            'description' => $this->getDescription()->toNullableString(),
            'notes' => $this->getNotes()->toNullableString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
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

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(mixed $description): self
    {
        $this->description = new Description($description instanceof AbstractValue ? $description->toValue() : $description);

        return $this;
    }

    public function getEndsOn(): EndsOn
    {
        return $this->endsOn;
    }

    public function setEndsOn(mixed $endsOn): self
    {
        $this->endsOn = new EndsOn($endsOn instanceof AbstractValue ? $endsOn->toValue() : $endsOn);

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

    public function getNotes(): Notes
    {
        return $this->notes;
    }

    public function setNotes(mixed $notes): self
    {
        $this->notes = new Notes($notes instanceof AbstractValue ? $notes->toValue() : $notes);

        return $this;
    }

    public function getPurchaseOrder(): PurchaseOrder
    {
        return $this->purchaseOrder;
    }

    public function setPurchaseOrder(mixed $purchaseOrder): self
    {
        $this->purchaseOrder = new PurchaseOrder($purchaseOrder instanceof AbstractValue ? $purchaseOrder->toValue() : $purchaseOrder);

        return $this;
    }

    public function getSignedOn(): SignedOn
    {
        return $this->signedOn;
    }

    public function setSignedOn(mixed $signedOn): self
    {
        $this->signedOn = new SignedOn($signedOn instanceof AbstractValue ? $signedOn->toValue() : $signedOn);

        return $this;
    }

    public function getStartsOn(): StartsOn
    {
        return $this->startsOn;
    }

    public function setStartsOn(mixed $startsOn): self
    {
        $this->startsOn = new StartsOn($startsOn instanceof AbstractValue ? $startsOn->toValue() : $startsOn);

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

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(mixed $title): self
    {
        $this->title = new Title($title instanceof AbstractValue ? $title->toValue() : $title);

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
