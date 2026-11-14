<?php

declare(strict_types=1);

namespace App\Milestone\Actions;

use App\Milestone\Milestone;
use App\Milestone\MilestoneEloquentModel;
use App\Milestone\Properties\Id;

final readonly class CreateMilestone
{
    public function __construct(
        private Milestone $milestone,
    ) {}

    public function __invoke(): Milestone
    {
        if ($this->milestone->getId()->isNull()) {
            $this->milestone->setId(Id::generate());
        }

        $model = MilestoneEloquentModel::query()->create($this->milestone->forDatabaseCreate());

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
