@props(['label', 'value', 'detail' => null, 'isCode' => false])
{{-- The one thing the email is about, such as a sign-in code or an amount owed. --}}
<table class="highlight" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="highlight-content">
<div class="highlight-label">{{ $label }}</div>
<div @class(['highlight-value', 'highlight-code' => $isCode])>{{ $value }}</div>
@if ($detail !== null)
<div class="highlight-detail">{{ $detail }}</div>
@endif
</td>
</tr>
</table>
