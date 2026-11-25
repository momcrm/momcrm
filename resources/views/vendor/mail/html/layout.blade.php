<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ $title ?? config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;600&family=Geist+Mono:wght@500&display=swap" rel="stylesheet">
<style>
@media only screen and (max-width: 600px) {
.inner-body,
.header-inner,
.footer {
width: 100% !important;
}

.header {
padding: 24px 16px 16px !important;
}

.body {
padding: 0 12px !important;
}

.content-cell {
padding: 28px 24px !important;
}

.footer .content-cell {
padding: 20px 24px 32px !important;
}
}

@media only screen and (max-width: 500px) {
.button {
display: block !important;
text-align: center !important;
}
}

@media (prefers-color-scheme: dark) {
body,
.wrapper,
.body {
background-color: #1a1a19 !important;
}

.inner-body {
background-color: #232321 !important;
border-color: #34332f !important;
}

h1,
h2,
h3,
.brand-name,
.highlight-value {
color: #f2f1ee !important;
}

p,
.panel-content,
.panel-content p,
.table td {
color: #d6d3cd !important;
}

.highlight-content,
.panel-content {
background-color: #2b2a27 !important;
border-color: #34332f !important;
}

.highlight-detail,
.subcopy p {
color: #a29e96 !important;
}

.subcopy {
border-top-color: #34332f !important;
}

.footer p,
.footer a,
.highlight-label {
color: #8c8880 !important;
}

a {
color: #8e9af3 !important;
}

a.button {
color: #ffffff !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
