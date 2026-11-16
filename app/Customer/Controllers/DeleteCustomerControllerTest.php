<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a customer and their contacts', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->delete(route('customers.destroy', $customer->id))
        ->assertRedirectToRoute('customers.index');

    assertModelMissing($customer);
    assertModelMissing($contact);
});

it('deletes the customer\'s files and contract documents, with their stored copies', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $file = AttachmentEloquentModel::factory()->on($customer)->create(['path' => 'attachments/contract.pdf']);
    $document = AttachmentEloquentModel::factory()
        ->on(ContractEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]))
        ->create(['path' => 'attachments/signed.pdf']);
    Storage::disk('local')->put('attachments/contract.pdf', 'PDF');
    Storage::disk('local')->put('attachments/signed.pdf', 'PDF');

    actingAs($user)->delete(route('customers.destroy', $customer->id));

    assertModelMissing($file);
    assertModelMissing($document);
    Storage::disk('local')->assertMissing(['attachments/contract.pdf', 'attachments/signed.pdf']);
});

it("returns not found for another team's customer", function (): void {
    $other = CustomerEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('customers.destroy', $other->id))
        ->assertNotFound();

    expect(CustomerEloquentModel::query()->withoutGlobalScopes()->whereKey($other->id)->exists())->toBeTrue();
});

it('leaves deleting customers to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('customers.destroy', $customer->id))->assertForbidden();

    expect($customer->fresh())->not->toBeNull();
});
