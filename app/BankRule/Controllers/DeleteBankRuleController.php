<?php

declare(strict_types=1);

namespace App\BankRule\Controllers;

use App\BankRule\FormRequests\DeleteBankRuleFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteBankRuleController
{
    public function __invoke(DeleteBankRuleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
