<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceAccess;
use App\Choice\ChoiceListEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's customisable lists (ticket statuses and priorities), for owners
 * and admins to edit, with how many records use each choice.
 */
final class ShowWorkflowController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        ChoiceAccess::authorize($request);
        $teamId = ChoiceAccess::team($request)->getId()->toString();

        return Inertia::render('settings/workflow', [
            'lists' => array_map(function (ChoiceListEnum $list) use ($teamId): array {
                $usage = FindChoice::usageCountsForTeam($teamId, $list);

                return [
                    'value' => $list->value,
                    'label' => $list->label(),
                    'categories' => $list->categoryOptions(),
                    'icons' => $list->icons(),
                    'choices' => FindChoice::listForTeam($teamId, $list)
                        ->map(fn (Choice $choice): array => [...$choice->toSummary(), 'usage' => $usage[$choice->getId()->toString()] ?? 0])
                        ->values()
                        ->all(),
                ];
            }, ChoiceListEnum::cases()),
        ]);
    }
}
