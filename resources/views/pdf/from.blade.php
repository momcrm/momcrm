{{-- Who a PDF is from: the team's billing name, tax ID and contact details. --}}
@php /** @var \App\Team\Team $team */ @endphp
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
