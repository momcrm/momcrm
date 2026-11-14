<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('skips boost:update when Boost is not set up', function (): void {
    $basePath = sys_get_temp_dir().'/momcrm-no-boost-'.uniqid();
    mkdir($basePath);
    app()->setBasePath($basePath);

    artisan('app:boost-update')
        ->expectsOutputToContain('Laravel Boost is not set up here; skipping boost:update.')
        ->assertSuccessful();

    rmdir($basePath);
});
