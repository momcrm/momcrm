<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\Exceptions\CouldNotFindContact;
use App\Contact\FormRequests\InviteContactToPortalFormRequest;
use Illuminate\Http\RedirectResponse;

final class InviteContactToPortalController
{
    /**
     * @throws CouldNotFindContact
     */
    public function __invoke(InviteContactToPortalFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
