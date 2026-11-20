<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Contact\Exceptions\CouldNotFindContact;
use App\Portal\FormRequests\ViewPortalAsContactFormRequest;
use Illuminate\Http\RedirectResponse;

final class ViewPortalAsContactController
{
    /**
     * @throws CouldNotFindContact
     */
    public function __invoke(ViewPortalAsContactFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
