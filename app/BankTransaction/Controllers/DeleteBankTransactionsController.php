<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankTransaction\FormRequests\DeleteBankTransactionsFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteBankTransactionsController
{
    public function __invoke(DeleteBankTransactionsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
