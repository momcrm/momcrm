<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\Attendance\AttendanceTimezone;
use App\Shift\Properties\Id;
use App\Shift\Shift;
use App\Shift\ShiftEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Starts a shift for a member. Clocking in twice, say from two tabs, keeps
 * the shift already running: the unique index on open shifts settles a race.
 */
final readonly class ClockIn
{
    public function __construct(
        private string $teamId,
        private string $userId,
        private ?string $ipAddress,
        private CarbonImmutable $at,
    ) {}

    public function __invoke(): Shift
    {
        $open = FindShift::openForMember($this->teamId, $this->userId);

        if ($open->isNotNull()) {
            return $open;
        }

        $clockedInAt = $this->at->utc()->startOfMinute();

        $shift = Shift::new()
            ->setId(Id::generate())
            ->setTeamId($this->teamId)
            ->setUserId($this->userId)
            ->setClockedInAt($clockedInAt)
            ->setWorkDate($clockedInAt->setTimezone(AttendanceTimezone::forUserId($this->userId))->toDateString())
            ->setClockInIp($this->ipAddress);

        try {
            $model = ShiftEloquentModel::query()->create($shift->forDatabaseCreate());
        } catch (UniqueConstraintViolationException) {
            return FindShift::openForMember($this->teamId, $this->userId);
        }

        return Shift::fromEloquentModel($model->refresh());
    }

    public static function forMember(string $teamId, string $userId, ?string $ipAddress, ?CarbonImmutable $at = null): Shift
    {
        return new self($teamId, $userId, $ipAddress, $at ?? CarbonImmutable::now())->handle();
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
