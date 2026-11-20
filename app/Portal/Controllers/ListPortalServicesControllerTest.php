<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Service\ServiceEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's services without archived ones, staff notes or other customers'", function (): void {
    $contact = portalContact();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'name' => 'Managed firewall', 'note' => 'Discounted, do not mention']);
    ServiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'archived_at' => now()]);
    ServiceEloquentModel::factory()->create(['team_id' => $contact->team_id]);

    actingAs($contact, 'portal')
        ->get(route('portal.services.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/services')
            ->has('services', 1)
            ->where('services.0.id', $service->id)
            ->where('services.0.name', 'Managed firewall')
            ->missing('services.0.note'));
});

it('is forbidden when no rule lets contacts see services', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    actingAs($contact, 'portal')->get(route('portal.services.index'))->assertForbidden();
});
