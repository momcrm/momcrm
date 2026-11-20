<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Asset\AssetEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's assets without staff descriptions or other customers'", function (): void {
    $contact = portalContact();
    $asset = AssetEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'name' => 'Front desk PC', 'serial_number' => 'SN-1', 'description' => 'Admin password on the sticky note']);
    AssetEloquentModel::factory()->create(['team_id' => $contact->team_id]);

    actingAs($contact, 'portal')
        ->get(route('portal.assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/assets')
            ->has('assets', 1)
            ->where('assets.0.id', $asset->id)
            ->where('assets.0.serial_number', 'SN-1')
            ->missing('assets.0.description'));
});

it('is forbidden when no rule lets contacts see assets', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    actingAs($contact, 'portal')->get(route('portal.assets.index'))->assertForbidden();
});
