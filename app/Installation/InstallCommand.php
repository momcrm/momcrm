<?php

declare(strict_types=1);

namespace App\Installation;

use App\Auth\Actions\CreateAccount;
use App\Environment\EnvironmentFile;
use App\Environment\Exceptions\CouldNotWriteEnvironmentFile;
use App\User\Actions\SetPlatformAdmin;
use App\User\Properties\Email;
use App\User\Properties\IsPlatformAdmin;
use App\User\UserEloquentModel;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

/**
 * Takes a fresh copy of MomCRM to the point where someone can sign in: the
 * `.env` and its app key, the database, email, the tables, the first account
 * and the front-end assets.
 *
 * It is safe to run again. Every question starts from the current setting, only
 * settings that changed are written, and no second account is made once a
 * platform admin exists. Without a terminal (`--no-interaction`) it keeps the
 * current settings and takes the first account from the options.
 */
final class InstallCommand extends Command
{
    /**
     * Drivers that are reached over the network, so have a host and a sign-in.
     *
     * @var list<string>
     */
    private const array SERVER_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlsrv'];

    /**
     * A throwaway connection for trying database settings, so a wrong answer
     * never touches the connection the app is using.
     */
    private const string PROBE_CONNECTION = 'momcrm_install';

    /**
     * @var string
     */
    protected $signature = 'momcrm:install
        {--email= : Email address of the first account}
        {--first-name= : First name of the first account}
        {--last-name= : Last name of the first account}
        {--password= : Password of the first account}
        {--skip-assets : Leave the front-end assets as they are}';

    /**
     * @var string
     */
    protected $description = 'Set up MomCRM: environment file, database, email, first account and assets';

    private EnvironmentFile $environment;

    public function handle(EnvironmentFile $environment): int
    {
        $this->environment = $environment;

        intro(__('Install MomCRM'));

        try {
            if (! $this->hasWritableDirectories() || ! $this->prepareEnvironmentFile()) {
                return self::FAILURE;
            }

            $url = $this->configureApplication();

            if (! $this->configureDatabase()) {
                return self::FAILURE;
            }

            $this->configureMail();
        } catch (CouldNotWriteEnvironmentFile) {
            error(__('Could not write :path. Make it writable, then run the installer again.', ['path' => $environment->path]));

            return self::FAILURE;
        }

        if (! $this->migrate() || ! $this->createFirstAccount()) {
            return self::FAILURE;
        }

        $assetsAreReady = $this->buildAssets();

        if ($this->laravel->configurationIsCached()) {
            $this->callSilently('config:cache');
        }

        $this->explainNextSteps();

        if (! $assetsAreReady) {
            return self::FAILURE;
        }

        outro(__('MomCRM is installed. Open :url to sign in.', ['url' => $url]));

        return self::SUCCESS;
    }

    private static function isMissingDatabase(Throwable $exception): bool
    {
        return Str::contains($exception->getMessage(), ['Unknown database', 'does not exist']);
    }

    /**
     * Laravel writes its logs, cache, sessions and compiled files here, so
     * nothing works until the web server can write to them.
     */
    private function hasWritableDirectories(): bool
    {
        $unwritable = array_filter(
            [storage_path(), storage_path('framework'), storage_path('logs'), $this->laravel->bootstrapPath('cache')],
            fn (string $directory): bool => ! is_dir($directory) || ! is_writable($directory),
        );

        foreach ($unwritable as $directory) {
            error(__(':directory must exist and be writable.', ['directory' => $directory]));
        }

        return $unwritable === [];
    }

    /**
     * Makes sure there is a `.env` to write to and that it has an app key.
     */
    private function prepareEnvironmentFile(): bool
    {
        $path = $this->environment->path;

        if (! is_file($path)) {
            if (! @copy($this->laravel->basePath('.env.example'), $path)) {
                error(__('Could not create :path from .env.example.', ['path' => $path]));

                return false;
            }

            info(__('Created :path from .env.example.', ['path' => $path]));
        }

        $key = Config::get('app.key');

        if (! is_string($key) || $key === '') {
            $key = 'base64:'.base64_encode(Encrypter::generateKey(Config::string('app.cipher')));

            $this->environment->set('APP_KEY', $key);
            Config::set('app.key', $key);

            info(__('Generated the app key.'));
        }

        return true;
    }

    /**
     * Asks where MomCRM is opened and whether this is a live install.
     *
     * @return string The address to sign in at.
     */
    private function configureApplication(): string
    {
        $url = rtrim(text(
            label: __('Address people will open MomCRM at'),
            default: $this->current('APP_URL', 'http://localhost:8000'),
            required: true,
            validate: ['address' => ['url:http,https']],
        ), '/');

        $environment = $this->current('APP_ENV', 'production');

        $isLive = confirm(
            label: __('Is this a live install that other people will use?'),
            default: $environment === 'production',
            hint: __('A live install hides error details and asks for strong passwords.'),
        );

        $settings = ['APP_URL' => $url];

        if ($isLive) {
            $environment = 'production';
            $settings += ['APP_ENV' => 'production', 'APP_DEBUG' => 'false'];
        } elseif ($environment === 'production') {
            $environment = 'local';
            $settings += ['APP_ENV' => 'local', 'APP_DEBUG' => 'true'];
        }

        $this->save($settings);

        // The rest of this run should behave as the installed app will: the
        // password rules, for one, depend on the environment.
        $this->laravel->instance('env', $environment);
        Config::set('app.env', $environment);
        Config::set('app.url', $url);

        return $url;
    }

    /**
     * Asks for the database server until it can be reached, then points both
     * the `.env` and this run at it. SQLite and a `DB_URL` have nothing to ask.
     */
    private function configureDatabase(): bool
    {
        $connection = $this->current('DB_CONNECTION', Config::string('database.default'));
        $configured = Config::get("database.connections.{$connection}");

        if (! is_array($configured)) {
            error(__('DB_CONNECTION is ":connection", which config/database.php does not have.', ['connection' => $connection]));

            return false;
        }

        if (! in_array($configured['driver'] ?? null, self::SERVER_DRIVERS, true) || $this->current('DB_URL') !== '') {
            $this->useDatabase($connection, []);
            info(__('Using the :connection database from the environment file.', ['connection' => $connection]));

            return true;
        }

        $settings = [];

        foreach (['host' => 'DB_HOST', 'port' => 'DB_PORT', 'database' => 'DB_DATABASE', 'username' => 'DB_USERNAME', 'password' => 'DB_PASSWORD'] as $option => $key) {
            $settings[$key] = $this->current($key, is_scalar($configured[$option] ?? null) ? (string) $configured[$option] : '');
        }

        while (true) {
            $settings = $this->askForDatabase($settings);
            $problem = $this->databaseProblem($connection, $settings);

            if ($problem === null) {
                break;
            }

            error(__('Could not connect to the database. :problem', ['problem' => $problem]));

            if (! $this->input->isInteractive()) {
                return false;
            }
        }

        $this->save($settings);
        $this->useDatabase($connection, $settings);

        return true;
    }

    /**
     * @param  array<string, string>  $settings  The last answers, offered again.
     * @return array<string, string>
     */
    private function askForDatabase(array $settings): array
    {
        $answers = [
            'DB_HOST' => text(label: __('Database host'), default: $settings['DB_HOST'], required: true),
            'DB_PORT' => text(label: __('Database port'), default: $settings['DB_PORT'], required: true, validate: ['port' => ['integer', 'between:1,65535']]),
            'DB_DATABASE' => text(label: __('Database name'), default: $settings['DB_DATABASE'], required: true),
            'DB_USERNAME' => text(label: __('Database username'), default: $settings['DB_USERNAME'], required: true),
        ];

        $password = password(
            label: __('Database password'),
            hint: $settings['DB_PASSWORD'] === '' ? '' : __('Leave empty to keep the current password.'),
        );

        return [...$answers, 'DB_PASSWORD' => $password === '' ? $settings['DB_PASSWORD'] : $password];
    }

    /**
     * Tries the settings on a connection of their own. A database that is not
     * there yet is created when the person agrees to it.
     *
     * @param  array<string, string>  $settings
     * @return ?string What went wrong, or null when the database answered.
     */
    private function databaseProblem(string $connection, array $settings): ?string
    {
        $probe = [...$this->databaseConfig($connection, $settings), 'url' => null];

        try {
            $this->probe($probe, fn (Connection $database): mixed => $database->getPdo());

            return null;
        } catch (Throwable $exception) {
            $name = $settings['DB_DATABASE'];

            if (! self::isMissingDatabase($exception) || ! confirm(__('The database ":name" does not exist. Create it?', ['name' => $name]))) {
                return $exception->getMessage();
            }
        }

        try {
            // PostgreSQL only takes a connection to a database that exists.
            $server = [...$probe, 'database' => $probe['driver'] === 'pgsql' ? 'postgres' : null];
            $this->probe($server, fn (Connection $database): bool => $database->getSchemaBuilder()->createDatabase($name));

            return null;
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  callable(Connection): mixed  $use
     */
    private function probe(array $config, callable $use): void
    {
        Config::set('database.connections.'.self::PROBE_CONNECTION, $config);

        try {
            $use(DB::connection(self::PROBE_CONNECTION));
        } finally {
            DB::purge(self::PROBE_CONNECTION);
            Config::set('database.connections.'.self::PROBE_CONNECTION, null);
        }
    }

    /**
     * Points this run at the database the `.env` now names, so the tables and
     * the first account land there. The connection is only dropped when a
     * setting really changed.
     *
     * @param  array<string, string>  $settings
     */
    private function useDatabase(string $connection, array $settings): void
    {
        $before = Config::array("database.connections.{$connection}");
        $after = $this->databaseConfig($connection, $settings);
        $hasChanged = Config::get('database.default') !== $connection;

        foreach ($after as $option => $value) {
            $previous = $before[$option] ?? null;
            $hasChanged = $hasChanged || (is_scalar($value) && is_scalar($previous) ? (string) $value !== (string) $previous : $value !== $previous);
        }

        Config::set('database.default', $connection);
        Config::set("database.connections.{$connection}", $after);

        if ($hasChanged) {
            DB::purge($connection);
        }
    }

    /**
     * The connection's config with the answers in place.
     *
     * @param  array<string, string>  $settings
     * @return array<string, mixed>
     */
    private function databaseConfig(string $connection, array $settings): array
    {
        /** @var array<string, mixed> $config */
        $config = Config::array("database.connections.{$connection}");

        foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_DATABASE' => 'database', 'DB_USERNAME' => 'username', 'DB_PASSWORD' => 'password'] as $key => $option) {
            if (array_key_exists($key, $settings)) {
                $config[$option] = $settings[$key];
            }
        }

        return $config;
    }

    /**
     * Sign-in codes and invitations go out by email, so a live install needs a
     * mail server. Any other mailer already set up is left as it is.
     */
    private function configureMail(): void
    {
        $current = $this->current('MAIL_MAILER', 'log');

        $options = [
            'smtp' => __('Through an SMTP server'),
            'log' => __('Do not send: write emails to the log'),
        ];

        if (! array_key_exists($current, $options)) {
            $options[$current] = __('Keep the current setting (:mailer)', ['mailer' => $current]);
        }

        $mailer = (string) select(
            label: __('How should MomCRM send email?'),
            options: $options,
            default: $current,
            hint: __('Sign-in codes and invitations go out by email.'),
        );

        if ($mailer !== 'smtp') {
            $this->save(['MAIL_MAILER' => $mailer]);

            return;
        }

        $currentPassword = $this->current('MAIL_PASSWORD');

        $settings = [
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => text(label: __('SMTP host'), default: $this->current('MAIL_HOST'), required: true),
            'MAIL_PORT' => text(label: __('SMTP port'), default: $this->current('MAIL_PORT', '587'), required: true, validate: ['port' => ['integer', 'between:1,65535']]),
            'MAIL_USERNAME' => text(label: __('SMTP username'), default: $this->current('MAIL_USERNAME')),
        ];

        $password = password(
            label: __('SMTP password'),
            hint: $currentPassword === '' ? '' : __('Leave empty to keep the current password.'),
        );

        $this->save([
            ...$settings,
            'MAIL_PASSWORD' => $password === '' ? $currentPassword : $password,
            'MAIL_FROM_ADDRESS' => text(
                label: __('Send email from'),
                default: $this->current('MAIL_FROM_ADDRESS'),
                required: true,
                validate: ['address' => ['email']],
            ),
        ]);
    }

    private function migrate(): bool
    {
        try {
            return $this->call('migrate', ['--force' => true]) === self::SUCCESS;
        } catch (Throwable $exception) {
            error(__('Could not set up the database tables. :problem', ['problem' => $exception->getMessage()]));

            return false;
        }
    }

    /**
     * Creates the first account and makes it a platform admin, who looks after
     * the installation. An existing account with that email is made one instead.
     */
    private function createFirstAccount(): bool
    {
        if (UserEloquentModel::query()->where(IsPlatformAdmin::getDatabaseTableColumnName(), true)->exists()) {
            info(__('A platform admin already exists, so no account was created.'));

            return true;
        }

        $rules = [
            'email' => ['required', 'string', 'email', 'max:255'],
            'first-name' => ['required', 'string', 'max:100'],
            'last-name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', Password::defaults()],
        ];

        $given = array_filter($this->options(), fn (mixed $value, string $option): bool => array_key_exists($option, $rules) && is_string($value), ARRAY_FILTER_USE_BOTH);
        $validator = Validator::make($given, array_intersect_key($rules, $given));

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                error($message);
            }

            return false;
        }

        if (! $this->input->isInteractive() && count($given) < count($rules) && ! $this->promote($given['email'] ?? null)) {
            warning(__('No account was created. Pass --email, --first-name, --last-name and --password, or run the installer again in a terminal.'));

            return true;
        }

        $email = Str::lower(mb_trim($given['email'] ?? text(label: __('Your email address'), required: true, validate: ['email' => $rules['email']])));

        if ($this->promote($email)) {
            info(__(':email is now a platform admin.', ['email' => $email]));

            return true;
        }

        CreateAccount::for(
            email: $email,
            firstName: mb_trim($given['first-name'] ?? text(label: __('Your first name'), required: true, validate: ['first name' => $rules['first-name']])),
            lastName: mb_trim($given['last-name'] ?? text(label: __('Your last name'), required: true, validate: ['last name' => $rules['last-name']])),
            password: $given['password'] ?? $this->askForPassword($rules['password']),
        );

        SetPlatformAdmin::forEmail($email);

        info(__('Created the account for :email, a platform admin.', ['email' => $email]));

        return true;
    }

    /**
     * Makes the account with this email a platform admin, when there is one.
     */
    private function promote(?string $email): bool
    {
        return $email !== null
            && UserEloquentModel::query()->where(Email::getDatabaseTableColumnName(), Str::lower(mb_trim($email)))->exists()
            && SetPlatformAdmin::forEmail(Str::lower(mb_trim($email)))->isNotNull();
    }

    /**
     * Asks for the password twice, until both answers are the same.
     *
     * @param  array<int, mixed>  $rules
     */
    private function askForPassword(array $rules): string
    {
        while (true) {
            $password = password(label: __('Choose a password'), required: true, validate: ['password' => $rules]);

            if (password(label: __('Type the password again'), required: true) === $password) {
                return $password;
            }

            error(__('The passwords do not match.'));
        }
    }

    /**
     * Builds the pages' JavaScript and CSS. Without them every page is blank,
     * so a failed build fails the install.
     */
    private function buildAssets(): bool
    {
        if ($this->option('skip-assets')) {
            return true;
        }

        $shouldBuild = confirm(
            label: __('Build the front-end assets now?'),
            default: ! is_file($this->laravel->publicPath('build/manifest.json')),
            hint: __('Runs npm install and npm run build. Needs Node.js 22 or newer.'),
        );

        if (! $shouldBuild) {
            return true;
        }

        foreach (['npm install', 'npm run build'] as $command) {
            $result = Process::path($this->laravel->basePath())->forever()->run($command, function (string $type, string $output): void {
                $this->output->write($output);
            });

            if ($result->failed()) {
                error(__('":command" failed. Run npm install and npm run build yourself to finish the install.', ['command' => $command]));

                return false;
            }
        }

        return true;
    }

    private function explainNextSteps(): void
    {
        if ($this->laravel->isProduction()) {
            note(implode(PHP_EOL, [
                __('Keep a queue worker running, and run the scheduler every minute from cron:'),
                '  php artisan queue:work',
                '  * * * * * cd '.$this->laravel->basePath().' && php artisan schedule:run',
            ]));

            return;
        }

        note(__('Start MomCRM with: composer run dev'));
    }

    /**
     * Writes the settings that are not already in the `.env`, so running the
     * installer again without changing anything leaves the file alone.
     *
     * @param  array<string, string>  $settings
     */
    private function save(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if ($this->environment->get($key) !== $value) {
                $this->environment->set($key, $value);
            }
        }
    }

    /**
     * What a setting is now: the environment this run started with, else the
     * `.env` (which was not read when it was just created, or when the config
     * is cached), else the fallback.
     */
    private function current(string $key, string $fallback = ''): string
    {
        $value = Env::get($key) ?? $this->environment->get($key);

        return is_string($value) && ! in_array($value, ['', 'null', '(null)'], true) ? $value : $fallback;
    }
}
