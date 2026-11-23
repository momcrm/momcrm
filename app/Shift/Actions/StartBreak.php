<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\ShiftBreak\Actions\FindShiftBreak;
use App\ShiftBreak\Properties\Id;
use App\ShiftBreak\ShiftBreak;
use App\ShiftBreak\ShiftBreakEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Starts a break in the member's running shift. Starting one while already on
 * a break keeps that break. Returns an empty break when they are not clocked in.
 */
final readonly class StartBreak
{
    public function __construct(
        private string $teamId,
        private string $userId,
        private CarbonImmutable $at,
    ) {}

    public function __invoke(): ShiftBreak
    {
        $shift = FindShift::openForMember($this->teamId, $this->userId);

        if ($shift->isNull()) {
            return ShiftBreak::new();
        }

        $shiftId = $shift->getId()->toString();
        $open = FindShiftBreak::forShifts([$shiftId])->open();

        if ($open->isNotNull()) {
            return $open;
        }

        $break = ShiftBreak::new()
            ->setId(Id::generate())
            ->setTeamId($this->teamId)
            ->setShiftId($shiftId)
            ->setStartedAt($this->at->utc()->startOfMinute()->max($shift->getClockedInAt()->toCarbon()));

        try {
            $model = ShiftBreakEloquentModel::query()->create($break->forDatabaseCreate());
        } catch (UniqueConstraintViolationException) {
            return FindShiftBreak::forShifts([$shiftId])->open();
        }

        return ShiftBreak::fromEloquentModel($model->refresh());
    }

    public static function forMember(string $teamId, string $userId, ?CarbonImmutable $at = null): ShiftBreak
    {
        return new self($teamId, $userId, $at ?? CarbonImmutable::now())->handle();
    }

    public function handle(): ShiftBreak
    {
        return $this->__invoke();
    }

    public function execute(): ShiftBreak
    {
        return $this->__invoke();
    }
}
