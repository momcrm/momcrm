<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceStatusEnum;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Moves an invoice along draft → sent → paid, or voids it, stamping when it
 * was sent or paid.
 */
final readonly class ChangeInvoiceStatus
{
    public function __construct(
        private Invoice $invoice,
        private InvoiceStatusEnum $status,
    ) {}

    public function __invoke(): Invoice
    {
        if (! $this->invoice->getStatus()->toEnum()->canBecome($this->status)) {
            throw new InvalidArgumentException("A {$this->invoice->getStatus()->toString()} invoice cannot become {$this->status->value}.");
        }

        $this->invoice->setStatus($this->status);

        match ($this->status) {
            InvoiceStatusEnum::Sent => $this->invoice->setSentAt(CarbonImmutable::now()),
            InvoiceStatusEnum::Paid => $this->invoice->setPaidAt(CarbonImmutable::now()),
            default => null,
        };

        return UpdateInvoice::fromInvoice($this->invoice);
    }

    public static function to(Invoice $invoice, InvoiceStatusEnum $status): Invoice
    {
        return new self($invoice, $status)->handle();
    }

    public function handle(): Invoice
    {
        return $this->__invoke();
    }

    public function execute(): Invoice
    {
        return $this->__invoke();
    }
}
