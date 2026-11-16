<?php

declare(strict_types=1);

use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldValueRules;
use App\CustomField\Properties\CustomFieldValues;
use App\Team\TeamEloquentModel;

it('keeps the values of archived fields and drops those of deleted ones', function (): void {
    $teamId = TeamEloquentModel::factory()->create()->id;
    $live = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);
    $archived = CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $teamId]);
    $held = new CustomFieldValues([$archived->id => 'kept', 'deleted-field' => 'gone']);

    $merged = CustomFieldValueRules::merge($teamId, CustomFieldTargetEnum::Ticket, $held, [$live->id => 'new', $archived->id => 'changed']);

    expect($merged)->toEqual([$live->id => 'new', $archived->id => 'kept']);
});
