<?php

declare(strict_types=1);

use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;
use App\Team\Actions\CreateTeam;
use App\Team\Team;
use App\User\UserEloquentModel;

it('gives every new team the default ticket statuses and priorities, in order', function (): void {
    $team = CreateTeam::fromTeam(Team::new()->setName('Acme')->setOwnerId(UserEloquentModel::factory()->create()->id));
    $names = fn (ChoiceListEnum $list): array => FindChoice::listForTeam($team->getId()->toString(), $list)
        ->map(fn (Choice $choice): string => $choice->getName()->toString())
        ->all();

    expect($names(ChoiceListEnum::TicketStatus))->toBe(['New', 'In progress', 'Waiting on customer', 'Waiting on supplier', 'Closed'])
        ->and($names(ChoiceListEnum::TicketPriority))->toBe(['Urgent', 'High', 'Normal', 'Low']);
});
