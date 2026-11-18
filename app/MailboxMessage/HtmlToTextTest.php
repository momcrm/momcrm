<?php

declare(strict_types=1);

use App\MailboxMessage\HtmlToText;

it('keeps the words and line breaks of an HTML email', function (): void {
    $html = <<<'HTML'
        <html>
          <head><title>Printer</title><style>p { color: red; }</style></head>
          <body>
            <!-- tracking -->
            <p>Hi&nbsp;team,</p>
            <p>The printer on level&nbsp;2 is <b>offline</b> &amp; out of toner.<br>Can someone come?</p>
            <ul><li>Tried turning it off</li><li>Tried a new cable</li></ul>
            <script>alert('hi')</script>
            <div>Kim</div>
          </body>
        </html>
        HTML;

    expect(HtmlToText::convert($html))->toBe(implode("\n", [
        'Hi team,',
        '',
        'The printer on level 2 is offline & out of toner.',
        'Can someone come?',
        '',
        '- Tried turning it off',
        '- Tried a new cable',
        '',
        'Kim',
    ]));
});

it('never lets markup through as text', function (): void {
    expect(HtmlToText::convert('<p>Hello <a href="https://example.test" onclick="steal()">there</a></p><img src=x onerror=alert(1)>'))
        ->toBe('Hello there');
});
