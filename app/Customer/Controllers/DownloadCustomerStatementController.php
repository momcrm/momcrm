<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerStatement;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A customer's statement for a period as a PDF. Shown in the browser unless
 * ?download=1 asks for it to be saved.
 */
final class DownloadCustomerStatementController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $found = FindCustomer::byIdForTeam($customer, (string) $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        [$from, $to] = CustomerStatement::periodFrom($request);
        $statement = CustomerStatement::for($found, $from, $to);
        $pdf = Pdf::loadView('customers.statement', ['statement' => $statement])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($statement->fileName())
            : $pdf->stream($statement->fileName());
    }
}
