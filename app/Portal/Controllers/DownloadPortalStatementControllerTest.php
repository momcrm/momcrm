<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

it("downloads the customer's statement as a PDF", function (): void {
    $response = actingAs(portalContact(['is_primary' => true]), 'portal')
        ->get(route('portal.statement.pdf', ['to' => '2026-11-30', 'download' => 1]));

    $response->assertOk()->assertDownload('Statement Coastal Logistics 2026-11-30.pdf');
});

it('is forbidden to a contact who may not see billing', function (): void {
    actingAs(portalContact(), 'portal')->get(route('portal.statement.pdf'))->assertForbidden();
});
