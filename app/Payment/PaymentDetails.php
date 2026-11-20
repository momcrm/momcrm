<?php

declare(strict_types=1);

namespace App\Payment;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Payment\Actions\FindPayment;
use App\PaymentAllocation\PaymentAllocation;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Everything the payment dialog shows: the payment, the invoices it paid,
 * what the viewer may do with it and the methods it can be changed to.
 */
final class PaymentDetails
{
    /**
     * @return array{payment: array<string, mixed>, allocations: array<int, array<string, mixed>>, can: array<string, bool>, methods: list<array{value: string, label: string}>}
     */
    public static function for(Payment $payment, ?Authenticatable $user): array
    {
        return [
            'payment' => $payment->toSummary(),
            'allocations' => array_values(FindPayment::allocationsFor($payment)
                ->map(fn (PaymentAllocation $allocation): array => $allocation->toSummary())
                ->all()),
            'can' => AccessAbilities::for($user, AccessResourceEnum::Payment, $payment),
            'methods' => PaymentMethodEnum::options(),
        ];
    }
}
