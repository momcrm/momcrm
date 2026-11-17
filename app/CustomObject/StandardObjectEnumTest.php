<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomObject\StandardObjectEnum;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Model;

/**
 * One record of each built-in kind, in the owner's team, with what it should
 * be titled and where it should link.
 *
 * @return array{0: Model, 1: string, 2: string}
 */
function standardRecord(StandardObjectEnum $object, string $teamId): array
{
    $team = ['team_id' => $teamId];
    $customer = fn () => CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);

    return match ($object) {
        StandardObjectEnum::Ticket => [$record = TicketEloquentModel::factory()->create([...$team, 'number' => 12, 'title' => 'Printer offline']), '#12 Printer offline', route('tickets.show', $record->id)],
        StandardObjectEnum::Customer => [$record = $customer(), 'Acme', route('customers.show', $record->id)],
        StandardObjectEnum::Contact => [$record = ContactEloquentModel::factory()->create([...$team, 'customer_id' => $customer()->id, 'first_name' => 'Sam', 'last_name' => 'Lee']), 'Sam Lee', route('contacts.edit', $record->id)],
        StandardObjectEnum::Project => [$record = ProjectEloquentModel::factory()->create([...$team, 'name' => 'Refit']), 'Refit', route('projects.show', $record->id)],
    };
}

it("finds, titles and links the team's records of every built-in kind, and names the kind", function (StandardObjectEnum $object): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    [$record, $title, $url] = standardRecord($object, $owner->current_team_id);
    [$foreign] = standardRecord($object, UserEloquentModel::factory()->withPersonalTeam()->create()->current_team_id);

    $found = $object->visibleQuery($owner->current_team_id, $owner->id)->whereKey($record->id)->sole();

    expect($object->title($found))->toBe($title)
        ->and($object->url($found))->toBe($url)
        ->and($object->visibleQuery($owner->current_team_id, $owner->id)->whereKey($foreign->id)->count())->toBe(0)
        ->and($object->label())->not->toBe('')
        ->and($object->labelSingular())->not->toBe('');
})->with(StandardObjectEnum::cases());

it('hides tickets and projects in a members-only project from someone who is not in it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $owner->current_team_id;
    $outsider = UserEloquentModel::factory()->inTeam($teamId)->create();
    $insider = UserEloquentModel::factory()->inTeam($teamId)->create();
    $secret = ProjectEloquentModel::factory()->create(['team_id' => $teamId, 'visibility' => 'restricted']);
    $secret->members()->attach($insider->id);
    TicketEloquentModel::factory()->create(['team_id' => $teamId, 'project_id' => $secret->id]);

    expect(StandardObjectEnum::Project->visibleQuery($teamId, $outsider->id)->count())->toBe(0)
        ->and(StandardObjectEnum::Ticket->visibleQuery($teamId, $outsider->id)->count())->toBe(0)
        ->and(StandardObjectEnum::Project->visibleQuery($teamId, $insider->id)->count())->toBe(1)
        ->and(StandardObjectEnum::Ticket->visibleQuery($teamId, $insider->id)->count())->toBe(1);
});
