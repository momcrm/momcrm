<?php

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Environment\EnvironmentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

// Tests co-located with the code they cover, e.g. app/Team/Controllers/UpdateTeamControllerTest.php.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('../app');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * The id of a team's ticket status by name, such as "Closed".
 */
function ticketStatusId(string $teamId, string $name): string
{
    return FindChoice::byNameForTeam($teamId, ChoiceListEnum::TicketStatus, $name)->getId()->toString();
}

/**
 * An email as Microsoft Graph returns it when asked for its headers and a
 * plain-text body.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function graphMessage(array $overrides = []): array
{
    return [
        'id' => 'graph-1',
        'subject' => 'Printer offline',
        'from' => ['emailAddress' => ['name' => 'Sam Lee', 'address' => 'Sam@Acme.test']],
        'toRecipients' => [['emailAddress' => ['name' => 'Support', 'address' => 'Support@Helpdesk.test']]],
        'receivedDateTime' => '2026-11-18T01:15:00Z',
        'internetMessageId' => '<message-1@acme.test>',
        'isDraft' => false,
        'hasAttachments' => false,
        'body' => ['contentType' => 'text', 'content' => "The printer on level 2 is offline.\r\n"],
        'uniqueBody' => ['contentType' => 'text', 'content' => "The printer on level 2 is offline.\r\n"],
        'internetMessageHeaders' => [
            ['name' => 'Received', 'value' => 'from mail.acme.test'],
            ['name' => 'Message-ID', 'value' => '<message-1@acme.test>'],
        ],
        ...$overrides,
    ];
}

/**
 * An EnvironmentFile over a throwaway `.env` with the given contents, bound in
 * the container so the environment pages edit it instead of the real one.
 */
function temporaryEnvironmentFile(string $contents, int $backupsKept = 20): EnvironmentFile
{
    $directory = sys_get_temp_dir().'/momcrm-environment-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($directory);
    file_put_contents($directory.'/.env', $contents);

    $file = new EnvironmentFile(path: $directory.'/.env', backupDirectory: $directory.'/backups', backupsKept: $backupsKept);
    app()->instance(EnvironmentFile::class, $file);

    return $file;
}
