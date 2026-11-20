<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\InvoiceDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * An invoice as the invoice dialog shows it, fetched when the dialog opens
 * over a list.
 */
final class ShowInvoiceDetailsController
{
    /**
     * @throws CouldNotFindInvoice
     */
    public function __invoke(Request $request, string $invoice): JsonResponse
    {
        $found = FindInvoice::byIdForTeam($invoice, (string) $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        return response()->json(InvoiceDocument::for($found)->toArray());
    }
}
