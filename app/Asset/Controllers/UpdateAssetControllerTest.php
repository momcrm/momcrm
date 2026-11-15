<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Choice\ChoiceEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a asset', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->put(route('assets.update', $asset->id), ['name' => 'Reception PC', 'serial_number' => 'SN-1', 'status' => 'active'])
        ->assertRedirectToRoute('customers.show', $customer->id);

    expect($asset->fresh())
        ->name->toBe('Reception PC')
        ->serial_number->toBe('SN-1')
        ->status->toBe('active');
});

it("returns not found for another team's asset", function (): void {
    $other = AssetEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('assets.update', $other->id), ['name' => 'Reception PC', 'serial_number' => 'SN-1', 'status' => 'active'])
        ->assertNotFound();
});

it("replaces an asset's labels, clearing them when the form sends none", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    [$first, $second] = ChoiceEloquentModel::factory()->label()->count(2)->create(['team_id' => $user->current_team_id])->all();
    $asset->labels()->attach($first);
    $fields = ['name' => 'Reception PC', 'status' => 'active'];

    actingAs($user)->put(route('assets.update', $asset->id), [...$fields, 'label_ids' => [$second->id]])->assertSessionHasNoErrors();
    expect($asset->labels()->pluck('choices.id')->all())->toBe([$second->id]);

    actingAs($user)->put(route('assets.update', $asset->id), [...$fields, 'labels_sent' => '1'])->assertSessionHasNoErrors();
    expect($asset->labels()->count())->toBe(0);
});

it("rejects another team's label", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id]);

    actingAs($user)
        ->put(route('assets.update', $asset->id), ['name' => 'Reception PC', 'status' => 'active', 'label_ids' => [ChoiceEloquentModel::factory()->label()->create()->id]])
        ->assertSessionHasErrors('label_ids.0');
});
