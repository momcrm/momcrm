<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Behind a load balancer or a host's own proxy, the address, scheme and
    | client IP of a request arrive in `X-Forwarded-*` headers. Laravel only
    | believes them from the proxies named here: `*` for whatever is in front
    | of the app, or a comma-separated list of addresses and ranges.
    |
    | Leave it empty when people reach the app directly, or anyone could
    | claim another IP address by sending the headers themselves.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
