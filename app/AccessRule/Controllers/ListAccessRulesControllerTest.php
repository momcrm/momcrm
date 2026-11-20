<?php

declare(strict_types=1);

use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows owners and admins the team's rules and what they can be built from", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Admin)->create();

    foreach ([$owner, $admin] as $user) {
        actingAs($user)
            ->get(route('access-rules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/access-rules')
                ->has('rules', 8)
                ->where('audience', 'staff')
                ->where('rules.0.name', 'Owners and admins can do everything')
                ->where('rules.0.audience', 'staff')
                ->where('rules.0.conditions.0', ['attribute' => 'subject.team_role', 'operator' => 'in', 'value' => ['owner', 'admin']])
                ->where('options.staff.resources', fn ($resources): bool => collect($resources)->firstWhere('value', 'project')['actions'] === ['create', 'update', 'change_visibility', 'manage_members', 'archive', 'restore', 'delete'])
                ->where('options.staff.attributes', fn ($attributes): bool => collect($attributes)->firstWhere('value', 'subject.user')['options'] === collect([$owner, $admin])->sortBy('name')->map(fn ($user): array => ['value' => $user->id, 'label' => $user->name])->values()->all()));
    }
});

it('offers contact rules only what contacts can do and what is known about them', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->get(route('access-rules.index', ['audience' => 'contact']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('audience', 'contact')
            ->where('options.contact.resources', fn ($resources): bool => collect($resources)->pluck('value')->sort()->values()->all() === ['article', 'asset', 'attachment', 'comment', 'invoice', 'payment', 'service', 'statement', 'ticket']
                && collect($resources)->firstWhere('value', 'ticket')['actions'] === ['view', 'create', 'close', 'reopen'])
            ->where('options.contact.attributes', fn ($attributes): bool => collect($attributes)->pluck('value')->contains('contact.role')
                && collect($attributes)->pluck('value')->contains('ticket.subject_is_requester')
                && ! collect($attributes)->pluck('value')->contains('subject.team_role')
                && ! collect($attributes)->pluck('value')->contains('ticket.subject_is_assignee'))
            ->where('options.staff.attributes', fn ($attributes): bool => ! collect($attributes)->pluck('value')->contains('contact.role'))
            ->where('options.staff.resources', fn ($resources): bool => ! collect($resources)->pluck('value')->contains('statement')));
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('access-rules.index'))->assertForbidden();
});
