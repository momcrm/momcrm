<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Customer\Actions\ImportCustomersFromCsv;
use App\User\OnboardingUseCaseEnum;
use App\User\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowStartController
{
    public function __invoke(Request $request): Response
    {
        $useCase = User::fromEloquentModel($request->user())->getOnboardingUseCase()->toNullableEnum();

        return Inertia::render('onboarding/start', [
            'recommended' => match ($useCase) {
                OnboardingUseCaseEnum::B2bSales => 'import',
                OnboardingUseCaseEnum::Other => 'blank',
                default => 'sample',
            },
            'maxRows' => ImportCustomersFromCsv::MAX_ROWS,
        ]);
    }
}
