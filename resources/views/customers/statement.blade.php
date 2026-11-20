@php
    /** @var \App\Customer\CustomerStatement $statement */
    $customer = $statement->customer;
    $team = $statement->team;
    $day = fn (string $date): string => \Carbon\CarbonImmutable::parse($date)->format('j M Y');
    $currency = $customer->getCurrency()->toEnum()->value;
    $closing = $statement->closingBalanceInCents;
    $signed = fn (int $cents): string => $cents < 0 ? $statement->money(-$cents).' CR' : $statement->money($cents);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement {{ $customer->getName()->toString() }}</title>
    @include('pdf.styles')
    <style>
        .ages { margin-top: 28px; }
        .ages td { padding: 8px 10px; text-align: right; border: 1px solid #ecebe7; }
        .ages .head td { font-size: 7pt; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #8b877f; background: #f6f5f2; }
    </style>
</head>
<body>
    <div class="accent-bar"></div>

    <div class="footer">
        <table>
            <tr>
                <td>{{ $team->getNameOnInvoices() }}@if ($team->getTaxId()->isNotNull()) · {{ $team->getTaxIdLabel()->toString() }} {{ $team->getTaxId()->toString() }}@endif</td>
                <td style="text-align: right;">Statement · {{ $statement->from->format('j M Y') }} – {{ $statement->to->format('j M Y') }}</td>
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
                    <div class="title">STATEMENT</div>
                    <div class="reference">{{ $statement->from->format('j M Y') }} – {{ $statement->to->format('j M Y') }}</div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            <tr>
                <td style="width: 50%; padding-right: 24px;">
                    <div class="label">Account of</div>
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
                                <td class="muted">Opening balance</td>
                                <td class="value">{{ $signed($statement->openingBalanceInCents) }}</td>
                            </tr>
                            @if ($statement->creditInCents > 0)
                                <tr>
                                    <td class="muted">Credit available</td>
                                    <td class="value">{{ $statement->money($statement->creditInCents) }}</td>
                                </tr>
                            @endif
                        </table>
                        <div class="divider" style="margin: 10px 0;"></div>
                        <table>
                            <tr>
                                <td class="label" style="vertical-align: bottom; padding-bottom: 4px;">{{ $closing < 0 ? 'In credit' : 'Balance owing' }}</td>
                                <td class="amount" style="text-align: right;">{{ $statement->money(abs($closing)) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="first" style="width: 16%;">Date</th>
                    <th>Details</th>
                    <th class="num" style="width: 15%;">Charged</th>
                    <th class="num" style="width: 15%;">Paid</th>
                    <th class="num last" style="width: 17%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="first muted">{{ $statement->from->format('j M Y') }}</td>
                    <td class="muted">Opening balance</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num last">{{ $signed($statement->openingBalanceInCents) }}</td>
                </tr>
                @foreach ($statement->entries as $entry)
                    <tr>
                        <td class="first">{{ $day($entry['date']) }}</td>
                        <td>{{ $entry['description'] }}</td>
                        <td class="num">{{ $entry['debit_in_cents'] > 0 ? $statement->money($entry['debit_in_cents']) : '' }}</td>
                        <td class="num">{{ $entry['credit_in_cents'] > 0 ? $statement->money($entry['credit_in_cents']) : '' }}</td>
                        <td class="num last">{{ $signed($entry['balance_in_cents']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr class="total">
                <td>{{ $closing < 0 ? 'In credit' : 'Balance owing' }} {{ $currency }}</td>
                <td class="value">{{ $statement->money(abs($closing)) }}</td>
            </tr>
        </table>

        <table class="ages">
            <tr class="head">
                @foreach (\App\Customer\CustomerStatement::AGES as $label)
                    <td>{{ $label }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach (array_keys(\App\Customer\CustomerStatement::AGES) as $key)
                    <td>{{ $statement->money($statement->ages[$key] ?? 0) }}</td>
                @endforeach
            </tr>
        </table>

        @if ($team->getPaymentDetails()->isNotNull() && $closing > 0)
            <div class="payment">
                <div class="label">How to pay</div>
                <p>{{ $team->getPaymentDetails()->toString() }}</p>
            </div>
        @endif
    </div>
</body>
</html>
