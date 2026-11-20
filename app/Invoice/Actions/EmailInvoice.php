<?php

declare(strict_types=1);

namespace App\Invoice\Actions;

use App\Invoice\Invoice;
use App\Invoice\InvoiceDocument;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Notifications\InvoiceNotification;
use App\Invoice\Properties\EmailedAt;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\Notification;

/**
 * Emails an invoice to the addresses given, with its PDF. A draft is marked
 * sent first, as it is then owed, so the customer's credit goes to it.
 */
final readonly class EmailInvoice
{
    /**
     * @param  list<string>  $emails
     */
    public function __construct(
        private Invoice $invoice,
        private array $emails,
    ) {}

    public function __invoke(): Invoice
    {
        $invoice = $this->invoice->getStatus()->toEnum() === InvoiceStatusEnum::Draft
            ? ChangeInvoiceStatus::to($this->invoice, InvoiceStatusEnum::Sent)
            : $this->invoice;

        Notification::route('mail', $this->emails)->notify(new InvoiceNotification(InvoiceDocument::for($invoice)));

        InvoiceEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($invoice->getId()->toString())
            ?->update([EmailedAt::getDatabaseTableColumnName() => now()]);

        return $invoice;
    }

    /**
     * @param  list<string>  $emails
     */
    public static function to(Invoice $invoice, array $emails): Invoice
    {
        return new self($invoice, $emails)->handle();
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
