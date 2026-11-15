<?php

declare(strict_types=1);

namespace App\AccessRule\Engine;

use App\AccessRule\AccessAttributeEnum;
use App\Customer\Customer;
use App\Group\Group;
use App\Group\GroupRoleEnum;
use App\Invoice\Invoice;
use App\Project\Project;
use App\Shared\RequestMemo;
use App\Shared\Table;
use App\Shared\Types;
use App\Ticket\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mom\Data\AbstractData;

/**
 * Reads an attribute's value for an access decision. Returns null when there
 * is nothing to read, such as a record attribute while creating a record.
 */
final class AccessAttributeResolver
{
    /**
     * @param  AbstractData<covariant Model>|null  $record
     * @return string|array<int, string>|bool|null
     */
    public static function resolve(AccessAttributeEnum $attribute, AccessSubject $subject, ?AbstractData $record): string|array|bool|null
    {
        return match ($attribute) {
            AccessAttributeEnum::SubjectTeamRole => $subject->role?->value,
            AccessAttributeEnum::SubjectUser => $subject->userId,
            AccessAttributeEnum::SubjectGroup => $subject->groupIds,
            AccessAttributeEnum::SubjectLeadsGroup => $subject->leadGroupIds,

            AccessAttributeEnum::ProjectStatus => $record instanceof Project ? $record->getStatus()->toNullableString() : null,
            AccessAttributeEnum::ProjectVisibility => $record instanceof Project ? $record->getVisibility()->toNullableString() : null,
            AccessAttributeEnum::ProjectGroup => $record instanceof Project ? $record->getGroupId()->toNullableString() : null,
            AccessAttributeEnum::ProjectIsArchived => $record instanceof Project ? $record->getArchivedAt()->isNotNull() : null,
            AccessAttributeEnum::ProjectSubjectIsLead => $record instanceof Project ? $record->getLeadId()->equalsTo($subject->userId) : null,
            AccessAttributeEnum::ProjectSubjectIsMember => $record instanceof Project ? self::isProjectMember($record->getId()->toString(), $subject->userId) : null,

            AccessAttributeEnum::TicketStatus => $record instanceof Ticket ? $record->getStatus()->toNullableString() : null,
            AccessAttributeEnum::TicketPriority => $record instanceof Ticket ? $record->getPriority()->toNullableString() : null,
            AccessAttributeEnum::TicketProject => $record instanceof Ticket ? $record->getProjectId()->toNullableString() : null,
            AccessAttributeEnum::TicketLabel => $record instanceof Ticket ? self::ticketLabelIds($record->getId()->toString()) : null,
            AccessAttributeEnum::TicketIsArchived => $record instanceof Ticket ? $record->getArchivedAt()->isNotNull() : null,
            AccessAttributeEnum::TicketSubjectIsAssignee => $record instanceof Ticket ? $record->getAssigneeId()->equalsTo($subject->userId) : null,
            AccessAttributeEnum::TicketSubjectIsProjectLead => $record instanceof Ticket
                ? $record->getProjectId()->isNotNull() && self::projectLeadId($record->getProjectId()->toString()) === $subject->userId
                : null,
            AccessAttributeEnum::TicketSubjectIsProjectMember => $record instanceof Ticket
                ? $record->getProjectId()->isNotNull() && self::isProjectMember($record->getProjectId()->toString(), $subject->userId)
                : null,

            AccessAttributeEnum::CustomerStatus => $record instanceof Customer ? $record->getStatus()->toNullableString() : null,
            AccessAttributeEnum::InvoiceStatus => $record instanceof Invoice ? $record->getStatus()->toNullableString() : null,

            AccessAttributeEnum::GroupSubjectIsLead => $record instanceof Group ? self::groupRole($record->getId()->toString(), $subject->userId) === GroupRoleEnum::Lead->value : null,
            AccessAttributeEnum::GroupSubjectIsMember => $record instanceof Group ? self::groupRole($record->getId()->toString(), $subject->userId) !== null : null,
        };
    }

    private static function isProjectMember(string $projectId, string $userId): bool
    {
        return RequestMemo::remember("access-project-member.{$projectId}.{$userId}", fn (): bool => DB::table(Table::ProjectUser->value)
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->exists());
    }

    private static function projectLeadId(string $projectId): ?string
    {
        return RequestMemo::remember("access-project-lead.{$projectId}", function () use ($projectId): ?string {
            $leadId = DB::table(Table::Projects->value)->where('id', $projectId)->value('lead_id');

            return is_string($leadId) ? $leadId : null;
        });
    }

    /**
     * @return array<int, string>
     */
    private static function ticketLabelIds(string $ticketId): array
    {
        return RequestMemo::remember("access-ticket-labels.{$ticketId}", fn (): array => DB::table(Table::LabelTicket->value)
            ->where('ticket_id', $ticketId)
            ->pluck('label_id')
            ->map(Types::string(...))
            ->values()
            ->all());
    }

    private static function groupRole(string $groupId, string $userId): ?string
    {
        return RequestMemo::remember("access-group-role.{$groupId}.{$userId}", function () use ($groupId, $userId): ?string {
            $role = DB::table(Table::GroupUser->value)->where('group_id', $groupId)->where('user_id', $userId)->value('role');

            return is_string($role) ? $role : null;
        });
    }
}
