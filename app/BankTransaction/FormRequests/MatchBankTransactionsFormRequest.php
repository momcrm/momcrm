<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankTransaction\Actions\FindBankTransaction;
use App\BankTransaction\Actions\MatchBankTransactions;
use App\BankTransaction\BankTransactionCollection;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Applies the rules again and tries again to find the customer of every
 * pending line that has none.
 */
final class MatchBankTransactionsFormRequest extends FormRequest
{
    public BankTransactionCollection $transactions;

    public function __construct()
    {
        parent::__construct();

        $this->transactions = new BankTransactionCollection([]);
    }

    public function authorize(): Response
    {
        $this->transactions = FindBankTransaction::unmatchedForTeam((string) $this->user()?->current_team_id);

        foreach ($this->transactions as $transaction) {
            Gate::authorize('update', $transaction);
        }

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function process(): RedirectResponse
    {
        $count = $this->transactions->count();
        $result = MatchBankTransactions::lines($this->transactions);
        $message = $count === 0
            ? __('Every pending line already has a customer.')
            : __('Found the customer for :matched of :count lines.', ['matched' => $result['matched'], 'count' => $count]);

        if ($result['hidden'] > 0) {
            $message .= ' '.trans_choice(':count was hidden by a rule.|:count were hidden by a rule.', $result['hidden']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
