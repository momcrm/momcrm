<?php

declare(strict_types=1);

namespace App\Invoice\Billing;

use Carbon\CarbonImmutable;

/**
 * The month billing is done for, as Amphora's billing picks it: periods
 * starting in or before it are due, so it is billed up to its last day.
 */
final readonly class BillingMonth
{
    private function __construct(
        public CarbonImmutable $firstDay,
    ) {}

    /**
     * A month written YYYY-MM, or this month when it isn't one.
     */
    public static function fromString(?string $month): self
    {
        $parsed = $month !== null && preg_match('/^(\d{4})-(\d{2})$/', $month, $matches) === 1 && (int) $matches[2] >= 1 && (int) $matches[2] <= 12
            ? CarbonImmutable::create((int) $matches[1], (int) $matches[2], 1)
            : null;

        return new self(($parsed ?? CarbonImmutable::today())->startOfMonth());
    }

    /**
     * The day periods must start by to be billed for the month.
     */
    public function lastDay(): CarbonImmutable
    {
        return $this->firstDay->endOfMonth()->startOfDay();
    }

    public function toString(): string
    {
        return $this->firstDay->format('Y-m');
    }
}
