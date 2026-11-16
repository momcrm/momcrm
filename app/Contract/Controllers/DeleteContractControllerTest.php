<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Contract\ContractEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a contract with its documents', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $contract = ContractEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $document = AttachmentEloquentModel::factory()->on($contract)->create(['path' => 'attachments/signed.pdf']);
    Storage::disk('local')->put('attachments/signed.pdf', 'PDF');

    actingAs($user)
        ->delete(route('contracts.destroy', $contract->id))
        ->assertRedirectToRoute('customers.contracts', $contract->customer_id);

    assertModelMissing($contract);
    assertModelMissing($document);
    Storage::disk('local')->assertMissing('attachments/signed.pdf');
});

it('leaves deleting contracts to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $contract = ContractEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('contracts.destroy', $contract->id))->assertForbidden();

    expect($contract->fresh())->not->toBeNull();
});

it("returns not found for another team's contract", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('contracts.destroy', ContractEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
