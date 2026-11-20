<?php

declare(strict_types=1);

namespace App\BankAccount\FormRequests;

use App\BankAccount\Actions\DeleteBankAccount;
use App\BankAccount\Actions\FindBankAccount;
use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountAccess;
use App\BankAccount\Exceptions\CouldNotFindBankAccount;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Deletes a bank account with its feed lines.
 */
final class DeleteBankAccountFormRequest extends FormRequest
{
    public BankAccount $account;

    public function __construct()
    {
        parent::__construct();

        $this->account = BankAccount::new();
    }

    /**
     * @throws CouldNotViewTeam
     * @throws CouldNotFindBankAccount
     */
    public function authorize(): Response
    {
        $this->account = FindBankAccount::byIdForTeam((string) $this->route('bankAccount'), BankAccountAccess::team($this)->getId()->toString());

        if ($this->account->isNull()) {
            throw CouldNotFindBankAccount::notFound();
        }

        return BankAccountAccess::authorize($this);
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
        DeleteBankAccount::fromBankAccount($this->account);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name deleted.', ['name' => $this->account->getName()->toString()])]);

        return back();
    }
}
