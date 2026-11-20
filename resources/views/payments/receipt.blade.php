@php
    /** @var \App\Payment\PaymentDocument $document */
    $payment = $document->payment;
    $customer = $document->customer;
    $team = $document->team;
    $date = fn ($value): string => $value->isNull() ? '—' : $value->toCarbon()->format('j M Y');
    $currency = $payment->getCurrency()->toEnum()->value;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $payment->getReferenceNumber() }}</title>
    @include('pdf.styles')
</head>
<body>
    <div class="accent-bar"></div>

    <div class="footer">
        <table>
            <tr>
                <td>{{ $team->getNameOnInvoices() }}@if ($team->getTaxId()->isNotNull()) · {{ $team->getTaxIdLabel()->toString() }} {{ $team->getTaxId()->toString() }}@endif</td>
                <td style="text-align: right;">{{ $payment->getReferenceNumber() }} · Paid {{ $date($payment->getPaidOn()) }}</td>
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
                    <div class="title">RECEIPT</div>
                    <div class="reference">{{ $payment->getReferenceNumber() }}</div>
                    <div class="stamp stamp-paid">Paid</div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            <tr>
                <td style="width: 50%; padding-right: 24px;">
                    <div class="label">Received from</div>
                    <div class="bill-to-name">{{ $customer->getName()->toString() }}</div>
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
                                <td class="muted">Paid on</td>
                                <td class="value">{{ $date($payment->getPaidOn()) }}</td>
                            </tr>
                            <tr>
                                <td class="muted">Method</td>
                                <td class="value">{{ $payment->getMethod()->toEnum()->label() }}</td>
                            </tr>
                            @if ($payment->getReference()->isNotNull())
                                <tr>
                                    <td class="muted">Reference</td>
                                    <td class="value">{{ $payment->getReference()->toString() }}</td>
                                </tr>
                            @endif
                        </table>
                        <div class="divider" style="margin: 10px 0;"></div>
                        <table>
                            <tr>
                                <td class="label" style="vertical-align: bottom; padding-bottom: 4px;">Amount received</td>
                                <td class="amount" style="text-align: right;">{{ $document->money($payment->getAmountInCents()->toInteger()) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="first">Applied to</th>
                    <th class="num last" style="width: 25%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($document->allocations as $allocation)
                    <tr>
                        <td class="first">Invoice {{ $allocation->getInvoice()->toNullableData()?->getReference() }}</td>
                        <td class="num last">{{ $document->money($allocation->getAmountInCents()->toInteger()) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="first muted">Kept as credit for future invoices.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="muted">Applied to invoices</td>
                <td class="value">{{ $document->money($payment->getAllocatedInCents()->toInteger()) }}</td>
            </tr>
            <tr>
                <td class="muted">Kept as credit</td>
                <td class="value">{{ $document->money($payment->getUnallocatedInCents()) }}</td>
            </tr>
            <tr class="total">
                <td>Total {{ $currency }}</td>
                <td class="value">{{ $document->money($payment->getAmountInCents()->toInteger()) }}</td>
            </tr>
        </table>

        @if ($document->customerCreditInCents > 0)
            <div class="payment">
                <div class="label">Your credit</div>
                <p>You have {{ $document->money($document->customerCreditInCents) }} in credit with us. It will be used on your next invoices.</p>
            </div>
        @endif

        @if ($payment->getNotes()->isNotNull())
            <div class="notes">
                <div class="label">Notes</div>
                <p>{{ $payment->getNotes()->toString() }}</p>
            </div>
        @endif
    </div>
</body>
</html>
