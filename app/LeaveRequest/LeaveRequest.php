<?php

declare(strict_types=1);

namespace App\LeaveRequest;

use App\LeaveRequest\Factories\LeaveRequestFactory;
use App\LeaveRequest\Properties\CreatedAt;
use App\LeaveRequest\Properties\EndsOn;
use App\LeaveRequest\Properties\Id;
use App\LeaveRequest\Properties\LeaveTypeId;
use App\LeaveRequest\Properties\Part;
use App\LeaveRequest\Properties\Reason;
use App\LeaveRequest\Properties\ReviewedAt;
use App\LeaveRequest\Properties\ReviewedBy;
use App\LeaveRequest\Properties\ReviewNote;
use App\LeaveRequest\Properties\StartsOn;
use App\LeaveRequest\Properties\Status;
use App\LeaveRequest\Properties\TeamId;
use App\LeaveRequest\Properties\UpdatedAt;
use App\LeaveRequest\Properties\UserId;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * Time off a member asked for: its type, the days, and whether a morning or
 * afternoon only, with where it is in being approved.
 *
 * @extends AbstractData<LeaveRequestEloquentModel>
 */
final class LeaveRequest extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public EndsOn $endsOn,
        public Id $id,
        public LeaveTypeId $leaveTypeId,
        public Part $part,
        public Reason $reason,
        public ReviewNote $reviewNote,
        public ReviewedAt $reviewedAt,
        public ReviewedBy $reviewedBy,
        public StartsOn $startsOn,
        public Status $status,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public static function getFactory(): LeaveRequestFactory
    {
        return LeaveRequestEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): LeaveRequestCollection
    {
        if ($items instanceof LeaveRequestCollection) {
            return $items;
        }

        return LeaveRequestCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The days in words, such as "Mon 1 Dec", "Mon 1 Dec (morning)" or
     * "Mon 1 Dec – Fri 5 Dec".
     */
    public function describeDays(): string
    {
        $startsOn = $this->getStartsOn()->toCarbon();
        $endsOn = $this->getEndsOn()->toCarbon();
        $part = $this->getPart()->toEnum();

        if ($startsOn->isSameDay($endsOn)) {
            return $startsOn->translatedFormat('D j M').($part->isHalfDay() ? ' ('.mb_strtolower($part->label()).')' : '');
        }

        return $startsOn->translatedFormat('D j M').' – '.$endsOn->translatedFormat('D j M');
    }

    /**
     * Whether the leave covers this date (YYYY-MM-DD).
     */
    public function covers(string $date): bool
    {
        return $this->getStartsOn()->toDateString() <= $date && $date <= $this->getEndsOn()->toDateString();
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getEndsOn(): EndsOn
    {
        return $this->endsOn;
    }

    public function setEndsOn(mixed $endsOn): self
    {
        $this->endsOn = new EndsOn($endsOn instanceof AbstractValue ? $endsOn->toValue() : $endsOn);

        return $this;
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

    public function getLeaveTypeId(): LeaveTypeId
    {
        return $this->leaveTypeId;
    }

    public function setLeaveTypeId(mixed $leaveTypeId): self
    {
        $this->leaveTypeId = new LeaveTypeId($leaveTypeId instanceof AbstractValue ? $leaveTypeId->toValue() : $leaveTypeId);

        return $this;
    }

    public function getPart(): Part
    {
        return $this->part;
    }

    public function setPart(mixed $part): self
    {
        $this->part = new Part($part instanceof AbstractValue ? $part->toValue() : $part);

        return $this;
    }

    public function getReason(): Reason
    {
        return $this->reason;
    }

    public function setReason(mixed $reason): self
    {
        $this->reason = new Reason($reason instanceof AbstractValue ? $reason->toValue() : $reason);

        return $this;
    }

    public function getReviewNote(): ReviewNote
    {
        return $this->reviewNote;
    }

    public function setReviewNote(mixed $reviewNote): self
    {
        $this->reviewNote = new ReviewNote($reviewNote instanceof AbstractValue ? $reviewNote->toValue() : $reviewNote);

        return $this;
    }

    public function getReviewedAt(): ReviewedAt
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(mixed $reviewedAt): self
    {
        $this->reviewedAt = new ReviewedAt($reviewedAt instanceof AbstractValue ? $reviewedAt->toValue() : $reviewedAt);

        return $this;
    }

    public function getReviewedBy(): ReviewedBy
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(mixed $reviewedBy): self
    {
        $this->reviewedBy = new ReviewedBy($reviewedBy instanceof AbstractValue ? $reviewedBy->toValue() : $reviewedBy);

        return $this;
    }

    public function getStartsOn(): StartsOn
    {
        return $this->startsOn;
    }

    public function setStartsOn(mixed $startsOn): self
    {
        $this->startsOn = new StartsOn($startsOn instanceof AbstractValue ? $startsOn->toValue() : $startsOn);

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(mixed $status): self
    {
        $this->status = new Status($status instanceof AbstractValue ? $status->toValue() : $status);

        return $this;
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
}
