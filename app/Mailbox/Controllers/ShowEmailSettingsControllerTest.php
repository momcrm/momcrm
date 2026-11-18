<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lists the team\'s mailboxes without their sign-in, and its groups', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Helpdesk']);
    $mailbox = MailboxEloquentModel::factory()->shared()->create([
        'team_id' => $owner->current_team_id,
        'address' => 'support@acme.test',
        'group_id' => $group->id,
    ]);
    MailboxEloquentModel::factory()->create(['address' => 'other@elsewhere.test']);
    config(['services.microsoft.client_id' => 'id', 'services.microsoft.client_secret' => 'secret']);

    actingAs($owner)
        ->get(route('mailboxes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/email')
            ->where('canConnect', true)
            ->where('groups', [['id' => $group->id, 'name' => 'Helpdesk']])
            ->has('mailboxes', 1)
            ->where('mailboxes.0', [
                'id' => $mailbox->id,
                'address' => 'support@acme.test',
                'display_name' => null,
                'is_shared' => true,
                'status' => 'connected',
                'group_id' => $group->id,
                'auto_create_tickets' => true,
                'last_synced_at' => null,
                'last_error' => null,
            ]),
        );
});

it('says mailboxes cannot be connected when Microsoft sign-in is not set up', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    config(['services.microsoft.client_id' => null, 'services.microsoft.client_secret' => null]);

    actingAs($owner)
        ->get(route('mailboxes.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canConnect', false));
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('mailboxes.index'))->assertForbidden();
});

it('sends guests to sign in', function (): void {
    get(route('mailboxes.index'))->assertRedirectToRoute('login');
});
