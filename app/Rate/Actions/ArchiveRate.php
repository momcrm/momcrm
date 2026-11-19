<?php

declare(strict_types=1);

namespace App\Rate\Actions;

use App\Rate\Rate;
use Carbon\CarbonImmutable;

/**
 * Archives a rate, or brings it back. An archived rate is no longer offered
 * for new services, but the services already on it keep it and are billed
 * from it as before.
 */
final readonly class ArchiveRate
{
    public function __construct(
        private Rate $rate,
        private bool $archive = true,
    ) {}

    public function __invoke(): Rate
    {
        return UpdateRate::fromRate($this->rate->setArchivedAt($this->archive ? CarbonImmutable::now() : null));
    }

    public static function archive(Rate $rate): Rate
    {
        return new self($rate, archive: true)->handle();
    }

    public static function restore(Rate $rate): Rate
    {
        return new self($rate, archive: false)->handle();
    }

    public function handle(): Rate
    {
        return $this->__invoke();
    }

    public function execute(): Rate
    {
        return $this->__invoke();
    }
}
