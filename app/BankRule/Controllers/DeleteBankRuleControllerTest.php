<?php

declare(strict_types=1);

use App\BankRule\BankRuleEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a rule', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rule = BankRuleEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->delete(route('bank-rules.destroy', $rule->id))->assertSessionHasNoErrors();

    assertModelMissing($rule);
});
