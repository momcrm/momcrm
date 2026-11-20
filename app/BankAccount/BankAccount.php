<?php

declare(strict_types=1);

namespace App\BankAccount;

use App\BankAccount\Factories\BankAccountFactory;
use App\BankAccount\Properties\CreatedAt;
use App\BankAccount\Properties\CsvColumns;
use App\BankAccount\Properties\Currency;
use App\BankAccount\Properties\Id;
use App\BankAccount\Properties\Kind;
use App\BankAccount\Properties\LastImportedAt;
use App\BankAccount\Properties\Name;
use App\BankAccount\Properties\TeamId;
use App\BankAccount\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * One of the team's bank accounts, whose statements are imported as bank
 * feed lines: the money customers paid into it.
 *
 * @extends AbstractData<BankAccountEloquentModel>
 */
final class BankAccount extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CsvColumns $csvColumns,
        public Currency $currency,
        public Id $id,
        public Kind $kind,
        public LastImportedAt $lastImportedAt,
        public Name $name,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): BankAccountFactory
    {
        return BankAccountEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): BankAccountCollection
    {
        if ($items instanceof BankAccountCollection) {
            return $items;
        }

        return BankAccountCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the settings page and the bank feed pickers show.
     *
     * @return array{id: string, name: string, kind: string, kind_label: string, currency: string, last_imported_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
            'kind' => $this->getKind()->toEnum()->value,
            'kind_label' => $this->getKind()->toEnum()->label(),
            'currency' => $this->getCurrency()->toEnum()->value,
            'last_imported_at' => $this->getLastImportedAt()->toNullableISOString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCsvColumns(): CsvColumns
    {
        return $this->csvColumns;
    }

    public function setCsvColumns(mixed $csvColumns): self
    {
        $this->csvColumns = new CsvColumns($csvColumns instanceof AbstractValue ? $csvColumns->toValue() : $csvColumns);

        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(mixed $currency): self
    {
        $this->currency = new Currency($currency instanceof AbstractValue ? $currency->toValue() : $currency);

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

    public function getLastImportedAt(): LastImportedAt
    {
        return $this->lastImportedAt;
    }

    public function setLastImportedAt(mixed $lastImportedAt): self
    {
        $this->lastImportedAt = new LastImportedAt($lastImportedAt instanceof AbstractValue ? $lastImportedAt->toValue() : $lastImportedAt);

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
