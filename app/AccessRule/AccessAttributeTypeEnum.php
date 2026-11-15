<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\Shared\Traits\Enum;

enum AccessAttributeTypeEnum: string
{
    use Enum;

    /** One or more values picked from a list (a role, a status, a group). */
    case Choice = 'choice';

    /** Yes or no (is the assignee, is archived). */
    case Boolean = 'boolean';

    /**
     * @return array<int, AccessOperatorEnum>
     */
    public function operators(): array
    {
        return match ($this) {
            self::Choice => [AccessOperatorEnum::Equals, AccessOperatorEnum::NotEquals, AccessOperatorEnum::In, AccessOperatorEnum::NotIn],
            self::Boolean => [AccessOperatorEnum::IsTrue, AccessOperatorEnum::IsFalse],
        };
    }
}
