<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it("uploads files to a customer's folder", function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $folder = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->post(route('customers.files.store', $customer->id), [
            'folder_id' => $folder->id,
            'files' => [UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf')],
        ])
        ->assertSessionHasNoErrors();

    $file = AttachmentEloquentModel::query()->sole();
    expect($file)
        ->attachable_type->toBe('customer')
        ->attachable_id->toBe($customer->id)
        ->folder_id->toBe($folder->id)
        ->uploaded_by_id->toBe($user->id);
    Storage::disk('local')->assertExists($file->path);
});

it('uploads to the top level without a folder', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('customers.files.store', $customer->id), ['folder_id' => '', 'files' => [UploadedFile::fake()->image('site.png')]])
        ->assertSessionHasNoErrors();

    expect(AttachmentEloquentModel::query()->sole()->folder_id)->toBeNull();
});

it("rejects another customer's folder and unsafe file types", function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = FolderEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('customers.files.store', $customer->id), [
            'folder_id' => $elsewhere->id,
            'files' => [UploadedFile::fake()->createWithContent('page.html', '<script>alert(1)</script>')],
        ])
        ->assertSessionHasErrors([
            'folder_id' => "Choose one of this customer's folders.",
            'files.0' => 'Upload images, PDFs, Office documents, text, CSV or zip files.',
        ]);

    expect(AttachmentEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's customer", function (): void {
    Storage::fake('local');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('customers.files.store', CustomerEloquentModel::factory()->create()->id), ['files' => [UploadedFile::fake()->image('site.png')]])
        ->assertNotFound();
});
