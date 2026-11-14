<?php

declare(strict_types=1);

use App\Shared\MorphMapKeyEnum;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Every Eloquent model class under app/, found from its *EloquentModel.php file.
 *
 * @return array<string, class-string<Model>>
 */
function eloquentModelClasses(): array
{
    return collect(glob(__DIR__.'/../*/*EloquentModel.php') ?: [])
        ->mapWithKeys(function (string $path): array {
            $class = 'App\\'.basename(dirname($path)).'\\'.basename($path, '.php');

            return [$class => $class];
        })
        ->all();
}

it('requires a morph map', function (): void {
    expect(Relation::requiresMorphMap())->toBeTrue();
});

it('gives every eloquent model a morph alias', function (string $model): void {
    expect(Relation::getMorphAlias($model))->not->toBe($model);
})->with(eloquentModelClasses());

it('keeps the stored morph aliases unchanged', function (): void {
    // These values are stored in polymorphic columns. Add new aliases, never rename existing ones.
    expect(MorphMapKeyEnum::values())->toBe(['activity', 'audit_log', 'passkey', 'team', 'team_user', 'user']);
});

it('maps every alias to its model and back', function (MorphMapKeyEnum $key): void {
    expect(Relation::getMorphedModel($key->value))->toBe($key->modelClass())
        ->and(MorphMapKeyEnum::fromModel(new ($key->modelClass())))->toBe($key);
})->with(MorphMapKeyEnum::cases());

it('throws for a model without a morph alias', function (): void {
    $unmapped = new class extends Model {};

    $unmapped->getMorphClass();
})->throws(ClassMorphViolationException::class);
