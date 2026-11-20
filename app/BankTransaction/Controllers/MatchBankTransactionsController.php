<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankTransaction\FormRequests\MatchBankTransactionsFormRequest;
use Illuminate\Http\RedirectResponse;

final class MatchBankTransactionsController
{
    public function __invoke(MatchBankTransactionsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
