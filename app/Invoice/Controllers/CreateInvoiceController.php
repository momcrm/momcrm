<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\InvoiceFormOptions;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreateInvoiceController
{
    public const int DEFAULT_DAYS_TO_PAY = 14;

    public function __invoke(Request $request): Response
    {
        $today = CarbonImmutable::today();

        return Inertia::render('invoices/create', [
            ...InvoiceFormOptions::forTeam((string) $request->user()?->current_team_id),
            'customerId' => $request->string('customer')->toString() ?: null,
            'defaults' => [
                'issued_on' => $today->toDateString(),
                'due_on' => $today->addDays(self::DEFAULT_DAYS_TO_PAY)->toDateString(),
                'tax_rate_basis_points' => 1000,
            ],
        ]);
    }
}
