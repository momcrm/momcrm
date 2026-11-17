<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldAccess;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The fields the team has added to each kind of record, for owners and admins
 * to edit. `?target=` picks which kind of record is open.
 */
final class ShowCustomFieldsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        CustomFieldAccess::authorize($request);
        $teamId = CustomFieldAccess::team($request)->getId()->toString();

        $current = CustomFieldTargetEnum::tryFrom($request->string('target')->toString()) ?? CustomFieldTargetEnum::Ticket;

        return Inertia::render('settings/custom-fields', [
            'current' => $current->value,
            'targets' => CustomFieldTargetEnum::options(),
            'types' => array_map(fn (CustomFieldTypeEnum $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'has_options' => $type->hasOptions(),
            ], CustomFieldTypeEnum::cases()),
            'relations' => CustomFieldRelationEnum::options(),
            'fields' => FindCustomField::listForTeam($teamId, $current, withArchived: true)->toSummaries(),
        ]);
    }
}
