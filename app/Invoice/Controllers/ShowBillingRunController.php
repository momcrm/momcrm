<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\BillingRun;
use App\Invoice\Invoice;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The billing run, as Amphora's finance billing does it: every customer the
 * monthly run would bill on a day, with the lines they are due, to check
 * before drafting their invoices.
 */
final class ShowBillingRunController
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('create', Invoice::class);

        $team = FindTeam::byId((string) $request->user()?->current_team_id);
        $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->string('date')->toString()) === 1
            ? CarbonImmutable::parse($request->string('date')->toString())
            : CarbonImmutable::today();

        return Inertia::render('invoices/run', [
            'date' => $day->toDateString(),
            'customers' => BillingRun::for($team, $day)->toArray($team),
        ]);
    }
}
