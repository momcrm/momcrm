<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessAudienceEnum;
use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Properties\Id;

final readonly class CreateAccessRule
{
    public function __construct(
        private AccessRule $accessRule,
    ) {}

    public function __invoke(): AccessRule
    {
        if ($this->accessRule->getId()->isNull()) {
            $this->accessRule->setId(Id::generate());
        }

        if ($this->accessRule->getAudience()->isNull()) {
            $this->accessRule->setAudience(AccessAudienceEnum::Staff->value);
        }

        if ($this->accessRule->getIsEnabled()->isNull()) {
            $this->accessRule->setIsEnabled(true);
        }

        $model = AccessRuleEloquentModel::query()->create($this->accessRule->forDatabaseCreate());

        FindAccessRules::forgetForTeam($model->team_id);

        return AccessRule::fromEloquentModel($model->refresh());
    }

    public static function fromAccessRule(AccessRule $accessRule): AccessRule
    {
        return new self($accessRule)->handle();
    }

    public function handle(): AccessRule
    {
        return $this->__invoke();
    }

    public function execute(): AccessRule
    {
        return $this->__invoke();
    }
}
