<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\TeamUser\TeamRoleEnum;
use Inertia\Inertia;
use Inertia\Response;

final class ShowInviteController
{
    public function __invoke(): Response
    {
        return Inertia::render('onboarding/invite', [
            'roles' => [
                ['value' => TeamRoleEnum::Member->value, 'label' => 'Member'],
                ['value' => TeamRoleEnum::Admin->value, 'label' => 'Admin'],
            ],
        ]);
    }
}
