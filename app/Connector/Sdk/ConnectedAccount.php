<?php

declare(strict_types=1);

namespace App\Connector\Sdk;

/**
 * What a form-connected type says to save once the service let it in.
 */
final readonly class ConnectedAccount
{
    /**
     * @param  string  $account  which account this is, such as its address; the same account again changes the same connector
     * @param  string  $name  what people see the connector as
     * @param  array<string, mixed>  $settings  what to keep that is not secret; it is sent back to the form when the connector is changed
     * @param  array<string, mixed>  $credentials  what to keep encrypted; it never leaves the server
     */
    public function __construct(
        public string $account,
        public string $name,
        public array $settings,
        public array $credentials,
    ) {}
}
