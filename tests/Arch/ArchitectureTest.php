<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/*
|--------------------------------------------------------------------------
| Domain discovery
|--------------------------------------------------------------------------
|
| Every folder under app/ is a domain except the framework layers below.
| New domains are picked up automatically, so these rules cover them from
| the moment they are created.
|
*/

const FRAMEWORK_LAYERS = ['Console', 'Http', 'Providers'];

const PROJECT_ROOT = __DIR__.'/../..';

const APP_ROOT = PROJECT_ROOT.'/app';

/**
 * @return array<int, string>
 */
function domains(): array
{
    return collect(glob(APP_ROOT.'/*', GLOB_ONLYDIR) ?: [])
        ->map(fn (string $path): string => basename($path))
        ->reject(fn (string $name): bool => in_array($name, FRAMEWORK_LAYERS, true))
        ->values()
        ->all();
}

/**
 * Namespaces of a sub-folder (e.g. "Controllers") in every domain that has one.
 *
 * @return array<int, string>
 */
function domainNamespaces(string $folder): array
{
    return collect(domains())
        ->filter(fn (string $domain): bool => is_dir(APP_ROOT."/{$domain}/{$folder}"))
        ->map(fn (string $domain): string => "App\\{$domain}\\{$folder}")
        ->values()
        ->all();
}

/**
 * Class names of files matching a pattern at the root of every domain.
 *
 * @return array<int, string>
 */
function domainRootClasses(string $pattern): array
{
    return collect(glob(APP_ROOT."/*/{$pattern}") ?: [])
        ->reject(fn (string $path): bool => str_ends_with($path, 'Test.php'))
        ->map(fn (string $path): string => 'App\\'.basename(dirname($path)).'\\'.basename($path, '.php'))
        ->values()
        ->all();
}

/*
|--------------------------------------------------------------------------
| General
|--------------------------------------------------------------------------
*/

arch()->preset()->php();

arch()->preset()->security();

arch('domain code uses strict types')
    ->expect(collect(domains())->map(fn (string $domain): string => "App\\{$domain}")->all())
    ->toUseStrictTypes();

test('the starter kit layers stay empty', function (string $legacyFolder): void {
    expect(APP_ROOT.'/'.$legacyFolder)->not->toBeDirectory();
})->with([
    'app/Models' => 'Models',
    'app/Actions' => 'Actions',
    'app/Concerns' => 'Concerns',
    'app/Http/Controllers' => 'Http/Controllers',
    'app/Http/Requests' => 'Http/Requests',
]);

/*
|--------------------------------------------------------------------------
| Domain building blocks
|--------------------------------------------------------------------------
*/

arch('controllers are final, invokable and suffixed')
    ->expect(domainNamespaces('Controllers'))
    ->toBeFinal()
    ->toBeInvokable()
    ->toHaveSuffix('Controller');

arch('form requests are final, extend FormRequest and are suffixed')
    ->expect(domainNamespaces('FormRequests'))
    ->toBeFinal()
    ->toExtend(FormRequest::class)
    ->toHaveSuffix('FormRequest');

arch('actions are final')
    ->expect(domainNamespaces('Actions'))
    ->toBeFinal();

arch('properties are final mom/data values')
    ->expect(domainNamespaces('Properties'))
    ->toBeFinal()
    ->toExtend(AbstractValue::class);

arch('domain DTOs are final mom/data classes')
    ->expect(collect(domains())
        ->map(fn (string $domain): string => "App\\{$domain}\\{$domain}")
        ->filter(fn (string $class): bool => class_exists($class))
        ->values()
        ->all())
    ->toBeFinal()
    ->toExtend(AbstractData::class);

arch('eloquent models are final')
    ->expect(domainRootClasses('*EloquentModel.php'))
    ->toBeFinal()
    ->toExtend(Model::class);

arch('enums are string-backed')
    ->expect([
        ...domainRootClasses('*Enum.php'),
        ...domainRootClasses('*Relationship.php'),
        'App\Shared\Table',
    ])
    ->toBeStringBackedEnums();

/*
|--------------------------------------------------------------------------
| Co-located tests
|--------------------------------------------------------------------------
*/

test('every controller has a test beside it', function (string $controller): void {
    expect(dirname($controller).'/'.basename($controller, '.php').'Test.php')->toBeFile();
})->with(fn (): array => collect(glob(APP_ROOT.'/*/Controllers/*Controller.php') ?: [])
    ->mapWithKeys(fn (string $path): array => [str_replace(APP_ROOT.'/', 'app/', $path) => $path])
    ->all());
