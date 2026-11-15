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
 * The team's customisable lists (ticket statuses, priorities, types and labels), for owners
 * and admins to edit, with how many records use each choice. `?list=` opens
 * one list's editor; without it the page shows an overview of every list.
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

        $lists = [ChoiceListEnum::TicketStatus, ChoiceListEnum::TicketPriority, ChoiceListEnum::TicketType, ChoiceListEnum::Label];
        $current = ChoiceListEnum::tryFrom($request->string('list')->toString());

        return Inertia::render('settings/workflow', [
            'current' => in_array($current, $lists, true) ? $current->value : null,
            'lists' => array_map(function (ChoiceListEnum $list) use ($teamId): array {
                $usage = FindChoice::usageCountsForTeam($teamId, $list);

                return [
                    'value' => $list->value,
                    'label' => $list->label(),
                    'description' => $list->description(),
                    'categories' => $list->categoryOptions(),
                    'icons' => $list->icons(),
                    'is_optional' => $list->isOptional(),
                    'choices' => FindChoice::listForTeam($teamId, $list, withArchived: true)
                        ->map(fn (Choice $choice): array => [...$choice->toSummary(), 'usage' => $usage[$choice->getId()->toString()] ?? 0])
                        ->values()
                        ->all(),
                ];
            }, $lists),
        ]);
    }
}
