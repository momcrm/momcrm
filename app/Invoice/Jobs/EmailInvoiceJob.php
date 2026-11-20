<?php

declare(strict_types=1);

namespace App\Invoice\Jobs;

use App\Invoice\Actions\EmailInvoice;
use App\Invoice\Actions\FindInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Emails one invoice from a bulk send, off the request, so sending many
 * doesn't wait on each PDF. Not retried, so nobody gets it twice.
 */
final class EmailInvoiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * @param  list<string>  $emails
     */
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $teamId,
        public readonly array $emails,
    ) {}

    public function handle(): void
    {
        $invoice = FindInvoice::byIdForTeam($this->invoiceId, $this->teamId);

        if ($invoice->isNotNull()) {
            EmailInvoice::to($invoice, $this->emails);
        }
    }
}
