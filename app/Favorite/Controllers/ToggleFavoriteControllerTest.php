<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Favorite\FavoriteEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('stars a record, and unstars it the second time', function (string $type, string $model): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $record = $model::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->post(route('favorites.toggle'), ['favoritable_type' => $type, 'favoritable_id' => $record->id])->assertRedirect();

    expect(FavoriteEloquentModel::query()->sole())
        ->user_id->toBe($user->id)
        ->team_id->toBe($user->current_team_id)
        ->favoritable_type->toBe($type)
        ->favoritable_id->toBe($record->id);

    actingAs($user)->post(route('favorites.toggle'), ['favoritable_type' => $type, 'favoritable_id' => $record->id]);

    expect(FavoriteEloquentModel::query()->exists())->toBeFalse();
})->with([
    'project' => ['project', ProjectEloquentModel::class],
    'ticket' => ['ticket', TicketEloquentModel::class],
    'customer' => ['customer', CustomerEloquentModel::class],
]);

it('keeps stars per person', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    FavoriteEloquentModel::factory()->forFavoritable($project)->create();

    actingAs($user)->post(route('favorites.toggle'), ['favoritable_type' => 'project', 'favoritable_id' => $project->id]);

    expect(FavoriteEloquentModel::query()->withoutGlobalScopes()->count())->toBe(2);
});

it("rejects another team's record and unknown kinds", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('favorites.toggle'), ['favoritable_type' => 'project', 'favoritable_id' => ProjectEloquentModel::factory()->create()->id])
        ->assertSessionHasErrors('favoritable_id');
    actingAs($user)
        ->post(route('favorites.toggle'), ['favoritable_type' => 'invoice', 'favoritable_id' => fake()->uuid()])
        ->assertSessionHasErrors('favoritable_type');
});
