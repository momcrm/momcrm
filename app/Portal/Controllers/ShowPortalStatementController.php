<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerStatement;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's account over a period: what was charged and paid, the
 * balance, and how old what they owe is.
 */
final class ShowPortalStatementController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Request $request): Response
    {
        PortalAccess::authorize(AccessResourceEnum::Statement, AccessActionEnum::View);

        $contact = PortalContact::current();
        $customer = FindCustomer::byIdForTeam($contact->getCustomerId()->toString(), $contact->getTeamId()->toString());
        [$from, $to] = CustomerStatement::periodFrom($request);

        return Inertia::render('portal/statement', [
            'statement' => CustomerStatement::for($customer, $from, $to)->toArray(),
            'canSeeInvoices' => PortalAccess::maySeeSome(AccessResourceEnum::Invoice),
            'canSeeReceipts' => PortalAccess::maySeeSome(AccessResourceEnum::Payment),
        ]);
    }
}
