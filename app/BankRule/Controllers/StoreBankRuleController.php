<?php

declare(strict_types=1);

namespace App\BankRule\Controllers;

use App\BankRule\FormRequests\StoreBankRuleFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreBankRuleController
{
    public function __invoke(StoreBankRuleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
