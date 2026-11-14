<?php

declare(strict_types=1);

use App\TeamInvitation\Notifications\TeamInvitationNotification;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('sends the invites and moves on to the starting point', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('onboarding.invite.store'), ['emails' => 'sam@acme.test'])
        ->assertRedirectToRoute('onboarding.start');

    Notification::assertSentOnDemandTimes(TeamInvitationNotification::class, 1);
});
