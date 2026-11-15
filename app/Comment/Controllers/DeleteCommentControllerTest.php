<?php

declare(strict_types=1);

use App\Comment\CommentEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('deletes your own comment', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $comment = CommentEloquentModel::factory()->create([
        'ticket_id' => TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
        'user_id' => $user->id,
    ]);

    actingAs($user)->delete(route('comments.destroy', $comment->id));

    expect(CommentEloquentModel::query()->exists())->toBeFalse();
});

it("leaves other people's comments alone", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $comment = CommentEloquentModel::factory()->create([
        'ticket_id' => TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
    ]);

    actingAs($user)->delete(route('comments.destroy', $comment->id))->assertNotFound();

    expect(CommentEloquentModel::query()->exists())->toBeTrue();
});

it('lets a member delete their own comment under the default rules', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $comment = CommentEloquentModel::factory()->create([
        'ticket_id' => TicketEloquentModel::factory()->create(['team_id' => $owner->current_team_id])->id,
        'user_id' => $member->id,
    ]);

    actingAs($member)->delete(route('comments.destroy', $comment->id))->assertRedirect();

    expect(CommentEloquentModel::query()->exists())->toBeFalse();
});
