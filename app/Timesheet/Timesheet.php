<?php

declare(strict_types=1);

namespace App\Timesheet;

use App\Timesheet\Factories\TimesheetFactory;
use App\Timesheet\Properties\CreatedAt;
use App\Timesheet\Properties\EndsOn;
use App\Timesheet\Properties\Id;
use App\Timesheet\Properties\ReviewedAt;
use App\Timesheet\Properties\ReviewedBy;
use App\Timesheet\Properties\ReviewNote;
use App\Timesheet\Properties\StartsOn;
use App\Timesheet\Properties\Status;
use App\Timesheet\Properties\SubmittedAt;
use App\Timesheet\Properties\TeamId;
use App\Timesheet\Properties\Totals;
use App\Timesheet\Properties\UpdatedAt;
use App\Timesheet\Properties\UserId;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A member's hours for one pay period, submitted for approval. Once
 * approved it keeps its totals and locks the shifts in it.
 *
 * @extends AbstractData<TimesheetEloquentModel>
 */
final class Timesheet extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public EndsOn $endsOn,
        public Id $id,
        public ReviewNote $reviewNote,
        public ReviewedAt $reviewedAt,
        public ReviewedBy $reviewedBy,
        public StartsOn $startsOn,
        public Status $status,
        public SubmittedAt $submittedAt,
        public TeamId $teamId,
        public Totals $totals,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public static function getFactory(): TimesheetFactory
    {
        return TimesheetEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): TimesheetCollection
    {
        if ($items instanceof TimesheetCollection) {
            return $items;
        }

        return TimesheetCollection::make(parent::collect($items, $method)->all());
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

    public function getSubmittedAt(): SubmittedAt
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(mixed $submittedAt): self
    {
        $this->submittedAt = new SubmittedAt($submittedAt instanceof AbstractValue ? $submittedAt->toValue() : $submittedAt);

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

    public function getTotals(): Totals
    {
        return $this->totals;
    }

    public function setTotals(mixed $totals): self
    {
        $this->totals = new Totals($totals instanceof AbstractValue ? $totals->toValue() : $totals);

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
