<?php

declare(strict_types=1);

use App\Shared\Types;

it('passes through or converts values of the expected type', function (): void {
    expect(Types::string('a'))->toBe('a')
        ->and(Types::string(5))->toBe('5')
        ->and(Types::nullableString(null))->toBeNull()
        ->and(Types::int('42'))->toBe(42)
        ->and(Types::int(3.0))->toBe(3)
        ->and(Types::intOrZero(null))->toBe(0)
        ->and(Types::stringList(collect(['a', 1])))->toBe(['a', '1'])
        ->and(Types::stringKeyed(['name' => 'x']))->toBe(['name' => 'x']);
});

it('refuses values of another type', function (Closure $narrow): void {
    expect($narrow)->toThrow(UnexpectedValueException::class);
})->with([
    'array as string' => [fn () => Types::string(['a'])],
    'null as string' => [fn () => Types::string(null)],
    'fraction as int' => [fn () => Types::int(1.5)],
    'word as int' => [fn () => Types::int('ten')],
    'scalar as list' => [fn () => Types::stringList('a')],
    'list as keyed' => [fn () => Types::stringKeyed(['a'])],
]);
