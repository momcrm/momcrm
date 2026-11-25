<?php

declare(strict_types=1);

namespace App\Conversation\Controllers;

use App\Conversation\FormRequests\StartConversationFormRequest;
use Illuminate\Http\RedirectResponse;

final class StartConversationController
{
    public function __invoke(StartConversationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
