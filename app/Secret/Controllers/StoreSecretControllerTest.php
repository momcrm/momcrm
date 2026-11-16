<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\AuditLog\AuditLogEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Secret\SecretEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

it("saves a customer's secret with its values encrypted, and keeps them out of the audit log", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->post(route('secrets.store', $customer->id), [
            'title' => 'Firewall admin',
            'asset_id' => $asset->id,
            'values' => [['key' => ' username ', 'value' => 'admin'], ['key' => 'password', 'value' => 'hunter2-very-secret']],
        ])
        ->assertSessionHasNoErrors();

    $secret = SecretEloquentModel::query()->sole();
    expect($secret)
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->asset_id->toBe($asset->id)
        ->version->toBe(1)
        ->values->toBe([['key' => 'username', 'value' => 'admin'], ['key' => 'password', 'value' => 'hunter2-very-secret']])
        ->and(DB::table('secrets')->value('values'))->not->toContain('hunter2-very-secret')
        ->and(AuditLogEloquentModel::query()->where('auditable_type', 'secret')->get()->toJson())->not->toContain('hunter2-very-secret')
        ->not->toContain('"values"');
});

it('needs at least one value, a key on each, and no key twice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('secrets.store', $customer->id), ['title' => 'Wi-Fi', 'values' => []])
        ->assertSessionHasErrors(['values' => 'Add at least one key and value, such as a username and password.']);

    actingAs($user)
        ->post(route('secrets.store', $customer->id), ['title' => 'Wi-Fi', 'values' => [['key' => 'password', 'value' => 'a'], ['key' => 'password', 'value' => 'b'], ['key' => '', 'value' => 'c']]])
        ->assertSessionHasErrors([
            'values.0.key' => 'Each key can be used once.',
            'values.2.key' => 'Give every value a key, such as "password".',
        ]);

    expect(SecretEloquentModel::query()->exists())->toBeFalse();
});

it("rejects another customer's asset", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $elsewhere = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('secrets.store', $customer->id), ['title' => 'NAS', 'asset_id' => $elsewhere->id, 'values' => [['key' => 'password', 'value' => 'x']]])
        ->assertSessionHasErrors(['asset_id' => "Choose one of this customer's assets."]);
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('secrets.store', CustomerEloquentModel::factory()->create()->id), ['title' => 'NAS', 'values' => [['key' => 'password', 'value' => 'x']]])
        ->assertNotFound();
});
