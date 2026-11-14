<?php

declare(strict_types=1);

namespace App\Activity\Actions;

use App\Activity\Activity;
use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Shared\MorphMapKeyEnum;
use App\Shared\RequestContext;
use Illuminate\Database\Eloquent\Model;

final readonly class RecordActivity
{
    public function __construct(
        private Activity $activity,
    ) {}

    public function __invoke(): Activity
    {
        $model = ActivityEloquentModel::query()->create($this->activity->forDatabaseCreate());

        return Activity::fromEloquentModel($model);
    }

    public static function fromActivity(Activity $activity): Activity
    {
        return new self($activity)->handle();
    }

    /**
     * Record an activity performed by a user, optionally within a team and about a subject.
     *
     * @param  array<string, mixed>  $details
     */
    public static function for(
        ActivityTypeEnum $type,
        ?string $userId,
        ?string $teamId = null,
        ?Model $subject = null,
        array $details = [],
    ): Activity {
        return self::fromActivity(
            Activity::new()
                ->setType($type)
                ->setUserId($userId)
                ->setTeamId($teamId)
                ->setSubjectType($subject === null ? null : MorphMapKeyEnum::fromModel($subject))
                ->setSubjectId($subject === null ? null : (string) $subject->getKey())
                ->setDetails($details === [] ? null : $details)
                ->setRequestId(RequestContext::requestId())
                ->setIpAddress(RequestContext::ipAddress())
                ->setUserAgent(RequestContext::userAgent()),
        );
    }

    public function handle(): Activity
    {
        return $this->__invoke();
    }

    public function execute(): Activity
    {
        return $this->__invoke();
    }
}
