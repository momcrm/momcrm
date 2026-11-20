<?php

declare(strict_types=1);

namespace App\Payment\Actions;

use App\Payment\Payment;
use App\Payment\PaymentEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Saves a payment's details.
 */
final readonly class UpdatePayment
{
    public function __construct(
        private Payment $payment,
    ) {}

    public function __invoke(): Payment
    {
        PaymentEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->payment->getId()->toString())
            ?->update($this->payment->forDatabaseUpdate());

        return $this->payment;
    }

    public static function fromPayment(Payment $payment): Payment
    {
        return new self($payment)->handle();
    }

    public function handle(): Payment
    {
        return $this->__invoke();
    }

    public function execute(): Payment
    {
        return $this->__invoke();
    }
}
