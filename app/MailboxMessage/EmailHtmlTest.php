<?php

declare(strict_types=1);

use App\MailboxMessage\EmailHtml;

it('keeps how an email is laid out and what it says', function (): void {
    $page = EmailHtml::toPage('<html><body><table width="600" bgcolor="#ffffff"><tr><td style="padding:20px"><h1>Welcome</h1><p>Please <b>sign in</b>.</p><img src="https://acme.test/logo.png" alt="Acme"></td></tr></table></body></html>');

    expect($page)
        ->toStartWith('<!DOCTYPE html>')
        ->toContain('<table width="600" bgcolor="#ffffff">', '<td style="padding:20px">', '<h1>Welcome</h1>', '<b>sign in</b>', 'src="https://acme.test/logo.png"', 'alt="Acme"');
});

it('takes out whatever does something', function (string $html, string $gone): void {
    expect(EmailHtml::toPage("<p>Hello</p>{$html}"))
        ->toContain('<p>Hello</p>')
        ->not->toContain($gone);
})->with([
    'a script' => ['<script>alert(1)</script>', 'alert(1)'],
    'a handler' => ['<p onclick="alert(1)">Click</p>', 'onclick'],
    'a frame' => ['<iframe src="https://evil.test"></iframe>', 'iframe'],
    'an embedded object' => ['<object data="https://evil.test/x.swf"></object>', 'object'],
    'a form' => ['<form action="https://evil.test"><input name="password"></form>', '<form'],
    'a script link' => ['<a href="javascript:alert(1)">Open</a>', 'javascript:'],
    'a redirect' => ['<meta http-equiv="refresh" content="0;url=https://evil.test">', 'refresh'],
    'a base address' => ['<base href="https://evil.test/">', '<base'],
    'a style sheet from elsewhere' => ['<link rel="stylesheet" href="https://evil.test/a.css">', 'evil.test'],
]);

it('opens every link in a new tab, cut off from the page it came from', function (): void {
    expect(EmailHtml::toPage('<a href="https://acme.test/in?token=abc" target="_top" rel="opener">Sign in</a>'))
        ->toContain('target="_blank"', 'rel="noopener noreferrer"', 'acme.test/in')
        ->not->toContain('_top');
});

it('keeps the email\'s own style sheets, wherever they were, and never lets one end early', function (): void {
    $page = EmailHtml::toPage('<html><head><style>@media (max-width:600px){.button > a{color:#fff}}</style></head><body><style>.note{color:red}</style><style>p{color:blue}</stylex><script>alert(1)</script></style><p class="note">Hello</p></body></html>');

    expect($page)
        ->toContain('<style>@media (max-width:600px){.button > a{color:#fff}}</style>', '<style>.note{color:red}</style>', '<p class="note">Hello</p>')
        ->not->toContain('</stylex><script>');
});

it('lets the page load nothing and run nothing, and pictures from the web only when asked', function (): void {
    expect(EmailHtml::policy())
        ->toContain("default-src 'none'", "style-src 'unsafe-inline'", 'img-src data:;', "form-action 'none'", "frame-ancestors 'self'", 'sandbox allow-popups allow-popups-to-escape-sandbox')
        ->not->toContain('https:', 'allow-scripts', 'allow-same-origin')
        ->and(EmailHtml::policy(withPictures: true))->toContain('img-src data: https:;')
        ->not->toContain('allow-scripts', 'allow-same-origin');
});
