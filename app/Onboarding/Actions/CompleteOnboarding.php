<?php

declare(strict_types=1);

namespace App\Onboarding\Actions;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\User\Actions\UpdateUser;
use App\User\User;
use Carbon\CarbonImmutable;

final readonly class CompleteOnboarding
{
    public function __construct(
        private User $user,
    ) {}

    public function __invoke(): User
    {
        if ($this->user->getOnboardedAt()->isNotNull()) {
            return $this->user;
        }

        UpdateUser::fromUser($this->user->setOnboardedAt(CarbonImmutable::now()));

        RecordActivity::for(
            type: ActivityTypeEnum::OnboardingCompleted,
            userId: $this->user->getId()->toString(),
            teamId: $this->user->getCurrentTeamId()->toNullableString(),
        );

        return $this->user;
    }

    public static function fromUser(User $user): User
    {
        return new self($user)->handle();
    }

    public function handle(): User
    {
        return $this->__invoke();
    }

    public function execute(): User
    {
        return $this->__invoke();
    }
}
