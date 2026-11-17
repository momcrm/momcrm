<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\User\UserEloquentModel;

it("names the team's linked records across several records' values, leaving out the missing and the foreign", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Olive Owner']);
    $teamId = $owner->current_team_id;
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Acme IT']);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $teamId, 'customer_id' => $customer->id, 'first_name' => 'Sam', 'last_name' => null]);
    $foreign = CustomerEloquentModel::factory()->create();
    $reseller = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create(['team_id' => $teamId]);
    $approver = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Member)->create(['team_id' => $teamId]);
    $site = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Contact)->create(['team_id' => $teamId]);
    $text = CustomFieldEloquentModel::factory()->create(['team_id' => $teamId]);

    $labels = CustomFieldRelationLabels::for(
        $teamId,
        CustomFieldTargetEnum::Ticket,
        [$reseller->id => $customer->id, $approver->id => $owner->id, $text->id => $customer->id],
        [$reseller->id => $foreign->id, $site->id => $contact->id, $approver->id => '019a0000-0000-7000-8000-000000000000'],
    );

    expect($labels)->toEqual([$customer->id => 'Acme IT', $owner->id => 'Olive Owner', $contact->id => 'Sam']);
});
