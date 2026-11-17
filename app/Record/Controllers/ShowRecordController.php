<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityRelationship;
use App\Activity\Properties\SubjectId;
use App\Activity\Properties\SubjectType;
use App\Attachment\Actions\FindAttachment;
use App\Attachment\Attachment;
use App\Comment\Actions\FindComments;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\Actions\FindLinkedRecords;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Record\Exceptions\CouldNotFindRecord;
use App\Record\RecordAccess;
use App\Shared\MorphMapKeyEnum;
use App\TeamUser\Actions\FindTeamMembers;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One record's own page: its fields, what links to it, its comments, files
 * and what has happened to it.
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
        /** @var UserEloquentModel $user */
        $user = $request->user();
        $recordId = $record->getId()->toString();

        return Inertia::render('objects/record', [
            'object' => $object->toSummary(),
            'fields' => FindCustomField::listForTeam($object->getTeamId()->toString(), CustomFieldTargetEnum::Record, objectId: $object->getId()->toString())->toSummaries(),
            'record' => $record->toSummary(),
            'customFieldLabels' => CustomFieldRelationLabels::forObject($object->getTeamId()->toString(), $object->getId()->toString(), $record->getCustomFields()->toValues()),
            'linkedRecords' => FindLinkedRecords::to($object->getTeamId()->toString(), (string) $request->user()?->id, CustomFieldRelationEnum::Record, $record->getId()->toString(), $object->getId()->toString()),
            'comments' => FindComments::itemsForRecord($recordId, $user),
            'attachments' => FindAttachment::forAttachable(MorphMapKeyEnum::Record, $recordId)
                ->map(fn (Attachment $attachment): array => FindAttachment::listItem($attachment))
                ->values()
                ->all(),
            'activity' => ActivityEloquentModel::query()
                ->with(ActivityRelationship::Actor->value)
                ->where(SubjectType::getDatabaseTableColumnName(), MorphMapKeyEnum::Record->value)
                ->where(SubjectId::getDatabaseTableColumnName(), $recordId)
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (ActivityEloquentModel $activity): array => Activity::fromEloquentModel($activity)->toListItem())
                ->values()
                ->all(),
            'members' => FindTeamMembers::optionsForTeam($object->getTeamId()->toString()),
            'attachmentCan' => AccessAbilities::for($user, AccessResourceEnum::Attachment),
            'commentCan' => AccessAbilities::for($user, AccessResourceEnum::Comment),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Record, $record),
        ]);
    }
}
