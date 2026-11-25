@props(['url' => null, 'sender' => null])
{{-- The team's name when a team sends the email, otherwise the app's logo and name. --}}
<tr>
<td class="header" align="center">
<table class="header-inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
@if ($sender === null)
<td width="28">
<a href="{{ $url }}"><img src="{{ asset('apple-touch-icon.png') }}" class="logo" width="28" height="28" alt=""></a>
</td>
<td class="brand-name">
<a href="{{ $url }}" style="color: #1d1c1a;">{{ config('app.name') }}</a>
</td>
@else
<td class="monogram" width="28" height="28">{{ mb_strtoupper(mb_substr(trim($sender), 0, 1)) }}</td>
<td class="brand-name">{{ $sender }}</td>
@endif
</tr>
</table>
</td>
</tr>
