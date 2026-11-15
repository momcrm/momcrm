<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateAccessRule
{
    public function __construct(
        private AccessRule $accessRule,
    ) {}

    public function __invoke(): AccessRule
    {
        $model = AccessRuleEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->accessRule->getId()->toString());

        if ($model === null) {
            return $this->accessRule;
        }

        $model->update($this->accessRule->forDatabaseUpdate());

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
