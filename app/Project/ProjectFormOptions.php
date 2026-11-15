<?php

declare(strict_types=1);

namespace App\Project;

use App\Customer\Actions\FindCustomer;
use App\Group\Actions\FindGroup;
use App\TeamUser\Actions\FindTeamMembers;

/**
 * Everything the project form's pickers need.
 */
final class ProjectFormOptions
{
    /**
     * @return array{projectOptions: array<string, mixed>}
     */
    public static function forTeam(string $teamId): array
    {
        return [
            'projectOptions' => [
                'members' => FindTeamMembers::optionsForTeam($teamId),
                'customers' => FindCustomer::optionsForTeam($teamId),
                'groups' => FindGroup::optionsForTeam($teamId),
                'statuses' => ProjectStatusEnum::options(),
                'visibilities' => ProjectVisibilityEnum::options(),
            ],
        ];
    }
}
