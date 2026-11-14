<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('stores a file on a project', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('projects.attachments.store', $project->id), ['files' => [UploadedFile::fake()->image('plan.png')]]);

    expect(AttachmentEloquentModel::query()->sole())->attachable_type->toBe('project')->attachable_id->toBe($project->id);
});

it('hides members-only projects from others', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'visibility' => 'restricted']);

    actingAs($user)
        ->post(route('projects.attachments.store', $project->id), ['files' => [UploadedFile::fake()->image('plan.png')]])
        ->assertNotFound();
});
