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
use App\Customer\Actions\FindCustomer;
use App\Payment\Payment;
use App\Shared\CurrencyEnum;
use App\Shared\Types;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The bank feed: deposits imported from the team's bank statements, pending
 * ones first, as Amphora's bank feed list. The customers to pick from are
 * only sent when asked for.
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
            'customers' => Inertia::optional(fn (): array => self::customers($teamId)),
            'can' => [
                'import' => Gate::allows('import', BankTransaction::class),
                'apply' => Gate::allows('create', Payment::class),
            ],
        ]);
    }

    /**
     * The team's customers with the currency each is billed in, for picking
     * who a line is from. Loaded when the picker opens.
     *
     * @return list<array{id: string, name: string, currency: string}>
     */
    private static function customers(string $teamId): array
    {
        $options = FindCustomer::optionsForTeam($teamId);
        $currencies = FindCustomer::currenciesByIds(Types::stringList($options->pluck('id')->all()));

        return array_values($options
            ->map(fn (array $option): array => [...$option, 'currency' => ($currencies[$option['id']] ?? CurrencyEnum::default())->value])
            ->all());
    }
}
