<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessAttributeEnum;
use App\AccessRule\AccessEffectEnum;
use App\AccessRule\AccessOperatorEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRule;
use App\TeamUser\TeamRoleEnum;

/**
 * The rules every team starts with: everyone may work on everything, except
 * that members may not delete, archive or restore records or manage project
 * and group access. Comments and files are left to the people who add them.
 */
final readonly class SeedDefaultAccessRules
{
    public function __construct(
        private string $teamId,
    ) {}

    public function __invoke(): void
    {
        CreateAccessRule::fromAccessRule(
            AccessRule::new()
                ->setTeamId($this->teamId)
                ->setName('Everyone can work on everything')
                ->setResources([])
                ->setActions([])
                ->setEffect(AccessEffectEnum::Allow->value)
                ->setConditions([]),
        );

        CreateAccessRule::fromAccessRule(
            AccessRule::new()
                ->setTeamId($this->teamId)
                ->setName('Only owners and admins delete, archive and manage access')
                ->setResources(self::values(array_filter(
                    AccessResourceEnum::cases(),
                    fn (AccessResourceEnum $resource): bool => ! in_array($resource, [AccessResourceEnum::Attachment, AccessResourceEnum::Comment], true),
                )))
                ->setActions(self::values([
                    AccessActionEnum::Archive,
                    AccessActionEnum::ChangeVisibility,
                    AccessActionEnum::Delete,
                    AccessActionEnum::ManageMembers,
                    AccessActionEnum::Restore,
                ]))
                ->setEffect(AccessEffectEnum::Deny->value)
                ->setConditions([[
                    'attribute' => AccessAttributeEnum::SubjectTeamRole->value,
                    'operator' => AccessOperatorEnum::Equals->value,
                    'value' => TeamRoleEnum::Member->value,
                ]]),
        );
    }

    public static function forTeam(string $teamId): void
    {
        new self($teamId)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }

    /**
     * @param  array<int, AccessResourceEnum|AccessActionEnum>  $cases
     * @return array<int, string>
     */
    private static function values(array $cases): array
    {
        return array_values(array_map(fn (AccessResourceEnum|AccessActionEnum $case): string => $case->value, $cases));
    }
}
