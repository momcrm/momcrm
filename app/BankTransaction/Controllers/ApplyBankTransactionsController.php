<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankTransaction\FormRequests\ApplyBankTransactionsFormRequest;
use Illuminate\Http\RedirectResponse;

final class ApplyBankTransactionsController
{
    public function __invoke(ApplyBankTransactionsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
