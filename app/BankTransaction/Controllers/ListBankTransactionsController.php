<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankAccount\Actions\FindBankAccount;
use App\BankTransaction\Actions\FindBankTransaction;
use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionFilters;
use App\BankTransaction\BankTransactionStatusEnum;
use App\BankTransaction\FormRequests\ImportBankStatementFormRequest;
use App\BankTransaction\Statements\CsvStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The bank feed: deposits imported from the team's bank statements, pending
 * ones first, as Amphora's bank feed list.
 */
final class ListBankTransactionsController
{
    public function __invoke(Request $request): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $filters = BankTransactionFilters::fromRequest($request);

        return Inertia::render('bank-feeds/index', [
            'transactions' => FindBankTransaction::paginateForTeam($teamId, $filters)
                ->through(fn (BankTransaction $transaction): array => $transaction->toSummary()),
            'filters' => $filters->toArray(),
            'accounts' => FindBankAccount::listForTeam($teamId)->toSummaries(),
            'statuses' => BankTransactionStatusEnum::options(),
            'limits' => [
                'files' => ImportBankStatementFormRequest::MAX_FILES,
                'rows' => CsvStatement::MAX_ROWS,
            ],
            'can' => [
                'import' => Gate::allows('import', BankTransaction::class),
            ],
        ]);
    }
}
