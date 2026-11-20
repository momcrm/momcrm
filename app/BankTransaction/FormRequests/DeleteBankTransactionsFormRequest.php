<?php

declare(strict_types=1);

namespace App\BankTransaction\FormRequests;

use App\BankTransaction\Actions\DeleteBankTransactions;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\BankTransactionSelection;
use App\BankTransaction\Exceptions\CouldNotFindBankTransaction;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Deletes bank feed lines for good. Importing the same statement again
 * brings them back.
 */
final class DeleteBankTransactionsFormRequest extends FormRequest
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
        $this->transactions = BankTransactionSelection::authorize($this, 'delete');

        return Response::allow();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return BankTransactionSelection::rules();
    }

    public function process(): RedirectResponse
    {
        DeleteBankTransactions::lines($this->transactions);

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count line deleted.|:count lines deleted.', $this->transactions->count())]);

        return back();
    }
}
