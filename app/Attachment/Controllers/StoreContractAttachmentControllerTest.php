<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Contract\ContractEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('adds documents to a contract', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contract = ContractEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('contracts.attachments.store', $contract->id), ['files' => [UploadedFile::fake()->create('signed.pdf', 50, 'application/pdf')]])
        ->assertSessionHasNoErrors();

    expect(AttachmentEloquentModel::query()->sole())
        ->attachable_type->toBe('contract')
        ->attachable_id->toBe($contract->id)
        ->name->toBe('signed.pdf');
});

it("returns not found for another team's contract", function (): void {
    Storage::fake('local');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('contracts.attachments.store', ContractEloquentModel::factory()->create()->id), ['files' => [UploadedFile::fake()->create('signed.pdf', 50, 'application/pdf')]])
        ->assertNotFound();
});
