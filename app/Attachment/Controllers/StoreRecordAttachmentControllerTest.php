<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Attachment\AttachmentEloquentModel;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('stores a file on a record in the team folder, and records it', function (): void {
    Storage::fake('local');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'number' => 4]);

    actingAs($owner)
        ->post(route('records.attachments.store', [$vehicles, $van]), ['files' => [UploadedFile::fake()->create('rego.pdf', 120, 'application/pdf')]])
        ->assertSessionHasNoErrors();

    $attachment = AttachmentEloquentModel::query()->sole();

    expect($attachment)
        ->name->toBe('rego.pdf')
        ->attachable_type->toBe('record')
        ->attachable_id->toBe($van->id)
        ->uploaded_by_id->toBe($owner->id)
        ->and($attachment->path)->toStartWith("attachments/{$owner->current_team_id}/")
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::AttachmentAdded->value)->sole())
        ->subject_type->toBe('record')
        ->details->toBe(['file' => 'rego.pdf', 'number' => 4, 'title' => 'Van']);
    Storage::disk('local')->assertExists($attachment->path);
});

it('rejects files of other types', function (): void {
    Storage::fake('local');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $vehicles = CustomObjectEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create();

    actingAs($owner)
        ->post(route('records.attachments.store', [$vehicles, $van]), ['files' => [UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload')]])
        ->assertSessionHasErrors(['files.0' => 'Upload images, PDFs, Office documents, text, CSV or zip files.']);
});

it('finds no record to attach to in another team', function (): void {
    Storage::fake('local');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $foreign = RecordEloquentModel::factory()->create();

    actingAs($owner)
        ->post(route('records.attachments.store', [$foreign->object_id, $foreign]), ['files' => [UploadedFile::fake()->create('rego.pdf', 120, 'application/pdf')]])
        ->assertNotFound();

    expect(AttachmentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});
