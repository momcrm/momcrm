<?php

declare(strict_types=1);

use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * An email that came with HTML, on a ticket of the person's team.
 *
 * @param  array<string, mixed>  $attributes
 */
function htmlEmailOnTicket(UserEloquentModel $user, array $attributes = []): MailboxMessageEloquentModel
{
    return MailboxMessageEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'ticket_id' => TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
        'body_html' => '<p>Please <a href="https://acme.test/in">sign in</a>.</p><script>alert(1)</script>',
        ...$attributes,
    ]);
}

it('shows an email as it was sent, cleaned and boxed in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->get(route('emails.original', htmlEmailOnTicket($user)->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=utf-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee('<a href="https://acme.test/in" target="_blank" rel="noopener noreferrer">sign in</a>', false)
        ->assertDontSee('alert(1)', false);

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'none'", 'img-src data:;', 'sandbox allow-scripts allow-popups allow-popups-to-escape-sandbox')
        ->not->toContain('https:', 'allow-same-origin', "'unsafe-inline'; img");
});

it('lets only its own script run, under a word that is new for every page', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $email = htmlEmailOnTicket($user);
    $wordIn = function (TestResponse $response): string {
        preg_match("/script-src 'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $matches);

        return $matches[1] ?? '';
    };

    $first = actingAs($user)->get(route('emails.original', $email->id));
    $second = actingAs($user)->get(route('emails.original', $email->id));

    expect($wordIn($first))->not->toBe('')->not->toBe($wordIn($second));
    $first->assertSee('<script nonce="'.$wordIn($first).'">', false);
    expect(substr_count((string) $first->getContent(), '<script'))->toBe(1);
});

it('lets pictures from the web load only when asked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->get(route('emails.original', ['message' => htmlEmailOnTicket($user)->id, 'pictures' => 1]));

    expect($response->assertOk()->headers->get('Content-Security-Policy'))->toContain('img-src data: https:;');
});

it('sends a guest to sign in', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    get(route('emails.original', htmlEmailOnTicket($user)->id))->assertRedirect(route('login'));
});

it("returns not found for another team's email", function (): void {
    $email = htmlEmailOnTicket(UserEloquentModel::factory()->withPersonalTeam()->create());

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('emails.original', $email->id))
        ->assertNotFound();
});

it('returns not found for an email there is nothing to show for', function (Closure $attributes): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->get(route('emails.original', htmlEmailOnTicket($user, $attributes())->id))
        ->assertNotFound();
})->with([
    'one that came as text alone' => [fn (): array => ['body_html' => null]],
    'one that is on no ticket' => [fn (): array => ['ticket_id' => null]],
    'one the team sent' => [fn (): array => ['direction' => 'outbound']],
]);

it('returns not found for an email on a ticket in a members-only project the person is not in', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $member->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $member->current_team_id, 'user_id' => $outsider->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'restricted']);
    $project->members()->attach($member->id);
    $email = htmlEmailOnTicket($member, [
        'ticket_id' => TicketEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'project_id' => $project->id])->id,
    ]);

    actingAs($outsider)->get(route('emails.original', $email->id))->assertNotFound();
    actingAs($member)->get(route('emails.original', $email->id))->assertOk();
});
