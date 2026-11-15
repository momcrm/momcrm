<?php

declare(strict_types=1);

use App\AccessRule\AccessAttributeEnum;
use App\AccessRule\Engine\AccessAttributeResolver;
use App\AccessRule\Engine\AccessSubject;
use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Group\Group;
use App\Group\GroupEloquentModel;
use App\Invoice\Invoice;
use App\Invoice\InvoiceEloquentModel;
use App\Label\LabelEloquentModel;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

/**
 * @return array{user: UserEloquentModel, teamId: string, subject: Closure(): AccessSubject}
 */
function resolverSetup(): array
{
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = (string) $owner->current_team_id;
    $user = UserEloquentModel::factory()->create(['current_team_id' => $teamId]);
    CreateTeamUser::fromIds(teamId: $teamId, userId: $user->id, role: TeamRoleEnum::Admin);

    return ['user' => $user, 'teamId' => $teamId, 'subject' => fn (): AccessSubject => AccessSubject::for($user->id, $teamId)];
}

it("reads the person's role, identity and groups", function (): void {
    ['user' => $user, 'teamId' => $teamId, 'subject' => $subject] = resolverSetup();
    $led = GroupEloquentModel::factory()->create(['team_id' => $teamId]);
    $joined = GroupEloquentModel::factory()->create(['team_id' => $teamId]);
    $led->members()->attach($user->id, ['role' => 'lead']);
    $joined->members()->attach($user->id, ['role' => 'member']);
    GroupEloquentModel::factory()->create()->members()->attach($user->id, ['role' => 'lead']);

    expect(AccessAttributeResolver::resolve(AccessAttributeEnum::SubjectTeamRole, $subject(), null))->toBe('admin')
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::SubjectUser, $subject(), null))->toBe($user->id)
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::SubjectGroup, $subject(), null))->toEqualCanonicalizing([$led->id, $joined->id])
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::SubjectLeadsGroup, $subject(), null))->toBe([$led->id]);
});

it("reads a project's attributes and the person's place in it", function (): void {
    ['user' => $user, 'teamId' => $teamId, 'subject' => $subject] = resolverSetup();
    $group = GroupEloquentModel::factory()->create(['team_id' => $teamId]);
    $model = ProjectEloquentModel::factory()->archived()->create(['team_id' => $teamId, 'status' => 'at_risk', 'visibility' => 'restricted', 'group_id' => $group->id, 'lead_id' => $user->id]);
    $model->members()->attach($user->id);
    $project = Project::fromEloquentModel($model);
    $other = Project::fromEloquentModel(ProjectEloquentModel::factory()->create(['team_id' => $teamId]));
    $resolve = fn (AccessAttributeEnum $attribute, Project $project): mixed => AccessAttributeResolver::resolve($attribute, $subject(), $project);

    expect($resolve(AccessAttributeEnum::ProjectStatus, $project))->toBe('at_risk')
        ->and($resolve(AccessAttributeEnum::ProjectVisibility, $project))->toBe('restricted')
        ->and($resolve(AccessAttributeEnum::ProjectGroup, $project))->toBe($group->id)
        ->and($resolve(AccessAttributeEnum::ProjectIsArchived, $project))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::ProjectSubjectIsLead, $project))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::ProjectSubjectIsMember, $project))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::ProjectIsArchived, $other))->toBeFalse()
        ->and($resolve(AccessAttributeEnum::ProjectSubjectIsLead, $other))->toBeFalse()
        ->and($resolve(AccessAttributeEnum::ProjectSubjectIsMember, $other))->toBeFalse();
});

it("reads a ticket's attributes and the person's place in its project", function (): void {
    ['user' => $user, 'teamId' => $teamId, 'subject' => $subject] = resolverSetup();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'lead_id' => $user->id]);
    $project->members()->attach($user->id);
    $label = LabelEloquentModel::factory()->create(['team_id' => $teamId]);
    $model = TicketEloquentModel::factory()->create(['team_id' => $teamId, 'project_id' => $project->id, 'assignee_id' => $user->id, 'status' => 'in_progress', 'priority' => 'urgent']);
    $model->labels()->attach($label->id);
    $ticket = Ticket::fromEloquentModel($model);
    $loose = Ticket::fromEloquentModel(TicketEloquentModel::factory()->archived()->create(['team_id' => $teamId]));
    $resolve = fn (AccessAttributeEnum $attribute, Ticket $ticket): mixed => AccessAttributeResolver::resolve($attribute, $subject(), $ticket);

    expect($resolve(AccessAttributeEnum::TicketStatus, $ticket))->toBe('in_progress')
        ->and($resolve(AccessAttributeEnum::TicketPriority, $ticket))->toBe('urgent')
        ->and($resolve(AccessAttributeEnum::TicketProject, $ticket))->toBe($project->id)
        ->and($resolve(AccessAttributeEnum::TicketLabel, $ticket))->toBe([$label->id])
        ->and($resolve(AccessAttributeEnum::TicketIsArchived, $ticket))->toBeFalse()
        ->and($resolve(AccessAttributeEnum::TicketSubjectIsAssignee, $ticket))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::TicketSubjectIsProjectLead, $ticket))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::TicketSubjectIsProjectMember, $ticket))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::TicketIsArchived, $loose))->toBeTrue()
        ->and($resolve(AccessAttributeEnum::TicketProject, $loose))->toBeNull()
        ->and($resolve(AccessAttributeEnum::TicketLabel, $loose))->toBe([])
        ->and($resolve(AccessAttributeEnum::TicketSubjectIsAssignee, $loose))->toBeFalse()
        ->and($resolve(AccessAttributeEnum::TicketSubjectIsProjectLead, $loose))->toBeFalse()
        ->and($resolve(AccessAttributeEnum::TicketSubjectIsProjectMember, $loose))->toBeFalse();
});

it('reads customer and invoice statuses and group places', function (): void {
    ['user' => $user, 'teamId' => $teamId, 'subject' => $subject] = resolverSetup();
    $customer = Customer::fromEloquentModel(CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'status' => 'inactive']));
    $invoice = Invoice::fromEloquentModel(InvoiceEloquentModel::factory()->create(['team_id' => $teamId, 'status' => 'paid']));
    $ledModel = GroupEloquentModel::factory()->create(['team_id' => $teamId]);
    $ledModel->members()->attach($user->id, ['role' => 'lead']);
    $led = Group::fromEloquentModel($ledModel);
    $other = Group::fromEloquentModel(GroupEloquentModel::factory()->create(['team_id' => $teamId]));

    expect(AccessAttributeResolver::resolve(AccessAttributeEnum::CustomerStatus, $subject(), $customer))->toBe('inactive')
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::InvoiceStatus, $subject(), $invoice))->toBe('paid')
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::GroupSubjectIsLead, $subject(), $led))->toBeTrue()
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::GroupSubjectIsMember, $subject(), $led))->toBeTrue()
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::GroupSubjectIsLead, $subject(), $other))->toBeFalse()
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::GroupSubjectIsMember, $subject(), $other))->toBeFalse();
});

it('reads nothing from a record of another kind, or no record', function (): void {
    ['teamId' => $teamId, 'subject' => $subject] = resolverSetup();
    $customer = Customer::fromEloquentModel(CustomerEloquentModel::factory()->create(['team_id' => $teamId]));

    expect(AccessAttributeResolver::resolve(AccessAttributeEnum::ProjectStatus, $subject(), $customer))->toBeNull()
        ->and(AccessAttributeResolver::resolve(AccessAttributeEnum::TicketSubjectIsAssignee, $subject(), null))->toBeNull();
});
