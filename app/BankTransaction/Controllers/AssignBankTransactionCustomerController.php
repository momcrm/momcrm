<?php

declare(strict_types=1);

namespace App\BankTransaction\Controllers;

use App\BankTransaction\FormRequests\AssignBankTransactionCustomerFormRequest;
use Illuminate\Http\RedirectResponse;

final class AssignBankTransactionCustomerController
{
    public function __invoke(AssignBankTransactionCustomerFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
