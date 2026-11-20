<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use App\Service\Actions\FindService;
use App\Service\ServiceCollection;
use App\Service\ServiceLifecycleEnum;
use App\Shared\Types;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's services: what they get, where, and what it costs each
 * billing period. Archived services and staff notes are left out.
 */
final class ListPortalServicesController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(): Response
    {
        PortalAccess::authorizeSection(AccessResourceEnum::Service);

        $contact = PortalContact::current();
        $services = FindService::forCustomer($contact->getCustomerId()->toString(), $contact->getTeamId()->toString())->withoutArchived();
        $visible = ServiceCollection::make(PortalAccess::visible(AccessResourceEnum::Service, $services)->all());

        return Inertia::render('portal/services', [
            'services' => array_map(fn (array $item): array => [
                'id' => Types::string($item['id']),
                'name' => Types::nullableString($item['name']),
                'short_name' => Types::nullableString($item['short_name']),
                'status' => ServiceLifecycleEnum::from(Types::string($item['lifecycle']))->label(),
                'is_active' => $item['lifecycle'] === ServiceLifecycleEnum::Active->value,
                'quantity' => $item['quantity'],
                'currency' => Types::string($item['currency']),
                'rate_name' => is_array($item['rate']) ? Types::nullableString($item['rate']['name'] ?? null) : null,
                'rental_in_cents' => $item['rental_in_cents'],
                'billing_period_in_months' => $item['effective_billing_period_in_months'],
                'billing_starts_on' => Types::nullableString($item['billing_starts_on']),
                'ends_on' => Types::nullableString($item['ends_on']),
                'location_name' => Types::nullableString($item['location_name']),
            ], $visible->toListItems()),
        ]);
    }
}
