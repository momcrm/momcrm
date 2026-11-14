<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\SocialAccount\SocialProviderEnum;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RedirectToProviderController
{
    public function __invoke(string $provider): RedirectResponse
    {
        $found = SocialProviderEnum::tryFrom($provider);

        if ($found === null || ! $found->isConfigured()) {
            throw new NotFoundHttpException;
        }

        return Socialite::driver($found->value)->redirect();
    }
}
