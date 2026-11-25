<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Portal\PortalCustomerTicketsChannel;

it("lets a contact listen to their own customer's tickets only", function (): void {
    $contact = portalContact();
    $other = portalContact(['email' => 'pat@elsewhere.test']);

    expect(new PortalCustomerTicketsChannel()->join($contact, $contact->customer_id))->toBeTrue()
        ->and(new PortalCustomerTicketsChannel()->join($contact, $other->customer_id))->toBeFalse();
});

it('keeps a contact off when no rule lets them see tickets', function (): void {
    $contact = portalContact();
    AccessRuleEloquentModel::query()->withoutGlobalScopes()->where('team_id', $contact->team_id)->where('audience', 'contact')->delete();

    expect(new PortalCustomerTicketsChannel()->join($contact, $contact->customer_id))->toBeFalse();
});
