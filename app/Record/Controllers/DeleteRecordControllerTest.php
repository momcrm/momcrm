<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Comment\CommentEloquentModel;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it("lets an owner delete a record and returns to the object's list", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $record = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van']);

    actingAs($owner)
        ->delete(route('records.destroy', [$vehicles, $record]))
        ->assertRedirectToRoute('records.index', $vehicles->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Van deleted.']);

    expect($record->fresh())->toBeNull();
});

it('forbids a member, whom the default rules do not let delete', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $record = RecordEloquentModel::factory()->of($vehicles)->create();

    actingAs($member)->delete(route('records.destroy', [$vehicles, $record]))->assertForbidden();

    expect($record->fresh())->not->toBeNull();
});

it('finds no record to delete in another team', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $foreign = RecordEloquentModel::factory()->create();

    actingAs($owner)->delete(route('records.destroy', [$foreign->object_id, $foreign]))->assertNotFound();
});

it('takes the record\'s comments and files with it', function (): void {
    Storage::fake('local');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();
    CommentEloquentModel::factory()->create(['ticket_id' => null, 'record_id' => $van->id, 'team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('records.attachments.store', [$vehicles, $van]), ['files' => [UploadedFile::fake()->create('rego.pdf', 120, 'application/pdf')]]);
    $path = AttachmentEloquentModel::query()->sole()->path;

    actingAs($owner)->delete(route('records.destroy', [$vehicles, $van]))->assertSessionHasNoErrors();

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(AttachmentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});
