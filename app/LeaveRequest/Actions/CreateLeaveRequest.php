<?php

declare(strict_types=1);

namespace App\LeaveRequest\Actions;

use App\LeaveRequest\LeaveRequest;
use App\LeaveRequest\LeaveRequestEloquentModel;
use App\LeaveRequest\Properties\Id;

final readonly class CreateLeaveRequest
{
    public function __construct(
        private LeaveRequest $leave,
    ) {}

    public function __invoke(): LeaveRequest
    {
        if ($this->leave->getId()->isNull()) {
            $this->leave->setId(Id::generate());
        }

        $model = LeaveRequestEloquentModel::query()->create($this->leave->forDatabaseCreate());

        return LeaveRequest::fromEloquentModel($model->refresh());
    }

    public static function fromLeaveRequest(LeaveRequest $leave): LeaveRequest
    {
        return new self($leave)->handle();
    }

    public function handle(): LeaveRequest
    {
        return $this->__invoke();
    }

    public function execute(): LeaveRequest
    {
        return $this->__invoke();
    }
}
