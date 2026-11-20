<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessActionEnum;
use App\AccessRule\AccessAttributeEnum;
use App\AccessRule\AccessAudienceEnum;
use App\AccessRule\AccessEffectEnum;
use App\AccessRule\AccessOperatorEnum;
use App\AccessRule\AccessResourceEnum;
use App\AccessRule\AccessRule;
use App\TeamUser\TeamRoleEnum;

/**
 * The rules every team starts with. Access is refused unless a rule allows
 * it, so these grant staff: owners and admins everything; everyone creating and
 * editing; everyone deleting comments and files (comments stay limited
 * to their authors); and everyone revealing secrets. Deleting, archiving and restoring other records and
 * managing project and group access are left to owners and admins, until an
 * admin adds a rule allowing more. Customer contacts get their own rules.
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

        $this->seedContactRules();
    }

    /**
     * What customers' contacts can do in the portal to begin with: see and
     * raise their company's tickets, see its services, assets and articles,
     * and, for its primary contact and invoice recipients, its billing.
     */
    private function seedContactRules(): void
    {
        $rules = [
            ['Contacts can see, raise and answer their tickets', [AccessResourceEnum::Ticket, AccessResourceEnum::Comment, AccessResourceEnum::Attachment], [], []],
            ['Contacts can see services, assets and articles', [AccessResourceEnum::Service, AccessResourceEnum::Asset, AccessResourceEnum::Article], [AccessActionEnum::View], []],
            ['Primary contacts can see billing', [AccessResourceEnum::Invoice, AccessResourceEnum::Payment, AccessResourceEnum::Statement], [AccessActionEnum::View], [AccessAttributeEnum::ContactIsPrimary]],
            ['Invoice recipients can see billing', [AccessResourceEnum::Invoice, AccessResourceEnum::Payment, AccessResourceEnum::Statement], [AccessActionEnum::View], [AccessAttributeEnum::ContactReceivesInvoices]],
        ];

        foreach ($rules as [$name, $resources, $actions, $mustBeTrue]) {
            CreateAccessRule::fromAccessRule(
                AccessRule::new()
                    ->setTeamId($this->teamId)
                    ->setAudience(AccessAudienceEnum::Contact->value)
                    ->setName($name)
                    ->setResources(self::values($resources))
                    ->setActions(self::values($actions))
                    ->setEffect(AccessEffectEnum::Allow->value)
                    ->setConditions(array_map(fn (AccessAttributeEnum $attribute): array => [
                        'attribute' => $attribute->value,
                        'operator' => AccessOperatorEnum::IsTrue->value,
                        'value' => null,
                    ], $mustBeTrue)),
            );
        }
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
