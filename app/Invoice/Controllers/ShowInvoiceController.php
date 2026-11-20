<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\InvoiceDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowInvoiceController
{
    /**
     * @throws CouldNotFindInvoice
     */
    public function __invoke(Request $request, string $invoice): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindInvoice::byIdForTeam($invoice, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        return Inertia::render('invoices/show', [
            ...InvoiceDocument::for($found)->toArray(),
            'customFieldLabels' => CustomFieldRelationLabels::for($teamId, CustomFieldTargetEnum::Invoice, $found->getCustomFields()->toValues()),
            'linkedRecords' => FindLinkedRecords::to($teamId, (string) $request->user()?->id, CustomFieldRelationEnum::Invoice, $found->getId()->toString()),
        ]);
    }
}
