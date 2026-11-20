@php
    /** @var \App\Invoice\InvoiceDocument $document */
    $invoice = $document->invoice;
    $customer = $document->customer;
    $status = $invoice->getStatus()->toEnum();
    $isOverdue = $invoice->isOverdue();
    $stamp = match (true) {
        $isOverdue => ['label' => 'Overdue', 'class' => 'stamp-overdue'],
        $status === \App\Invoice\InvoiceStatusEnum::Paid => ['label' => 'Paid', 'class' => 'stamp-paid'],
        $status === \App\Invoice\InvoiceStatusEnum::Void => ['label' => 'Void', 'class' => 'stamp-void'],
        $status === \App\Invoice\InvoiceStatusEnum::Draft => ['label' => 'Draft', 'class' => 'stamp-draft'],
        default => null,
    };
    $date = fn ($value): string => $value->isNull() ? '—' : $value->toCarbon()->format('j M Y');
    $taxRate = rtrim(rtrim(number_format($invoice->getTaxRateBasisPoints()->toInteger() / 100, 2), '0'), '.');
    $currency = $invoice->getCurrency()->toEnum()->value;
    $team = $document->team;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->getReference() }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #1f1d1a;
        }
        .accent-bar { height: 6px; background: #4b5bd6; }
        .page { padding: 40px 48px 120px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 0; }
        .muted { color: #77736c; }
        .label {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #8b877f;
        }
        .from-name { font-size: 17pt; font-weight: bold; letter-spacing: -0.3px; }
        .from-details { margin-top: 4px; font-size: 8.5pt; color: #77736c; }
        .tax-id { margin-top: 4px; font-size: 8.5pt; font-weight: bold; color: #1f1d1a; }
        .title { font-size: 22pt; font-weight: bold; letter-spacing: 3px; color: #4b5bd6; text-align: right; line-height: 1; }
        .reference { text-align: right; font-size: 11pt; margin-top: 6px; }
        .stamp {
            display: inline-block;
            margin-top: 10px;
            padding: 3px 10px;
            border: 1.5px solid;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .stamp-paid { color: #25754b; border-color: #25754b; }
        .stamp-overdue { color: #b23c30; border-color: #b23c30; }
        .stamp-void { color: #77736c; border-color: #77736c; }
        .stamp-draft { color: #8b877f; border-color: #c9c5bd; }
        .divider { border-top: 1px solid #e7e4de; margin: 28px 0; }
        .bill-to-name { font-size: 12pt; font-weight: bold; margin-top: 6px; }
        .meta td { padding: 3px 0; }
        .meta td.value { text-align: right; font-weight: bold; }
        .summary {
            background: #f6f5f2;
            border-radius: 8px;
            padding: 14px 18px;
        }
        .summary .amount { font-size: 18pt; font-weight: bold; letter-spacing: -0.3px; }
        .items { margin-top: 28px; }
        .items th {
            padding: 9px 10px;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #8b877f;
            border-bottom: 1.5px solid #1f1d1a;
            text-align: left;
        }
        .items td { padding: 11px 10px; border-bottom: 1px solid #ecebe7; }
        .items .num { text-align: right; white-space: nowrap; }
        .items .first { padding-left: 0; }
        .items .last { padding-right: 0; }
        .items .period { font-size: 8pt; color: #8b877f; margin-top: 2px; }
        .totals { width: 46%; margin-left: 54%; margin-top: 18px; }
        .totals td { padding: 6px 10px; }
        .totals td.value { padding-right: 0; }
        .totals td.value { text-align: right; white-space: nowrap; }
        .totals tr.total td {
            padding-top: 12px;
            padding-bottom: 12px;
            border-top: 1.5px solid #1f1d1a;
            font-size: 12pt;
            font-weight: bold;
        }
        .totals tr.total td.value { color: #4b5bd6; }
        .payment {
            margin-top: 36px;
            padding: 14px 18px;
            border: 1px solid #e7e4de;
            border-left: 3px solid #4b5bd6;
            border-radius: 6px;
        }
        .payment p { margin: 6px 0 0; white-space: pre-line; }
        .notes { margin-top: 36px; }
        .notes p { margin: 6px 0 0; white-space: pre-line; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 18px 48px 26px;
            border-top: 1px solid #e7e4de;
            font-size: 8pt;
            color: #8b877f;
        }
    </style>
</head>
<body>
    <div class="accent-bar"></div>

    <div class="footer">
        <table>
            <tr>
                <td>{{ $team->getNameOnInvoices() }}@if ($team->getTaxId()->isNotNull()) · {{ $team->getTaxIdLabel()->toString() }} {{ $team->getTaxId()->toString() }}@endif</td>
                <td style="text-align: right;">
                    {{ $invoice->getReference() }}@unless ($invoice->getDueOn()->isNull()) · Due {{ $date($invoice->getDueOn()) }}@endunless
                </td>
            </tr>
        </table>
    </div>

    <div class="page">
        <table>
            <tr>
                <td style="width: 55%;">
                    <div class="label">From</div>
                    <div class="from-name">{{ $team->getNameOnInvoices() }}</div>
                    @if ($team->getTaxId()->isNotNull())
                        <div class="tax-id">{{ $team->getTaxIdLabel()->toString() }} {{ $team->getTaxId()->toString() }}</div>
                    @endif
                    <div class="from-details">
                        @if ($team->getBillingAddress()->isNotNull())
                            <div>{!! nl2br(e($team->getBillingAddress()->toString())) !!}</div>
                        @endif
                        @if ($team->getBillingEmail()->isNotNull())
                            <div>{{ $team->getBillingEmail()->toString() }}</div>
                        @endif
                        @if ($team->getBillingPhone()->isNotNull())
                            <div>{{ $team->getBillingPhone()->toString() }}</div>
                        @endif
                    </div>
                </td>
                <td style="width: 45%; text-align: right;">
                    <div class="title">{{ mb_strtoupper($document->title()) }}</div>
                    <div class="reference">{{ $invoice->getReference() }}</div>
                    @if ($stamp)
                        <div class="stamp {{ $stamp['class'] }}">{{ $stamp['label'] }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            <tr>
                <td style="width: 50%; padding-right: 24px;">
                    <div class="label">Bill to</div>
                    <div class="bill-to-name">{{ $customer->getName()->toString() }}</div>
                    @if ($customer->getAbn()->isNotNull())
                        <div class="muted">ABN {{ $customer->getAbn()->toString() }}</div>
                    @endif
                    @if ($customer->getEmail()->isNotNull())
                        <div class="muted">{{ $customer->getEmail()->toString() }}</div>
                    @endif
                    @if ($customer->getPhone()->isNotNull())
                        <div class="muted">{{ $customer->getPhone()->toString() }}</div>
                    @endif
                </td>
                <td style="width: 50%;">
                    <div class="summary">
                        <table class="meta">
                            <tr>
                                <td class="muted">Issued</td>
                                <td class="value">{{ $date($invoice->getIssuedOn()) }}</td>
                            </tr>
                            <tr>
                                <td class="muted">Due</td>
                                <td class="value">{{ $date($invoice->getDueOn()) }}</td>
                            </tr>
                            @if ($invoice->getPurchaseOrder()->isNotNull())
                                <tr>
                                    <td class="muted">Purchase order</td>
                                    <td class="value">{{ $invoice->getPurchaseOrder()->toString() }}</td>
                                </tr>
                            @endif
                            @if ($invoice->getPaidAt()->isNotNull())
                                <tr>
                                    <td class="muted">Paid</td>
                                    <td class="value">{{ $date($invoice->getPaidAt()) }}</td>
                                </tr>
                            @endif
                        </table>
                        <div class="divider" style="margin: 10px 0;"></div>
                        <table>
                            <tr>
                                <td class="label" style="vertical-align: bottom; padding-bottom: 4px;">
                                    {{ $status === \App\Invoice\InvoiceStatusEnum::Paid ? 'Amount paid' : 'Amount due' }}
                                </td>
                                <td class="amount" style="text-align: right;">{{ $document->money($document->totals->totalInCents) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="first">Description</th>
                    <th class="num" style="width: 12%;">Qty</th>
                    <th class="num" style="width: 20%;">Unit price</th>
                    <th class="num last" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($document->items as $item)
                    <tr>
                        <td class="first">
                            {{ $item->getDescription()->toString() }}
                            @if ($item->getRentalStartsOn()->isNotNull() && $item->getRentalEndsOn()->isNotNull())
                                <div class="period">{{ $date($item->getRentalStartsOn()) }} – {{ $date($item->getRentalEndsOn()) }}</div>
                            @endif
                        </td>
                        <td class="num">{{ $item->getQuantity()->toInteger() }}</td>
                        <td class="num">{{ $document->money($item->getUnitPriceInCents()->toInteger()) }}</td>
                        <td class="num last">{{ $document->money($item->getLineTotalInCents()) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" style="text-align: center; padding: 24px;">No lines on this invoice yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="muted">Subtotal</td>
                <td class="value">{{ $document->money($document->totals->subtotalInCents) }}</td>
            </tr>
            <tr>
                <td class="muted">Tax ({{ $taxRate }}%)</td>
                <td class="value">{{ $document->money($document->totals->taxInCents) }}</td>
            </tr>
            <tr class="total">
                <td>Total {{ $currency }}</td>
                <td class="value">{{ $document->money($document->totals->totalInCents) }}</td>
            </tr>
        </table>

        @if ($team->getPaymentDetails()->isNotNull() && $status !== \App\Invoice\InvoiceStatusEnum::Paid && $status !== \App\Invoice\InvoiceStatusEnum::Void)
            <div class="payment">
                <div class="label">How to pay</div>
                <p>{{ $team->getPaymentDetails()->toString() }}</p>
            </div>
        @endif

        @if ($invoice->getNotes()->isNotNull())
            <div class="notes">
                <div class="label">Notes</div>
                <p>{{ $invoice->getNotes()->toString() }}</p>
            </div>
        @endif
    </div>
</body>
</html>
