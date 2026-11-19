<?php

declare(strict_types=1);

namespace App\InvoiceItem\Actions;

use App\Invoice\Invoice;
use App\Invoice\Properties\IssuedOn;
use App\Invoice\Properties\Number;
use App\InvoiceItem\InvoiceItem;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\InvoiceItemRelationship;
use App\InvoiceItem\Properties\ServiceId;
use App\InvoiceItem\Properties\TeamId;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;

final class FindInvoiceItem
{
    /**
     * The invoice lines that billed a service, newest invoice first, each
     * with the invoice it is on.
     *
     * @return list<array{id: string, service_id: ?string, description: string, quantity: int, unit_price_in_cents: int, line_total_in_cents: int, invoice: array{id: string, number: int, reference: string, status: string, issued_on: ?string}}>
     */
    public static function forService(string $serviceId, string $teamId): array
    {
        $invoices = Table::Invoices->value;

        return array_values(InvoiceItemEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->with(InvoiceItemRelationship::Invoice->value)
            ->where(TeamId::getDatabaseTableColumnNameWithTable(Table::InvoiceItems->value), $teamId)
            ->where(ServiceId::getDatabaseTableColumnNameWithTable(Table::InvoiceItems->value), $serviceId)
            ->join($invoices, "{$invoices}.id", '=', Table::InvoiceItems->value.'.invoice_id')
            ->orderByDesc(IssuedOn::getDatabaseTableColumnNameWithTable($invoices))
            ->orderByDesc(Number::getDatabaseTableColumnNameWithTable($invoices))
            ->select(Table::InvoiceItems->value.'.*')
            ->get()
            ->map(function (InvoiceItemEloquentModel $model): array {
                $invoice = Invoice::fromEloquentModel($model->invoice);

                return [
                    ...InvoiceItem::fromEloquentModel($model)->toSummary(),
                    'invoice' => [
                        'id' => $invoice->getId()->toString(),
                        'number' => $invoice->getNumber()->toInteger(),
                        'reference' => $invoice->getReference(),
                        'status' => $invoice->getStatus()->toString(),
                        'issued_on' => $invoice->getIssuedOn()->toNullableCarbon()?->toDateString(),
                    ],
                ];
            })
            ->all());
    }
}
