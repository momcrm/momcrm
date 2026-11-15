<?php

declare(strict_types=1);

namespace App\AccessRule\Actions;

use App\AccessRule\AccessCondition;
use App\AccessRule\AccessOperatorEnum;
use App\AccessRule\AccessRule;
use App\Choice\Actions\FindChoice;
use App\Choice\Choice;
use App\Choice\ChoiceListEnum;

/**
 * Takes deleted statuses and priorities out of a team's access rule
 * conditions, without changing what any rule does:
 *
 * - "is not" and "is none of" always hold for a deleted choice, so its id is
 *   dropped, and the condition with it once nothing is left.
 * - "is any of" just loses the deleted id.
 * - "is", or "is any of" with nothing left, could never hold again, so the rule
 *   could never apply. Its value is cleared and the rule is disabled, for an
 *   admin to choose a new value.
 */
final readonly class PruneDeletedChoicesFromAccessRules
{
    public function __construct(
        private string $teamId,
    ) {}

    /**
     * @return int How many rules changed.
     */
    public function __invoke(): int
    {
        /** @var array<string, list<string>> $existingIds */
        $existingIds = [];

        foreach (ChoiceListEnum::cases() as $list) {
            $existingIds[$list->value] = array_values(FindChoice::listForTeam($this->teamId, $list, withArchived: true)
                ->map(fn (Choice $choice): string => $choice->getId()->toString())
                ->all());
        }

        $changed = 0;

        foreach (FindAccessRules::allForTeam($this->teamId) as $rule) {
            if ($this->prune($rule, $existingIds)) {
                $changed++;
            }
        }

        return $changed;
    }

    /**
     * @param  array<string, list<string>>  $existingIds
     */
    private function prune(AccessRule $rule, array $existingIds): bool
    {
        $isChanged = false;
        $canNeverApply = false;
        $conditions = [];

        foreach ($rule->getConditions()->toCollection()->all() as $stored) {
            $condition = AccessCondition::fromArray(is_array($stored) ? $stored : []);
            $list = $condition->attribute?->choiceList();

            if ($list === null || $condition->operator === null || $condition->isBroken()) {
                $conditions[] = $stored;

                continue;
            }

            $values = (array) $condition->value;
            $kept = array_values(array_intersect($values, $existingIds[$list->value]));

            if ($kept === $values) {
                $conditions[] = $stored;

                continue;
            }

            $isChanged = true;

            if (in_array($condition->operator, [AccessOperatorEnum::NotEquals, AccessOperatorEnum::NotIn], true)) {
                if ($kept !== []) {
                    $conditions[] = [...$condition->toArray(), 'value' => $kept];
                }

                continue;
            }

            if ($kept === []) {
                $canNeverApply = true;
            }

            $conditions[] = [...$condition->toArray(), 'value' => $condition->operator->takesList() ? $kept : null];
        }

        if (! $isChanged) {
            return false;
        }

        $rule->setConditions($conditions);

        if ($canNeverApply) {
            $rule->setIsEnabled(false);
        }

        UpdateAccessRule::fromAccessRule($rule);

        return true;
    }

    public static function forTeam(string $teamId): int
    {
        return new self($teamId)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }
}
