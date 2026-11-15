<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Table;
use App\Shared\Traits\Enum;

/**
 * What a rule's conditions can look at: the person acting (subject.*) or the
 * record they are acting on. The values are stored in access_rules.conditions,
 * so never change an existing value.
 */
enum AccessAttributeEnum: string
{
    use Enum;

    case AssetLabel = 'asset.label';
    case CustomerStatus = 'customer.status';
    case GroupSubjectIsLead = 'group.subject_is_lead';
    case GroupSubjectIsMember = 'group.subject_is_member';
    case InvoiceStatus = 'invoice.status';
    case ProjectGroup = 'project.group';
    case ProjectIsArchived = 'project.is_archived';
    case ProjectLabel = 'project.label';
    case ProjectStatus = 'project.status';
    case ProjectSubjectIsLead = 'project.subject_is_lead';
    case ProjectSubjectIsMember = 'project.subject_is_member';
    case ProjectVisibility = 'project.visibility';
    case SubjectGroup = 'subject.group';
    case SubjectLeadsGroup = 'subject.leads_group';
    case SubjectTeamRole = 'subject.team_role';
    case SubjectUser = 'subject.user';
    case TicketGroup = 'ticket.group';
    case TicketIsArchived = 'ticket.is_archived';
    case TicketLabel = 'ticket.label';
    case TicketPriority = 'ticket.priority';
    case TicketProject = 'ticket.project';
    case TicketStatus = 'ticket.status';
    case TicketSubjectIsAssignee = 'ticket.subject_is_assignee';
    case TicketSubjectIsGroupLead = 'ticket.subject_is_group_lead';
    case TicketSubjectIsGroupMember = 'ticket.subject_is_group_member';
    case TicketSubjectIsProjectLead = 'ticket.subject_is_project_lead';
    case TicketSubjectIsProjectMember = 'ticket.subject_is_project_member';
    case TicketType = 'ticket.type';

    public function label(): string
    {
        return match ($this) {
            self::AssetLabel => 'Asset has label',
            self::CustomerStatus => 'Customer status',
            self::GroupSubjectIsLead => 'They lead the group',
            self::GroupSubjectIsMember => 'They are in the group',
            self::InvoiceStatus => 'Invoice status',
            self::ProjectGroup => 'Project group',
            self::ProjectIsArchived => 'Project is archived',
            self::ProjectLabel => 'Project has label',
            self::ProjectStatus => 'Project status',
            self::ProjectSubjectIsLead => 'They lead the project',
            self::ProjectSubjectIsMember => 'They are a project member',
            self::ProjectVisibility => 'Project visibility',
            self::SubjectGroup => 'They are in group',
            self::SubjectLeadsGroup => 'They lead group',
            self::SubjectTeamRole => 'Their team role',
            self::SubjectUser => 'They are',
            self::TicketGroup => 'Ticket group',
            self::TicketIsArchived => 'Ticket is archived',
            self::TicketLabel => 'Ticket has label',
            self::TicketPriority => 'Ticket priority',
            self::TicketProject => 'Ticket project',
            self::TicketStatus => 'Ticket status',
            self::TicketSubjectIsAssignee => 'They are the assignee',
            self::TicketSubjectIsGroupLead => "They lead the ticket's group",
            self::TicketSubjectIsGroupMember => "They are in the ticket's group",
            self::TicketSubjectIsProjectLead => "They lead the ticket's project",
            self::TicketSubjectIsProjectMember => "They are a member of the ticket's project",
            self::TicketType => 'Ticket type',
        };
    }

    /**
     * The resource whose records the attribute reads, or null for an attribute
     * of the person acting, which every rule can use.
     */
    public function resource(): ?AccessResourceEnum
    {
        return match ($this) {
            self::AssetLabel => AccessResourceEnum::Asset,
            self::CustomerStatus => AccessResourceEnum::Customer,
            self::GroupSubjectIsLead, self::GroupSubjectIsMember => AccessResourceEnum::Group,
            self::InvoiceStatus => AccessResourceEnum::Invoice,
            self::ProjectGroup, self::ProjectIsArchived, self::ProjectLabel, self::ProjectStatus, self::ProjectSubjectIsLead, self::ProjectSubjectIsMember, self::ProjectVisibility => AccessResourceEnum::Project,
            self::TicketGroup, self::TicketIsArchived, self::TicketLabel, self::TicketPriority, self::TicketProject, self::TicketStatus, self::TicketSubjectIsAssignee, self::TicketSubjectIsGroupLead, self::TicketSubjectIsGroupMember, self::TicketSubjectIsProjectLead, self::TicketSubjectIsProjectMember, self::TicketType => AccessResourceEnum::Ticket,
            self::SubjectGroup, self::SubjectLeadsGroup, self::SubjectTeamRole, self::SubjectUser => null,
        };
    }

    public function type(): AccessAttributeTypeEnum
    {
        return match ($this) {
            self::GroupSubjectIsLead,
            self::GroupSubjectIsMember,
            self::ProjectIsArchived,
            self::ProjectSubjectIsLead,
            self::ProjectSubjectIsMember,
            self::TicketIsArchived,
            self::TicketSubjectIsAssignee,
            self::TicketSubjectIsGroupLead,
            self::TicketSubjectIsGroupMember,
            self::TicketSubjectIsProjectLead,
            self::TicketSubjectIsProjectMember => AccessAttributeTypeEnum::Boolean,
            default => AccessAttributeTypeEnum::Choice,
        };
    }

    /**
     * The table whose record ids this attribute's conditions hold, if any, so
     * conditions can be tidied when one of those records is deleted.
     */
    public function referencedTable(): ?Table
    {
        return match ($this) {
            self::ProjectGroup, self::SubjectGroup, self::SubjectLeadsGroup, self::TicketGroup => Table::Groups,
            self::AssetLabel, self::ProjectLabel, self::TicketLabel, self::TicketPriority, self::TicketStatus, self::TicketType => Table::Choices,
            self::TicketProject => Table::Projects,
            default => null,
        };
    }

    /**
     * Whether the attribute holds several values at once.
     */
    public function isList(): bool
    {
        return in_array($this, [self::AssetLabel, self::ProjectLabel, self::SubjectGroup, self::SubjectLeadsGroup, self::TicketLabel], true);
    }

    /**
     * Whether a rule covering these resources may use this attribute: subject
     * attributes always, record attributes only on a rule for that one resource.
     *
     * @param  array<int, AccessResourceEnum>  $resources
     */
    public function appliesTo(array $resources): bool
    {
        $resource = $this->resource();

        return $resource === null || $resources === [$resource];
    }
}
