<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankTransaction\Actions\HideBankTransactions;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\BankTransactionSelection;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Hides bank feed lines that aren't customers' payments, such as transfers
 * between the team's own accounts, or brings hidden ones back.
 */
final class HideBankTransactionsFormRequest extends FormRequest
{
    public BankTransactionCollection $transactions;

    public function __construct()
    {
        parent::__construct();

        $this->transactions = new BankTransactionCollection([]);
    }

    /**
     * @throws CouldNotFindBankTransaction
     */
    public function authorize(): Response
    {
        $this->transactions = BankTransactionSelection::authorize($this, 'update');

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...BankTransactionSelection::rules(),
            'hidden' => ['required', 'boolean'],
        ];
    }

    public function process(): RedirectResponse
    {
        $isHidden = $this->boolean('hidden');

        HideBankTransactions::lines($this->transactions, $isHidden);

        Inertia::flash('toast', ['type' => 'success', 'message' => $isHidden
            ? trans_choice(':count line hidden.|:count lines hidden.', $this->transactions->count())
            : trans_choice(':count line back in pending.|:count lines back in pending.', $this->transactions->count())]);

        return back();
    }
}
