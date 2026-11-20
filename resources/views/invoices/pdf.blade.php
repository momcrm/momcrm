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
    @include('pdf.styles')
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
                    @include('pdf.from', ['team' => $team])
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
                    @if ($customer->taxIdLine() !== null)
                        <div class="muted">{{ $customer->taxIdLine() }}</div>
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
                                <td class="amount" style="text-align: right;">{{ $document->money($status === \App\Invoice\InvoiceStatusEnum::Paid ? $document->totals->totalInCents : $invoice->balanceAfter($document->totals)) }}</td>
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
            @if ($invoice->getPaidInCents()->toInteger() > 0)
                <tr>
                    <td class="muted">Paid</td>
                    <td class="value">&minus;{{ $document->money($invoice->getPaidInCents()->toInteger()) }}</td>
                </tr>
                <tr>
                    <td><strong>Balance due</strong></td>
                    <td class="value"><strong>{{ $document->money($invoice->balanceAfter($document->totals)) }}</strong></td>
                </tr>
            @endif
        </table>

        @if (($team->getPaymentDetails()->isNotNull() || $document->bpay() !== null) && $status !== \App\Invoice\InvoiceStatusEnum::Paid && $status !== \App\Invoice\InvoiceStatusEnum::Void)
            <div class="payment">
                <div class="label">How to pay</div>
                @if ($document->bpay() !== null)
                    <p><strong>BPAY</strong> &middot; Biller code {{ $document->bpay()['biller_code'] }} &middot; Ref {{ $document->bpay()['reference'] }}</p>
                @endif
                @if ($team->getPaymentDetails()->isNotNull())
                    <p>{{ $team->getPaymentDetails()->toString() }}</p>
                @endif
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
