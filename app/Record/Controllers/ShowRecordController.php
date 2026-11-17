<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
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
            'record' => $record->toSummary(),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Record, $record),
        ]);
    }
}
