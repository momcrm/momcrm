<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\MentionedInRecordCommentNotification;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('adds a comment to a record and records it, telling whoever is mentioned but not the author', function (): void {
    Notification::fake();
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $teammate = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Vehicle', 'name_plural' => 'Vehicles']);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'number' => 4]);

    actingAs($owner)
        ->post(route('records.comments.store', [$vehicles, $van]), ['body' => ' Service is due ', 'mentions' => [$teammate->id, $owner->id]])
        ->assertSessionHasNoErrors();

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->sole())
        ->record_id->toBe($van->id)
        ->ticket_id->toBeNull()
        ->team_id->toBe($owner->current_team_id)
        ->user_id->toBe($owner->id)
        ->body->toBe('Service is due')
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::RecordCommented->value)->sole())
        ->subject_type->toBe('record')
        ->subject_id->toBe($van->id)
        ->details->toEqual(['name' => 'Van', 'object' => 'Vehicle']);

    Notification::assertSentTo($teammate, MentionedInRecordCommentNotification::class, fn (MentionedInRecordCommentNotification $notification): bool => $notification->toArray($teammate) === [
        'kind' => 'mention',
        'team_id' => $owner->current_team_id,
        'actor_id' => $owner->id,
        'actor_name' => $owner->name,
        'subject_type' => 'record',
        'subject_id' => $van->id,
        'object_id' => $vehicles->id,
        'number' => 4,
        'title' => 'Van',
        'project_name' => 'Vehicles',
        'message' => 'mentioned you',
        'excerpt' => 'Service is due',
    ]);
    Notification::assertNotSentTo($owner, MentionedInRecordCommentNotification::class);
});

it('rejects an empty comment and mentions outside the team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();

    actingAs($owner)
        ->post(route('records.comments.store', [$vehicles, $van]), ['body' => '  ', 'mentions' => [UserEloquentModel::factory()->create()->id]])
        ->assertSessionHasErrors(['body' => 'Write a comment first.', 'mentions.0' => 'You can only mention people in your team.']);
});

it('finds no record to comment on under another object, or in another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $licence = RecordEloquentModel::factory()->of(CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]))->create();
    $foreign = RecordEloquentModel::factory()->create();

    actingAs($owner)->post(route('records.comments.store', [$vehicles, $licence]), ['body' => 'Hi'])->assertNotFound();
    actingAs($owner)->post(route('records.comments.store', [$foreign->object_id, $foreign]), ['body' => 'Hi'])->assertNotFound();

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});
