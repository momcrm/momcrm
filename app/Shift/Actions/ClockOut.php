<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\Shift\Properties\ClockedOutAt;
use App\Shift\Shift;
use App\Shift\ShiftEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Ends the member's running shift, and its break if they are on one. Returns
 * an empty shift when they were not clocked in.
 */
final readonly class ClockOut
{
    public function __construct(
        private string $teamId,
        private string $userId,
        private CarbonImmutable $at,
    ) {}

    public function __invoke(): Shift
    {
        return DB::transaction(function (): Shift {
            $open = FindShift::openForMember($this->teamId, $this->userId);

            if ($open->isNull()) {
                return $open;
            }

            $model = ShiftEloquentModel::query()->withoutGlobalScopes()->lockForUpdate()->find($open->getId()->toString());

            if (! $model instanceof ShiftEloquentModel || $model->clocked_out_at !== null) {
                return Shift::new();
            }

            $clockedOutAt = $this->at->utc()->startOfMinute()->max($model->clocked_in_at);

            EndBreak::forShift($model->id, $clockedOutAt);

            $model->update([ClockedOutAt::getDatabaseTableColumnName() => $clockedOutAt->toDateTimeString()]);

            return Shift::fromEloquentModel($model->refresh());
        });
    }

    public static function forMember(string $teamId, string $userId, ?CarbonImmutable $at = null): Shift
    {
        return new self($teamId, $userId, $at ?? CarbonImmutable::now())->handle();
    }

    public function handle(): Shift
    {
        return $this->__invoke();
    }

    public function execute(): Shift
    {
        return $this->__invoke();
    }
}
