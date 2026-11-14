---
paths:
    - 'app/**/*Test.php'
    - 'tests/**'
---

# Tests

## Put tests beside the code they test

A test for a class in `app/` lives in the same folder, named after it: `app/Team/Controllers/UpdateTeamController.php` → `app/Team/Controllers/UpdateTeamControllerTest.php`.

Do not use `php artisan make:test` for these — it writes to `tests/`. Create the file directly.

## tests/ holds only what has no class of ours to sit beside

`tests/` keeps `TestCase.php`, `Pest.php`, architecture tests, and tests of Fortify's routes (login, two-factor, password reset, email verification). Do not add tests there for code under `app/`.

## Write co-located tests in Pest's functional style

Use `it('…', function (): void { … })` with no namespace and no class. Start the file with `declare(strict_types=1);`. Prefer the `Pest\Laravel` functions (`actingAs`, `get`, `put`, …) and `expect()`.

`RefreshDatabase` and `TestCase` are applied to `app/` in `tests/Pest.php` — do not add `uses()` or the trait in the file.

Build records with the EloquentModel factories, e.g. `UserEloquentModel::factory()->withPersonalTeam()->create()`.
