<?php

declare(strict_types=1);

use App\Shared\BrandedMailMessage;
use Illuminate\Mail\Markdown;

it("carries the app's logo and name when no team sends it", function (): void {
    $html = (string) new BrandedMailMessage()->line('Welcome.')->render();

    expect($html)
        ->toContain(asset('apple-touch-icon.png'))
        ->toContain('© '.date('Y').' <a href="'.config('app.url').'"')
        ->not->toContain('Sent by');
});

it("puts the team's name in the header, footer and sign-off when a team sends it", function (): void {
    $html = (string) new BrandedMailMessage()->sentBy('Acme Support')->line('Your invoice is attached.')->render();

    expect($html)
        ->not->toContain(asset('apple-touch-icon.png'))
        ->toContain('Acme Support</td>')
        ->toContain('Sent by Acme Support with '.config('app.name'))
        ->toContain('Acme Support</p>');
});

it('shows the highlighted value under the greeting, in the HTML and the plain text', function (): void {
    $mail = new BrandedMailMessage()
        ->greeting('Your statement')
        ->highlight('Balance owing', 'A$875.00', '1 Oct 2026 to 25 Nov 2026')
        ->line('The statement is attached.');

    $text = (string) app(Markdown::class)->renderText('notifications::email', $mail->data());

    expect((string) $mail->render())
        ->toMatch('/Your statement<\/h1>\s*<table class="highlight"/')
        ->toContain('A$875.00</div>')
        ->toContain('1 Oct 2026 to 25 Nov 2026</div>')
        ->and($text)->toContain("Balance owing: A$875.00\n1 Oct 2026 to 25 Nov 2026");
});
