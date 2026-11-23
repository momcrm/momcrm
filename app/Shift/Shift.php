<?php

declare(strict_types=1);

namespace App\Shift;

use App\Shift\Factories\ShiftFactory;
use App\Shift\Properties\ClockedInAt;
use App\Shift\Properties\ClockedOutAt;
use App\Shift\Properties\ClockInIp;
use App\Shift\Properties\CreatedAt;
use App\Shift\Properties\Id;
use App\Shift\Properties\Note;
use App\Shift\Properties\RemindedAt;
use App\Shift\Properties\TeamId;
use App\Shift\Properties\UpdatedAt;
use App\Shift\Properties\UserId;
use App\Shift\Properties\WorkDate;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A stretch of work from clocking in to clocking out. An open shift has no
 * clock-out time yet. The work date is the day it started in the member's
 * timezone, so a night shift counts toward the day it began.
 *
 * @extends AbstractData<ShiftEloquentModel>
 */
final class Shift extends AbstractData
{
    public function __construct(
        public ClockedInAt $clockedInAt,
        public ClockedOutAt $clockedOutAt,
        public ClockInIp $clockInIp,
        public CreatedAt $createdAt,
        public Id $id,
        public Note $note,
        public RemindedAt $remindedAt,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
        public WorkDate $workDate,
    ) {}

    public static function getFactory(): ShiftFactory
    {
        return ShiftEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ShiftCollection
    {
        if ($items instanceof ShiftCollection) {
            return $items;
        }

        return ShiftCollection::make(parent::collect($items, $method)->all());
    }

    public function isOpen(): bool
    {
        return $this->getClockedOutAt()->isNull();
    }

    public function getClockedInAt(): ClockedInAt
    {
        return $this->clockedInAt;
    }

    public function setClockedInAt(mixed $clockedInAt): self
    {
        $this->clockedInAt = new ClockedInAt($clockedInAt instanceof AbstractValue ? $clockedInAt->toValue() : $clockedInAt);

        return $this;
    }

    public function getClockedOutAt(): ClockedOutAt
    {
        return $this->clockedOutAt;
    }

    public function setClockedOutAt(mixed $clockedOutAt): self
    {
        $this->clockedOutAt = new ClockedOutAt($clockedOutAt instanceof AbstractValue ? $clockedOutAt->toValue() : $clockedOutAt);

        return $this;
    }

    public function getClockInIp(): ClockInIp
    {
        return $this->clockInIp;
    }

    public function setClockInIp(mixed $clockInIp): self
    {
        $this->clockInIp = new ClockInIp($clockInIp instanceof AbstractValue ? $clockInIp->toValue() : $clockInIp);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getNote(): Note
    {
        return $this->note;
    }

    public function setNote(mixed $note): self
    {
        $this->note = new Note($note instanceof AbstractValue ? $note->toValue() : $note);

        return $this;
    }

    public function getRemindedAt(): RemindedAt
    {
        return $this->remindedAt;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function setUserId(mixed $userId): self
    {
        $this->userId = new UserId($userId instanceof AbstractValue ? $userId->toValue() : $userId);

        return $this;
    }

    public function getWorkDate(): WorkDate
    {
        return $this->workDate;
    }

    public function setWorkDate(mixed $workDate): self
    {
        $this->workDate = new WorkDate($workDate instanceof AbstractValue ? $workDate->toValue() : $workDate);

        return $this;
    }
}
