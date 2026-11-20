<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankTransaction\FormRequests\HideBankTransactionsFormRequest;
use Illuminate\Http\RedirectResponse;

final class HideBankTransactionsController
{
    public function __invoke(HideBankTransactionsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
