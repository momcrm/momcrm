<?php

declare(strict_types=1);

namespace App\Shift\Actions;

use App\Attendance\AttendanceTimezone;
use App\Shift\Properties\Id;
use App\Shift\Shift;
use App\Shift\ShiftEloquentModel;
use App\ShiftBreak\Properties\Id as ShiftBreakId;
use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\ShiftBreak;
use App\ShiftBreak\ShiftBreakEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Adds or corrects a shift on a member's behalf, replacing its breaks with
 * the ones given. Each change is in the audit log.
 */
final readonly class SaveShift
{
    /**
     * @param  list<array{started_at: CarbonImmutable, ended_at: ?CarbonImmutable}>  $breaks
     */
    public function __construct(
        private Shift $shift,
        private array $breaks,
    ) {}

    public function __invoke(): Shift
    {
        $clockedInAt = $this->shift->getClockedInAt()->toCarbon()->utc();
        $this->shift
            ->setClockedInAt($clockedInAt)
            ->setClockedOutAt($this->shift->getClockedOutAt()->toNullableCarbon()?->utc())
            ->setWorkDate($clockedInAt->setTimezone(AttendanceTimezone::forUserId($this->shift->getUserId()->toString()))->toDateString());

        return DB::transaction(function (): Shift {
            if ($this->shift->getId()->isNull()) {
                $this->shift->setId(Id::generate());
                $model = ShiftEloquentModel::query()->create($this->shift->forDatabaseCreate());
            } else {
                $model = ShiftEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->shift->getId()->toString());
                $model->update($this->shift->forDatabaseUpdate());
            }

            $this->replaceBreaks($model);

            return Shift::fromEloquentModel($model->refresh());
        });
    }

    /**
     * @param  list<array{started_at: CarbonImmutable, ended_at: ?CarbonImmutable}>  $breaks
     */
    public static function fromShift(Shift $shift, array $breaks = []): Shift
    {
        return new self($shift, $breaks)->handle();
    }

    public function handle(): Shift
    {
        return $this->__invoke();
    }

    public function execute(): Shift
    {
        return $this->__invoke();
    }

    /**
     * Deletes the old breaks one by one, so each removal is audited, then
     * adds the new ones.
     */
    private function replaceBreaks(ShiftEloquentModel $model): void
    {
        ShiftBreakEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(ShiftId::getDatabaseTableColumnName(), $model->id)
            ->get()
            ->each(fn (ShiftBreakEloquentModel $break): ?bool => $break->delete());

        foreach ($this->breaks as $break) {
            ShiftBreakEloquentModel::query()->create(ShiftBreak::new()
                ->setId(ShiftBreakId::generate())
                ->setTeamId($model->team_id)
                ->setShiftId($model->id)
                ->setStartedAt($break['started_at']->utc())
                ->setEndedAt($break['ended_at']?->utc())
                ->forDatabaseCreate());
        }
    }
}
