<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\Exceptions\CouldNotFindCustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The team's records a record-link field can point at, matching a search,
 * for the field's picker. Any member of the team may look them up.
 */
final class ListCustomFieldRecordsController
{
    /**
     * @throws CouldNotFindCustomField
     */
    public function __invoke(Request $request, string $customField): JsonResponse
    {
        $teamId = (string) $request->user()?->current_team_id;
        $field = FindCustomField::byIdForTeam($customField, $teamId);
        $relation = $field->getRelation()->toNullableEnum();

        if ($relation === null) {
            throw CouldNotFindCustomField::notFound();
        }

        return response()->json([
            'records' => $relation->search($teamId, $request->string('search')->trim()->toString(), objectId: $field->getRelationObjectId()->toNullableString()),
        ]);
    }
}
