<?php

declare(strict_types=1);

namespace App\Charge\Actions;

use App\Charge\Charge;
use App\Charge\ChargeEloquentModel;

/**
 * Deletes a charge that hasn't been billed.
 */
final readonly class DeleteCharge
{
    public function __construct(
        private Charge $charge,
    ) {}

    public function __invoke(): void
    {
        ChargeEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->charge->getId()->toString())
            ?->delete();
    }

    public static function fromCharge(Charge $charge): void
    {
        new self($charge)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
