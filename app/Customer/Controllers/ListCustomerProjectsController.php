<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Project\Actions\FindProject;
use App\Project\Controllers\ListProjectsController;
use App\Project\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's projects tab.
 */
final class ListCustomerProjectsController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);

        return Inertia::render('customers/workspace/projects', [
            ...$workspace->props($request),
            'projects' => FindProject::forCustomer($workspace->teamId, $workspace->userId, $workspace->customerId())
                ->map(fn (Project $project): array => ListProjectsController::listItem($project, []))
                ->values()
                ->all(),
        ]);
    }
}
