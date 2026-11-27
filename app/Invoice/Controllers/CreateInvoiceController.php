<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * An invoice is created in a dialog over the page it was asked for from, so
 * a link to the old page opens that dialog over the invoices, for the
 * customer it named.
 */
final class CreateInvoiceController
{
    public function __invoke(Request $request): RedirectResponse
    {
        return to_route('invoices.index', array_filter([
            'new' => 1,
            'customer' => $request->string('customer')->toString() ?: null,
        ]));
    }
}
