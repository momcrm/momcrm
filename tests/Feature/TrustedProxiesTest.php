<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

const FORWARDED_BY_A_PROXY = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'];

beforeEach(function (): void {
    Route::get('seen-request', fn (Request $request): string => $request->getScheme().' '.$request->ip());
});

it('ignores forwarded headers when no proxy is trusted', function (): void {
    Config::set('trustedproxy.proxies', null);

    get('http://localhost/seen-request', FORWARDED_BY_A_PROXY)->assertContent('http 127.0.0.1');
});

it('takes the scheme and client address from a trusted proxy', function (string $proxies): void {
    Config::set('trustedproxy.proxies', $proxies);

    get('http://localhost/seen-request', FORWARDED_BY_A_PROXY)->assertContent('https 203.0.113.9');
})->with([
    'any proxy' => '*',
    'a listed range' => '10.0.0.0/8, 127.0.0.1',
]);

it('ignores forwarded headers from a proxy that is not listed', function (): void {
    Config::set('trustedproxy.proxies', '10.0.0.0/8');

    get('http://localhost/seen-request', FORWARDED_BY_A_PROXY)->assertContent('http 127.0.0.1');
});
