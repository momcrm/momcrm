<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessResourceEnum;
use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerStatement;
use App\Portal\PortalAccess;
use App\Portal\PortalContact;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The customer's statement for a period as a PDF, the same one staff send.
 */
final class DownloadPortalStatementController
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
        $statement = CustomerStatement::for($customer, $from, $to);
        $pdf = Pdf::loadView('customers.statement', ['statement' => $statement])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($statement->fileName())
            : $pdf->stream($statement->fileName());
    }
}
