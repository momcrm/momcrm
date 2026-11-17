<?php

declare(strict_types=1);

namespace App\Milestone\Actions;

use App\Milestone\Milestone;
use App\Milestone\MilestoneEloquentModel;

/**
 * Saves a milestone's name, due date and custom field values.
 */
final readonly class UpdateMilestone
{
    public function __construct(
        private Milestone $milestone,
    ) {}

    public function __invoke(): Milestone
    {
        $model = MilestoneEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->milestone->getId()->toString());
        $model->update($this->milestone->forDatabaseUpdate());

        return Milestone::fromEloquentModel($model->refresh());
    }

    public static function fromMilestone(Milestone $milestone): Milestone
    {
        return new self($milestone)->handle();
    }

    public function handle(): Milestone
    {
        return $this->__invoke();
    }

    public function execute(): Milestone
    {
        return $this->__invoke();
    }
}
