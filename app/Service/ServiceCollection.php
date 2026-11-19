<?php

declare(strict_types=1);

namespace App\Service;

use App\Customer\CustomerEloquentModel;
use App\InvoiceItem\Actions\FindInvoiceItem;
use App\Location\LocationEloquentModel;
use App\Rate\Actions\FindRate;
use App\Rate\Rate;
use App\Shared\Abstracts\AbstractDataCollection;
use App\Shared\Types;

/**
 * @extends AbstractDataCollection<int, Service>
 */
final class ServiceCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Service::class;
    }

    /**
     * Each service as the pages show it, with its rate, what it costs and its
     * next invoice, reading the rates and what was billed in one query each.
     *
     * @return list<array<string, mixed>>
     */
    public function toSummaries(): array
    {
        $rates = FindRate::byIds(array_values(array_unique(array_filter($this->map(fn (Service $service): ?string => $service->getRateId()->toNullableString())->all()))));
        $billedThrough = FindInvoiceItem::billedThroughForServices(array_values($this->map(fn (Service $service): string => $service->getId()->toString())->all()));

        return array_values($this->map(fn (Service $service): array => $service->toSummary(
            $rates[$service->getRateId()->toString()] ?? Rate::new(),
            $billedThrough[$service->getId()->toString()] ?? null,
        ))->all());
    }

    /**
     * Each service as a row of the services list: its summary with the names
     * of its customer and location, reading each kind of name in one query.
     *
     * @return list<array<string, mixed>>
     */
    public function toListItems(): array
    {
        $customerNames = CustomerEloquentModel::query()
            ->withoutGlobalScopes()
            ->whereKey($this->map(fn (Service $service): string => $service->getCustomerId()->toString())->unique()->all())
            ->pluck('name', 'id');
        $locationNames = LocationEloquentModel::query()
            ->withoutGlobalScopes()
            ->whereKey($this->map(fn (Service $service): ?string => $service->getLocationId()->toNullableString())->filter()->unique()->all())
            ->pluck('name', 'id');
        $summaries = $this->toSummaries();

        return array_values($this->values()->map(fn (Service $service, int $index): array => [
            ...$summaries[$index],
            'customer_name' => Types::nullableString($customerNames->get($service->getCustomerId()->toString())),
            'location_name' => Types::nullableString($locationNames->get($service->getLocationId()->toString())),
            'updated_at' => $service->getUpdatedAt()->toNullableISOString(),
        ])->all());
    }

    /**
     * The services that are not archived.
     */
    public function withoutArchived(): self
    {
        return $this->reject(fn (Service $service): bool => $service->isArchived())->values();
    }
}
