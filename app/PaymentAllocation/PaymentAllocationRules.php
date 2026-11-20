<?php

declare(strict_types=1);

namespace App\PaymentAllocation;

use App\Customer\Customer;
use App\Invoice\Actions\FindInvoice;
use App\Shared\Types;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Number;
use Illuminate\Validation\Validator;

/**
 * Checks money applied to invoices: each invoice is the customer's, sent,
 * in their currency and owed at least what is applied to it, and together
 * they take no more than the payment has to give.
 */
final class PaymentAllocationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'allocations' => ['array'],
            'allocations.*.invoice_id' => ['required', 'uuid', 'distinct'],
            'allocations.*.amount_in_cents' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'allocations.*.invoice_id.distinct' => __('Each invoice can only be paid once per payment.'),
            'allocations.*.amount_in_cents.required' => __('Enter how much goes to this invoice.'),
            'allocations.*.amount_in_cents.integer' => __('Enter how much goes to this invoice.'),
            'allocations.*.amount_in_cents.min' => __('Enter how much goes to this invoice.'),
        ];
    }

    /**
     * Dollars to cents for each allocation, so the rules see stored units.
     */
    public static function normalise(FormRequest $request): void
    {
        $allocations = $request->input('allocations');

        if (! is_array($allocations)) {
            return;
        }

        $request->merge(['allocations' => array_map(function (mixed $allocation): mixed {
            if (! is_array($allocation) || ! array_key_exists('amount', $allocation)) {
                return $allocation;
            }

            $amount = $allocation['amount'];
            unset($allocation['amount']);
            $allocation['amount_in_cents'] = is_numeric($amount) ? (int) round(((float) $amount) * 100) : $amount;

            return $allocation;
        }, $allocations)]);
    }

    /**
     * The validated allocations as cents keyed by invoice id.
     *
     * @param  array<array-key, mixed>  $validated
     * @return array<string, int>
     */
    public static function allocations(array $validated): array
    {
        $allocations = [];

        foreach (self::arrayOf($validated['allocations'] ?? []) as $allocation) {
            $allocation = self::arrayOf($allocation);
            $allocations[Types::string($allocation['invoice_id'])] = Types::int($allocation['amount_in_cents']);
        }

        return $allocations;
    }

    /**
     * Adds an error for each allocation that doesn't fit its invoice, and one
     * on $amountKey when they take more than $availableInCents.
     */
    public static function check(Validator $validator, Customer $customer, int $availableInCents, string $amountKey): void
    {
        if ($validator->errors()->isNotEmpty() || $customer->isNull()) {
            return;
        }

        $allocations = self::arrayOf($validator->getData()['allocations'] ?? []);
        $invoiceIds = array_values(array_filter(array_map(fn (mixed $allocation): mixed => self::arrayOf($allocation)['invoice_id'] ?? null, $allocations), is_string(...)));
        $invoices = FindInvoice::byIdsForTeam($invoiceIds, $customer->getTeamId()->toString());
        $currency = $customer->getCurrency()->toEnum();
        $total = 0;

        foreach ($allocations as $index => $allocation) {
            $allocation = self::arrayOf($allocation);
            $invoice = $invoices[Types::string($allocation['invoice_id'] ?? '')] ?? null;
            $amount = Types::intOrZero($allocation['amount_in_cents'] ?? 0);
            $total += $amount;

            if ($invoice === null || ! $invoice->getCustomerId()->equalsTo($customer->getId()->toString())) {
                $validator->errors()->add("allocations.{$index}.invoice_id", __('That invoice is not one of this customer\'s.'));

                continue;
            }

            if (! $invoice->canTakePayments()) {
                $validator->errors()->add("allocations.{$index}.invoice_id", __(':reference is :status, so it cannot be paid.', ['reference' => $invoice->getReference(), 'status' => mb_strtolower($invoice->getStatus()->toEnum()->label())]));

                continue;
            }

            if ($invoice->getCurrency()->toEnum() !== $currency) {
                $validator->errors()->add("allocations.{$index}.invoice_id", __(':reference is in :currency, not :customerCurrency.', ['reference' => $invoice->getReference(), 'currency' => $invoice->getCurrency()->toEnum()->value, 'customerCurrency' => $currency->value]));

                continue;
            }

            $balance = $invoice->balanceAfter($invoice->totals());

            if ($amount > $balance) {
                $validator->errors()->add("allocations.{$index}.amount_in_cents", __('Only :balance is owing on :reference.', ['balance' => Number::currency($balance / 100, $currency->value), 'reference' => $invoice->getReference()]));
            }
        }

        if ($total > $availableInCents) {
            $validator->errors()->add($amountKey, __('The invoices take :total, more than the :available there is to apply.', [
                'total' => Number::currency($total / 100, $currency->value),
                'available' => Number::currency($availableInCents / 100, $currency->value),
            ]));
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function arrayOf(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
