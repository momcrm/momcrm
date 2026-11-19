<?php

declare(strict_types=1);

namespace App\Service;

use App\Rate\Actions\FindRate;
use App\Rate\Rate;
use App\Shared\Abstracts\AbstractDataCollection;

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
     * Each service as the pages show it, with its rate and what it costs,
     * reading the rates in one query.
     *
     * @return list<array<string, mixed>>
     */
    public function toSummaries(): array
    {
        $rates = FindRate::byIds(array_values(array_unique(array_filter($this->map(fn (Service $service): ?string => $service->getRateId()->toNullableString())->all()))));

        return array_values($this->map(fn (Service $service): array => $service->toSummary($rates[$service->getRateId()->toString()] ?? Rate::new()))->all());
    }

    /**
     * The services that are not archived.
     */
    public function withoutArchived(): self
    {
        return $this->reject(fn (Service $service): bool => $service->isArchived())->values();
    }
}
