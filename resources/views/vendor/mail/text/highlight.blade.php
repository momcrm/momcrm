@props(['label', 'value', 'detail' => null, 'isCode' => false])
{{ $label }}: {{ $value }}
@if ($detail !== null)
{{ $detail }}
@endif
