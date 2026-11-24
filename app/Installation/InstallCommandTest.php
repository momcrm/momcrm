<?php

declare(strict_types=1);

use App\Environment\EnvironmentFile;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;

/**
 * Answers the questions about the database server with the settings the tests
 * already run on, so the installer keeps using that database. SQLite has no
 * server, so the installer asks nothing about it.
 *
 * @param  array<string, string>  $answers
 */
function answerDatabaseQuestions(PendingCommand $command, array $answers = []): PendingCommand
{
    $connection = config('database.connections.'.config('database.default'));

    if ($connection['driver'] === 'sqlite') {
        return $command;
    }

    return $command
        ->expectsQuestion('Database host', $answers['host'] ?? $connection['host'])
        ->expectsQuestion('Database port', $answers['port'] ?? (string) $connection['port'])
        ->expectsQuestion('Database name', $connection['database'])
        ->expectsQuestion('Database username', $connection['username'])
        ->expectsQuestion('Database password', '');
}

/**
 * Starts the installer and answers everything before the first account: by
 * default a local install at crm.acme.test that writes its email to the log.
 *
 * The command runs when the returned object is let go of, so chain the rest
 * of the answers and the assertion onto it instead of keeping it in a variable.
 *
 * @param  array<string, mixed>  $options
 */
function startInstaller(array $options = [], string $isLive = 'no', string $mailer = 'log'): PendingCommand
{
    $command = artisan('momcrm:install', $options)
        ->expectsQuestion('Address people will open MomCRM at', 'https://crm.acme.test')
        ->expectsConfirmation('Is this a live install that other people will use?', $isLive);

    return answerDatabaseQuestions($command)->expectsQuestion('How should MomCRM send email?', $mailer);
}

it('writes the settings, creates the first account as a platform admin and builds the assets', function (): void {
    $file = temporaryEnvironmentFile("APP_URL=http://localhost:8000\nMAIL_MAILER=smtp\n");
    Process::fake();

    startInstaller()
        ->expectsQuestion('Your email address', 'Dana@Acme.test')
        ->expectsQuestion('Your first name', 'Dana')
        ->expectsQuestion('Your last name', 'Reyes')
        ->expectsQuestion('Choose a password', 'correct horse battery')
        ->expectsQuestion('Type the password again', 'correct horse battery')
        ->expectsConfirmation('Build the front-end assets now?', 'yes')
        ->expectsOutputToContain('MomCRM is installed. Open https://crm.acme.test to sign in.')
        ->assertSuccessful();

    $user = UserEloquentModel::query()->where('email', 'dana@acme.test')->sole();

    expect($user)
        ->first_name->toBe('Dana')
        ->last_name->toBe('Reyes')
        ->is_platform_admin->toBeTrue()
        ->email_verified_at->not->toBeNull()
        ->current_team_id->not->toBeNull()
        ->and(Hash::check('correct horse battery', $user->password))->toBeTrue()
        ->and($file->get('APP_URL'))->toBe('https://crm.acme.test')
        ->and($file->get('MAIL_MAILER'))->toBe('log');

    Process::assertRan('npm install');
    Process::assertRan('npm run build');
});

it('creates the environment file from the example, with a new app key, when there is none', function (): void {
    UserEloquentModel::factory()->platformAdmin()->create();
    $directory = sys_get_temp_dir().'/momcrm-install-'.bin2hex(random_bytes(4));
    mkdir($directory);
    $file = new EnvironmentFile(path: $directory.'/.env', backupDirectory: $directory.'/backups');
    app()->instance(EnvironmentFile::class, $file);
    config(['app.key' => '']);

    startInstaller(['--skip-assets' => true])->assertSuccessful();

    expect($file->get('APP_NAME'))->toBe('MomCRM')
        ->and($file->get('APP_KEY'))->toStartWith('base64:')
        ->and(config('app.key'))->toBe($file->get('APP_KEY'));
});

it('fails when it cannot create the environment file', function (): void {
    $file = new EnvironmentFile(path: sys_get_temp_dir().'/momcrm-no-such-directory/.env', backupDirectory: sys_get_temp_dir().'/momcrm-no-such-directory/backups');
    app()->instance(EnvironmentFile::class, $file);

    artisan('momcrm:install')
        ->expectsOutputToContain('Could not create '.$file->path.' from .env.example.')
        ->assertFailed();

    expect($file->path)->not->toBeFile();
});

it('turns debugging off and explains the queue and scheduler for a live install', function (): void {
    UserEloquentModel::factory()->platformAdmin()->create();
    $file = temporaryEnvironmentFile("APP_ENV=local\nAPP_DEBUG=true\n");

    startInstaller(['--skip-assets' => true], isLive: 'yes')
        ->expectsOutputToContain('php artisan schedule:run')
        ->assertSuccessful();

    expect($file->get('APP_ENV'))->toBe('production')
        ->and($file->get('APP_DEBUG'))->toBe('false');
});

it('writes the SMTP server it is given to the environment file', function (): void {
    UserEloquentModel::factory()->platformAdmin()->create();
    $file = temporaryEnvironmentFile("MAIL_MAILER=log\n");

    startInstaller(['--skip-assets' => true], mailer: 'smtp')
        ->expectsQuestion('SMTP host', 'smtp.acme.test')
        ->expectsQuestion('SMTP port', '587')
        ->expectsQuestion('SMTP username', 'crm@acme.test')
        ->expectsQuestion('SMTP password', 'app password')
        ->expectsQuestion('Send email from', 'crm@acme.test')
        ->assertSuccessful();

    expect($file->variables()->pluck('value', 'key')->only(['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS'])->all())->toBe([
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => 'smtp.acme.test',
        'MAIL_PORT' => '587',
        'MAIL_USERNAME' => 'crm@acme.test',
        'MAIL_PASSWORD' => 'app password',
        'MAIL_FROM_ADDRESS' => 'crm@acme.test',
    ]);
});

it('asks for the database again when it cannot be reached', function (): void {
    UserEloquentModel::factory()->platformAdmin()->create();
    temporaryEnvironmentFile('');

    $command = artisan('momcrm:install', ['--skip-assets' => true])
        ->expectsQuestion('Address people will open MomCRM at', 'https://crm.acme.test')
        ->expectsConfirmation('Is this a live install that other people will use?', 'no');

    answerDatabaseQuestions($command, ['host' => '127.0.0.1', 'port' => '1'])
        ->expectsOutputToContain('Could not connect to the database.');

    answerDatabaseQuestions($command)
        ->expectsQuestion('How should MomCRM send email?', 'log')
        ->assertSuccessful()
        ->run();
})->skip(fn (): bool => config('database.default') === 'sqlite', 'SQLite has no database server to ask about.');

it('creates no account once there is a platform admin', function (): void {
    UserEloquentModel::factory()->platformAdmin()->create();
    temporaryEnvironmentFile('');

    startInstaller(['--skip-assets' => true])
        ->expectsOutputToContain('A platform admin already exists, so no account was created.')
        ->assertSuccessful();

    expect(UserEloquentModel::query()->count())->toBe(1);
});

it('makes the account that already has the email a platform admin', function (): void {
    $user = UserEloquentModel::factory()->create(['email' => 'dana@acme.test']);
    temporaryEnvironmentFile('');

    startInstaller(['--skip-assets' => true])
        ->expectsQuestion('Your email address', 'dana@acme.test')
        ->expectsOutputToContain('dana@acme.test is now a platform admin.')
        ->assertSuccessful();

    expect($user->fresh()?->is_platform_admin)->toBeTrue()
        ->and(UserEloquentModel::query()->count())->toBe(1);
});

it('asks for the password again when the two do not match', function (): void {
    temporaryEnvironmentFile('');

    startInstaller(['--skip-assets' => true])
        ->expectsQuestion('Your email address', 'dana@acme.test')
        ->expectsQuestion('Your first name', 'Dana')
        ->expectsQuestion('Your last name', 'Reyes')
        ->expectsQuestion('Choose a password', 'correct horse battery')
        ->expectsQuestion('Type the password again', 'correct horse')
        ->expectsOutputToContain('The passwords do not match.')
        ->expectsQuestion('Choose a password', 'correct horse battery')
        ->expectsQuestion('Type the password again', 'correct horse battery')
        ->assertSuccessful();

    expect(Hash::check('correct horse battery', UserEloquentModel::query()->where('email', 'dana@acme.test')->sole()->password))->toBeTrue();
});

// Under test Laravel still puts the settings questions to the fake terminal, so
// they are answered here; with a real `--no-interaction` they keep their defaults.
it('takes the first account from the options when nobody can answer', function (): void {
    temporaryEnvironmentFile('');

    startInstaller([
        '--no-interaction' => true,
        '--skip-assets' => true,
        '--email' => 'dana@acme.test',
        '--first-name' => 'Dana',
        '--last-name' => 'Reyes',
        '--password' => 'correct horse battery',
    ])->assertSuccessful();

    expect(UserEloquentModel::query()->where('email', 'dana@acme.test')->sole())
        ->name->toBe('Dana Reyes')
        ->is_platform_admin->toBeTrue();
});

it('creates no account when nobody can answer and the options are missing', function (): void {
    temporaryEnvironmentFile('');

    startInstaller(['--no-interaction' => true, '--skip-assets' => true, '--email' => 'dana@acme.test'])
        ->expectsOutputToContain('No account was created. Pass --email, --first-name, --last-name and --password')
        ->assertSuccessful();

    expect(UserEloquentModel::query()->count())->toBe(0);
});

it('fails on an email option that is not an email address', function (): void {
    temporaryEnvironmentFile('');

    startInstaller(['--skip-assets' => true, '--email' => 'dana'])
        ->expectsOutputToContain('The email field must be a valid email address.')
        ->assertFailed();

    expect(UserEloquentModel::query()->count())->toBe(0);
});

it('fails when the assets cannot be built', function (): void {
    UserEloquentModel::factory()->platformAdmin()->create();
    temporaryEnvironmentFile('');
    Process::fake(['npm install' => Process::result(exitCode: 1)]);

    startInstaller()
        ->expectsConfirmation('Build the front-end assets now?', 'yes')
        ->expectsOutputToContain('"npm install" failed.')
        ->assertFailed();

    Process::assertDidntRun('npm run build');
});
