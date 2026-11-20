<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionEloquentModel;
use App\BankTransaction\BankTransactionMatchEnum;
use App\BankTransaction\Exceptions\CouldNotApplyBankTransaction;
use App\BankTransaction\Properties\CustomerId;
use App\BankTransaction\Properties\HiddenAt;
use App\BankTransaction\Properties\MatchedBy;
use App\BankTransaction\Properties\PaymentId;
use App\Invoice\Actions\FindInvoice;
use App\Payment\Actions\RecordPayment;
use App\Payment\Payment;
use App\Shared\Types;
use Illuminate\Support\Facades\DB;

/**
 * Records a payment from a bank feed line, which then shows as applied.
 * The line is locked while it happens, so two people can't record it twice.
 * Deleting the payment later returns the line to pending.
 */
final readonly class ApplyBankTransaction
{
    /**
     * @param  array<string, int>  $allocations  cents applied, keyed by invoice id
     */
    public function __construct(
        private BankTransaction $transaction,
        private Payment $payment,
        private array $allocations,
    ) {}

    /**
     * @throws CouldNotApplyBankTransaction
     */
    public function __invoke(): Payment
    {
        return DB::transaction(function (): Payment {
            $model = BankTransactionEloquentModel::query()
                ->withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($this->transaction->getId()->toString());

            if ($model->payment_id !== null) {
                throw CouldNotApplyBankTransaction::alreadyApplied();
            }

            $payment = RecordPayment::fromPayment($this->payment, $this->allocations);
            $customerId = $payment->getCustomerId()->toString();

            $model->update([
                PaymentId::getDatabaseTableColumnName() => $payment->getId()->toString(),
                CustomerId::getDatabaseTableColumnName() => $customerId,
                MatchedBy::getDatabaseTableColumnName() => $model->customer_id === $customerId && $model->matched_by !== null
                    ? $model->matched_by
                    : BankTransactionMatchEnum::Manual->value,
                HiddenAt::getDatabaseTableColumnName() => null,
            ]);

            return $payment;
        });
    }

    /**
     * @param  array<string, int>  $allocations  cents applied, keyed by invoice id
     *
     * @throws CouldNotApplyBankTransaction
     */
    public static function asPayment(BankTransaction $transaction, Payment $payment, array $allocations): Payment
    {
        return new self($transaction, $payment, $allocations)->handle();
    }

    /**
     * What the money pays when nobody says: the customer's invoices still
     * owing, oldest due first, until it runs out.
     *
     * @return array<string, int> cents applied, keyed by invoice id
     */
    public static function oldestInvoicesFirst(string $customerId, string $teamId, int $amountInCents): array
    {
        $allocations = [];
        $left = $amountInCents;

        foreach (FindInvoice::outstandingForCustomer($customerId, $teamId) as $invoice) {
            if ($left <= 0) {
                break;
            }

            $applied = min($left, Types::intOrZero($invoice['balance_in_cents']));
            $allocations[Types::string($invoice['id'])] = $applied;
            $left -= $applied;
        }

        return $allocations;
    }

    /**
     * @throws CouldNotApplyBankTransaction
     */
    public function handle(): Payment
    {
        return $this->__invoke();
    }

    /**
     * @throws CouldNotApplyBankTransaction
     */
    public function execute(): Payment
    {
        return $this->__invoke();
    }
}
