<?php

use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Connector\Actions\FindConnector;
use App\Connector\Actions\GetConnectorAccessToken;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Environment\EnvironmentFile;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\MailboxMessage;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Mom\Connectors\Mail\MailClient;
use Mom\Microsoft\MicrosoftMailClient;
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
 * A contact with portal access at a customer named Coastal Logistics, in a
 * team of their own with the default access rules.
 *
 * @param  array<string, mixed>  $attributes
 */
function portalContact(array $attributes = []): ContactEloquentModel
{
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Coastal Logistics']);

    return ContactEloquentModel::factory()->withPortalAccess()->create([
        'team_id' => $owner->current_team_id,
        'customer_id' => $customer->id,
        'first_name' => 'Mark',
        'last_name' => 'Ellis',
        'email' => 'mark@coastal.test',
        ...$attributes,
    ]);
}

/**
 * A ticket at the portal contact's customer.
 *
 * @param  array<string, mixed>  $attributes
 */
function portalTicket(ContactEloquentModel $contact, array $attributes = []): TicketEloquentModel
{
    return TicketEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, ...$attributes]);
}

/**
 * The id of a team's ticket status by name, such as "Closed".
 */
function ticketStatusId(string $teamId, string $name): string
{
    return FindChoice::byNameForTeam($teamId, ChoiceListEnum::TicketStatus, $name)->getId()->toString();
}

/**
 * Gives a mailbox's connector an access token that is still good, so reading
 * or sending through it does not first ask the service for one.
 */
function rememberMailboxToken(Mailbox|MailboxEloquentModel $mailbox, string $token = 'access-token'): void
{
    $connectorId = $mailbox instanceof Mailbox ? $mailbox->getConnectorId()->toString() : $mailbox->connector_id;

    GetConnectorAccessToken::remember(FindConnector::byId($connectorId), $token, 3600);
}

/**
 * An email as Microsoft Graph returns it, as the message we would store.
 *
 * @param  array<string, mixed>  $message
 */
function messageFromGraph(array $message): MailboxMessage
{
    return MailboxMessage::fromInboundMail(MicrosoftMailClient::toInboundMail($message));
}

/**
 * The mail client of a new mailbox whose connector already has a token.
 */
function mailClientWithToken(): MailClient
{
    $mailbox = MailboxEloquentModel::factory()->create();
    rememberMailboxToken($mailbox);

    return ResolveMailClient::forMailbox(Mailbox::fromEloquentModel($mailbox));
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

/**
 * A work schedule form: Monday to Friday 9 to 5 with a 30-minute break.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function officeHoursForm(array $overrides = []): array
{
    $day = ['starts_at' => '09:00', 'ends_at' => '17:00', 'break_minutes' => 30];

    return [
        'name' => 'Office hours',
        'grace_minutes' => 5,
        'days' => ['mon' => $day, 'tue' => $day, 'wed' => $day, 'thu' => $day, 'fri' => $day, 'sat' => null, 'sun' => null],
        ...$overrides,
    ];
}

/**
 * An owner and a member of the owner's team who works in Perth (UTC+8).
 *
 * @return array{0: UserEloquentModel, 1: UserEloquentModel}
 */
function ownerAndPerthMember(): array
{
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['timezone' => 'Australia/Perth']);

    return [$owner, $member];
}
