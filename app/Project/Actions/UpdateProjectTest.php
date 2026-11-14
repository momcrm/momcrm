<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Project\Actions\UpdateProject;
use App\Project\Notifications\ProjectAtRiskNotification;
use App\Project\Project;
use App\Project\ProjectEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

it('tells the other members when a project is marked at risk', function (): void {
    Notification::fake();
    [$actor, $member] = UserEloquentModel::factory()->count(2)->create();
    $model = ProjectEloquentModel::factory()->create(['status' => 'in_progress']);
    $model->members()->attach([$actor->id, $member->id]);

    UpdateProject::fromProject(Project::fromEloquentModel($model)->setStatus('at_risk'), updatedById: $actor->id);

    Notification::assertSentTo($member, ProjectAtRiskNotification::class);
    Notification::assertNotSentTo($actor, ProjectAtRiskNotification::class);
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::ProjectStatusChanged->value)->sole()->details)
        ->toMatchArray(['from' => 'In progress', 'to' => 'At risk']);
});
