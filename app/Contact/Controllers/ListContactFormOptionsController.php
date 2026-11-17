<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Customer\Actions\FindCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customers to choose from in the new-contact dialog, fetched when it opens.
 */
final class ListContactFormOptionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'customers' => FindCustomer::optionsForTeam((string) $request->user()?->current_team_id),
        ]);
    }
}
