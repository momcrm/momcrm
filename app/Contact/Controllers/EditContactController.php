<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Contact\Actions\FindContact;
use App\Contact\Exceptions\CouldNotFindContact;
use App\Customer\Actions\FindCustomer;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditContactController
{
    /**
     * @throws CouldNotFindContact
     */
    public function __invoke(Request $request, string $contact): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindContact::byIdForTeam($contact, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindContact::notFound();
        }

        return Inertia::render('contacts/edit', [
            'contact' => $found->toSummary(),
            'customFieldLabels' => CustomFieldRelationLabels::for($teamId, CustomFieldTargetEnum::Contact, $found->getCustomFields()->toValues()),
            'linkedRecords' => FindLinkedRecords::to($teamId, (string) $request->user()?->id, CustomFieldRelationEnum::Contact, $found->getId()->toString()),
            'customers' => FindCustomer::optionsForTeam($teamId),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Contact, $found),
        ]);
    }
}
