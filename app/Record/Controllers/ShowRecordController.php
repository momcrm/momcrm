<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\RecordAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One record's own page.
 */
final class ShowRecordController
{
    /**
     * @throws CouldNotFindCustomObject
     * @throws CouldNotFindRecord
     */
    public function __invoke(Request $request): Response
    {
        $object = RecordAccess::object($request);
        $record = RecordAccess::record($request, $object);

        return Inertia::render('objects/record', [
            'object' => $object->toSummary(),
            'fields' => FindCustomField::listForTeam($object->getTeamId()->toString(), CustomFieldTargetEnum::Record, objectId: $object->getId()->toString())->toSummaries(),
            'record' => $record->toSummary(),
            'customFieldLabels' => CustomFieldRelationLabels::forObject($object->getTeamId()->toString(), $object->getId()->toString(), $record->getCustomFields()->toValues()),
            'linkedRecords' => FindLinkedRecords::to($object->getTeamId()->toString(), (string) $request->user()?->id, CustomFieldRelationEnum::Record, $record->getId()->toString(), $object->getId()->toString()),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Record, $record),
        ]);
    }
}
