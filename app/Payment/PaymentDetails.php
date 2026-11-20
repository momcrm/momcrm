<?php

declare(strict_types=1);

namespace App\Payment;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\BankTransaction\Actions\FindBankTransaction;
use App\Contact\ContactRecipients;
use App\Payment\Actions\FindPayment;
use App\PaymentAllocation\PaymentAllocation;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Everything the payment dialog shows: the payment, the invoices it paid,
 * what the viewer may do with it, the methods it can be changed to, the
 * contacts its receipt can be sent to and the bank feed line it was recorded
 * from, if any.
 */
final class PaymentDetails
{
    /**
     * @return array{payment: array<string, mixed>, allocations: array<int, array<string, mixed>>, can: array<string, bool>, methods: list<array{value: string, label: string}>, contacts: list<array{id: string, name: string, email: string, is_primary: bool}>, bank_transaction: ?array{account: ?string, posted_on: ?string, description: string}}
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
            'contacts' => ContactRecipients::optionsFor($payment->getCustomerId()->toString(), $payment->getTeamId()->toString()),
            'bank_transaction' => self::bankTransaction($payment),
        ];
    }

    /**
     * @return array{account: ?string, posted_on: ?string, description: string}|null
     */
    private static function bankTransaction(Payment $payment): ?array
    {
        $transaction = FindBankTransaction::forPayment($payment->getId()->toString(), $payment->getTeamId()->toString());

        if ($transaction->isNull()) {
            return null;
        }

        return [
            'account' => $transaction->getBankAccount()->toNullableData()?->getName()->toString(),
            'posted_on' => $transaction->getPostedOn()->toNullableCarbon()?->toDateString(),
            'description' => $transaction->getDescription()->toString(),
        ];
    }
}
