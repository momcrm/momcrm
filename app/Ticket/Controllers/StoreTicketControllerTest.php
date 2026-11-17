<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Attachment\AttachmentEloquentModel;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTypeEnum;
use App\Group\GroupEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('opens a ticket numbered within the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    TicketEloquentModel::factory()->create(['number' => 9]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1]);
    $high = FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, 'High')->getId()->toString();

    $response = actingAs($user)->post(route('tickets.store'), [
        'customer_id' => $customer->id,
        'contact_id' => $contact->id,
        'assignee_id' => $user->id,
        'title' => 'Printer offline',
        'description' => 'Reception printer shows an error.',
        'priority_id' => $high,
    ]);

    $ticket = TicketEloquentModel::query()->where('title', 'Printer offline')->sole();
    $response->assertRedirectToRoute('tickets.show', $ticket->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #2 opened for Acme IT.']);
    expect($ticket)
        ->number->toBe(2)
        ->status_id->toBe(ticketStatusId($user->current_team_id, 'New'))
        ->priority_id->toBe($high)
        ->assignee_id->toBe($user->id);
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketOpened->value)->sole())
        ->subject_id->toBe($ticket->id)
        ->team_id->toBe($user->current_team_id);
});

it('rejects a contact who works for a different customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $otherContact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'contact_id' => $otherContact->id, 'title' => 'X'])
        ->assertSessionHasErrors(['contact_id' => 'Choose a contact who works for this customer.']);
});

it('rejects an assignee who is not in the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'assignee_id' => UserEloquentModel::factory()->create()->id, 'title' => 'X'])
        ->assertSessionHasErrors(['assignee_id' => 'Choose someone in your team.']);
});

it("rejects another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.store'), ['customer_id' => CustomerEloquentModel::factory()->create()->id, 'title' => 'X'])
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer this ticket is for.']);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('requires a title', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id])
        ->assertSessionHasErrors(['title' => 'The title field is required.']);
});

it('opens a project ticket without a customer, with dates', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Docs']);

    actingAs($user)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'Write runbook', 'starts_on' => '2026-12-01', 'due_on' => '2026-12-05'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #1 opened for Docs.']);

    expect(TicketEloquentModel::query()->sole())
        ->project_id->toBe($project->id)
        ->customer_id->toBeNull()
        ->starts_on->toBe('2026-12-01')
        ->due_on->toBe('2026-12-05');
});

it("gives a project ticket the project's customer and group when none are chosen", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    [$projectsGroup, $chosen] = GroupEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id])->all();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'group_id' => $projectsGroup->id]);

    actingAs($user)->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'Inherits']);
    actingAs($user)->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'Chosen', 'group_id' => $chosen->id]);

    expect(TicketEloquentModel::query()->where('title', 'Inherits')->sole())->customer_id->toBe($customer->id)->group_id->toBe($projectsGroup->id)
        ->and(TicketEloquentModel::query()->where('title', 'Chosen')->sole()->group_id)->toBe($chosen->id);
});

it('needs a customer or a project', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.store'), ['title' => 'X'])
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer this ticket is for, or put it in a project.']);
});

it("rejects another team's project and an archived one", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    foreach ([ProjectEloquentModel::factory()->create(), ProjectEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id])] as $project) {
        actingAs($user)
            ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X'])
            ->assertSessionHasErrors(['project_id' => "Choose one of your team's projects."]);
    }
});

it('rejects a due date before the start date', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'title' => 'X', 'starts_on' => '2026-12-05', 'due_on' => '2026-12-01'])
        ->assertSessionHasErrors(['due_on' => "The due date can't be before the start date."]);
});

it('quick-adds a ticket into a board column and stays on the board', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $inProgress = ticketStatusId($user->current_team_id, 'In progress');
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status_id' => $inProgress, 'position' => 4]);

    actingAs($user)
        ->from(route('tickets.index', ['view' => 'board']))
        ->post(route('tickets.store'), ['title' => 'Quick one', 'project_id' => $project->id, 'status_id' => $inProgress, 'quick' => '1'])
        ->assertRedirectToRoute('tickets.index', ['view' => 'board']);

    expect(TicketEloquentModel::query()->where('title', 'Quick one')->sole())
        ->status_id->toBe($inProgress)
        ->position->toBe(5);
});

it('rejects a members-only project the person is not in', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $outsider = UserEloquentModel::factory()->create(['current_team_id' => $member->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $member->current_team_id, 'user_id' => $outsider->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'restricted']);
    $project->members()->attach($member->id);

    actingAs($outsider)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X'])
        ->assertSessionHasErrors(['project_id' => "Choose one of your team's projects."]);

    actingAs($member)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X'])
        ->assertSessionHasNoErrors();
});

it('opens a ticket from the dialog with labels, subtasks and files, and stays on the page', function (): void {
    Storage::fake('local');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $label = ChoiceEloquentModel::factory()->label()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->from(route('dashboard'))
        ->post(route('tickets.store'), [
            'project_id' => $project->id,
            'title' => 'Launch site',
            'quick' => '1',
            'label_ids' => [$label->id],
            'subtasks' => ['Point DNS', 'Check SSL'],
            'files' => [UploadedFile::fake()->create('brief.pdf', 50, 'application/pdf')],
        ])
        ->assertRedirectToRoute('dashboard');

    $ticket = TicketEloquentModel::query()->where('title', 'Launch site')->sole();
    expect($ticket->labels->pluck('id')->all())->toBe([$label->id])
        ->and($ticket->subtasks()->orderBy('number')->pluck('title')->all())->toBe(['Point DNS', 'Check SSL'])
        ->and($ticket->subtasks()->pluck('project_id')->unique()->all())->toBe([$project->id])
        ->and(AttachmentEloquentModel::query()->sole())
        ->attachable_id->toBe($ticket->id)
        ->name->toBe('brief.pdf');
});

it("rejects another team's label and opens nothing", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X', 'label_ids' => [ChoiceEloquentModel::factory()->label()->create()->id]])
        ->assertSessionHasErrors('label_ids.0');

    expect(TicketEloquentModel::query()->exists())->toBeFalse();
});

it('accepts the dialog sending no labels, subtasks or files', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X', 'label_ids' => [], 'subtasks' => [], 'files' => [], 'quick' => '1'])
        ->assertSessionHasNoErrors();

    expect(TicketEloquentModel::query()->count())->toBe(1);
});

it("gives a ticket the team's default priority when none is chosen", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X'])->assertSessionHasNoErrors();

    expect(TicketEloquentModel::query()->sole()->priority_id)
        ->toBe(FindChoice::defaultForTeam($user->current_team_id, ChoiceListEnum::TicketPriority)->getId()->toString());
});

it('rejects an archived priority', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $retired = ChoiceEloquentModel::factory()->ticketPriority()->create(['team_id' => $user->current_team_id, 'archived_at' => now()]);

    actingAs($user)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X', 'priority_id' => $retired->id])
        ->assertSessionHasErrors(['priority_id' => "Choose one of your team's priorities."]);
});

it('opens a ticket with its custom field values, leaving out the empty ones', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $teamId]);
    $serial = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $onsite = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Checkbox)->create(['team_id' => $teamId]);
    $notes = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);

    actingAs($user)
        ->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'title' => 'Printer offline',
            'custom_fields' => [$serial->id => 'SN-1', $onsite->id => false, $notes->id => ''],
        ])
        ->assertSessionHasNoErrors();

    expect(TicketEloquentModel::query()->where('title', 'Printer offline')->sole()->custom_fields)->toBe([$serial->id => 'SN-1']);
});

it('rejects a custom field value of the wrong type when opening a ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $seats = CustomFieldEloquentModel::factory()->ofType(CustomFieldTypeEnum::Number)->create(['team_id' => $user->current_team_id, 'name' => 'Seats']);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'title' => 'Printer offline', 'custom_fields' => [$seats->id => 'many']])
        ->assertSessionHasErrors(["custom_fields.{$seats->id}" => 'The Seats field must be a number.']);
});
