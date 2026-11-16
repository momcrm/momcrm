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
 * The rules every team starts with. Access is refused unless a rule allows
 * it, so these grant: owners and admins everything; everyone creating and
 * editing; everyone deleting comments and files (comments stay limited
 * to their authors); and everyone revealing secrets. Deleting, archiving and restoring other records and
 * managing project and group access are left to owners and admins, until an
 * admin adds a rule allowing more.
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
                ->setName('Owners and admins can do everything')
                ->setResources([])
                ->setActions([])
                ->setEffect(AccessEffectEnum::Allow->value)
                ->setConditions([[
                    'attribute' => AccessAttributeEnum::SubjectTeamRole->value,
                    'operator' => AccessOperatorEnum::In->value,
                    'value' => [TeamRoleEnum::Owner->value, TeamRoleEnum::Admin->value],
                ]]),
        );

        CreateAccessRule::fromAccessRule(
            AccessRule::new()
                ->setTeamId($this->teamId)
                ->setName('Everyone can create and edit')
                ->setResources([])
                ->setActions(self::values([
                    AccessActionEnum::ChangeStatus,
                    AccessActionEnum::Close,
                    AccessActionEnum::Create,
                    AccessActionEnum::Import,
                    AccessActionEnum::Move,
                    AccessActionEnum::Reopen,
                    AccessActionEnum::Update,
                ]))
                ->setEffect(AccessEffectEnum::Allow->value)
                ->setConditions([]),
        );

        CreateAccessRule::fromAccessRule(
            AccessRule::new()
                ->setTeamId($this->teamId)
                ->setName('Everyone can delete comments and files')
                ->setResources(self::values([AccessResourceEnum::Attachment, AccessResourceEnum::Comment]))
                ->setActions(self::values([AccessActionEnum::Delete]))
                ->setEffect(AccessEffectEnum::Allow->value)
                ->setConditions([]),
        );

        CreateAccessRule::fromAccessRule(
            AccessRule::new()
                ->setTeamId($this->teamId)
                ->setName('Everyone can reveal secrets')
                ->setResources(self::values([AccessResourceEnum::Secret]))
                ->setActions(self::values([AccessActionEnum::Reveal]))
                ->setEffect(AccessEffectEnum::Allow->value)
                ->setConditions([]),
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
