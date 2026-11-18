<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$sample = <<<'ENV'
# App
APP_NAME="MomCRM Staff"
APP_DEBUG=true

export MAIL_HOST=smtp.example.com # outbound
MAIL_PASSWORD='p@ss #1'
PRIVATE_KEY="-----BEGIN KEY-----
abc
-----END KEY-----"
APP_URL=${APP_NAME}/x
MAIL_HOST=ignored-duplicate

ENV;

it('reads variables in file order, decoded, first definition only', function () use ($sample): void {
    expect(temporaryEnvironmentFile($sample)->variables()->all())->toBe([
        ['key' => 'APP_NAME', 'value' => 'MomCRM Staff'],
        ['key' => 'APP_DEBUG', 'value' => 'true'],
        ['key' => 'MAIL_HOST', 'value' => 'smtp.example.com'],
        ['key' => 'MAIL_PASSWORD', 'value' => 'p@ss #1'],
        ['key' => 'PRIVATE_KEY', 'value' => "-----BEGIN KEY-----\nabc\n-----END KEY-----"],
        ['key' => 'APP_URL', 'value' => '${APP_NAME}/x'],
    ]);
});

it("rewrites only a set variable's line and drops later duplicates", function () use ($sample): void {
    $file = temporaryEnvironmentFile($sample);

    $file->set('MAIL_HOST', 'mail.example.org');

    expect(file_get_contents($file->path))->toBe(<<<'ENV'
# App
APP_NAME="MomCRM Staff"
APP_DEBUG=true

MAIL_HOST=mail.example.org
MAIL_PASSWORD='p@ss #1'
PRIVATE_KEY="-----BEGIN KEY-----
abc
-----END KEY-----"
APP_URL=${APP_NAME}/x

ENV);
});

it('replaces a multiline value whole', function () use ($sample): void {
    $file = temporaryEnvironmentFile($sample);

    $file->set('PRIVATE_KEY', 'short');

    expect(file_get_contents($file->path))->toContain("PRIVATE_KEY=short\nAPP_URL=")
        ->and(file_get_contents($file->path))->not->toContain('BEGIN KEY');
});

it('appends a new variable', function (): void {
    $file = temporaryEnvironmentFile("APP_NAME=x\n");

    $file->set('NEW_KEY', 'value');

    expect(file_get_contents($file->path))->toBe("APP_NAME=x\nNEW_KEY=value\n");
});

it('removes every definition of an unset variable and keeps the rest', function () use ($sample): void {
    $file = temporaryEnvironmentFile($sample);

    $file->unset('MAIL_HOST');

    expect($file->has('MAIL_HOST'))->toBeFalse()
        ->and(file_get_contents($file->path))->toContain("APP_DEBUG=true\n\nMAIL_PASSWORD=");
});

it('writes values that phpdotenv reads back the same', function (string $value): void {
    $file = temporaryEnvironmentFile("APP_NAME=x\n");

    $file->set('VALUE', $value);

    expect(Dotenv::parse((string) file_get_contents($file->path))['VALUE'])->toBe($value)
        ->and($file->get('VALUE'))->toBe($value);
})->with([
    'plain' => 'smtp.example.com',
    'empty' => '',
    'spaces' => 'MomCRM Staff',
    'hash' => 'a #b',
    'quotes' => 'say "hi" it\'s',
    'backslashes' => 'C:\\path\\"x\\',
    'dollar' => 'pa$$word',
    'newline' => "line one\nline two",
    'url' => 'https://user:pass@example.com:8080/a?b=c&d=e',
]);

it('backs the old file up on each change, keeping the newest few', function (): void {
    $file = temporaryEnvironmentFile("A=1\n", backupsKept: 2);

    $file->set('A', '2');
    usleep(1000);
    $file->set('A', '3');
    usleep(1000);
    $file->set('A', '4');

    $backups = glob($file->backupDirectory.'/.env.*') ?: [];
    sort($backups);

    expect($backups)->toHaveCount(2)
        ->and(file_get_contents($backups[0]))->toBe("A=2\n")
        ->and(file_get_contents($backups[1]))->toBe("A=3\n")
        ->and(decoct(fileperms($backups[1]) & 0777))->toBe('600');
});

it("keeps the file's windows line endings", function (): void {
    $file = temporaryEnvironmentFile("A=1\r\nB=2\r\n");

    $file->set('B', '3');

    expect(file_get_contents($file->path))->toBe("A=1\r\nB=3\r\n");
});
