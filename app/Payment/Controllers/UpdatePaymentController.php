<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\FormRequests\UpdatePaymentFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdatePaymentController
{
    public function __invoke(UpdatePaymentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
