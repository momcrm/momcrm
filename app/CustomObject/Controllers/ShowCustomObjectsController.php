<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomField\Actions\FindCustomField;
use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObject;
use App\CustomObject\CustomObjectAccess;
use App\CustomObject\StandardObjectEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every kind of record the team works with, for owners and admins: the ones
 * built into the app, then the ones the team has defined for itself and can
 * edit here. Each opens its fields.
 */
final class ShowCustomObjectsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        CustomObjectAccess::authorize($request);

        $teamId = CustomObjectAccess::team($request)->getId()->toString();
        $counts = FindCustomField::countsForTeam($teamId);

        return Inertia::render('settings/objects', [
            'standardObjects' => array_map(fn (StandardObjectEnum $object): array => [
                'key' => $object->value,
                'label' => $object->label(),
                'icon' => $object->icon(),
                'fields_count' => count($object->fields()),
                'custom_fields_count' => $counts[$object->value] ?? 0,
            ], StandardObjectEnum::cases()),
            'objects' => array_values(FindCustomObject::listForTeam($teamId, withArchived: true)
                ->map(fn (CustomObject $object): array => [...$object->toSummary(), 'custom_fields_count' => $counts[$object->getId()->toString()] ?? 0])
                ->all()),
        ]);
    }
}
