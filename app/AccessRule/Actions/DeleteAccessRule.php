<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessRule;
use App\AccessRule\AccessRuleEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class DeleteAccessRule
{
    public function __construct(
        private AccessRule $accessRule,
    ) {}

    public function __invoke(): void
    {
        AccessRuleEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->accessRule->getId()->toString())
            ?->delete();

        FindAccessRules::forgetForTeam($this->accessRule->getTeamId()->toString());
    }

    public static function fromAccessRule(AccessRule $accessRule): void
    {
        new self($accessRule)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
