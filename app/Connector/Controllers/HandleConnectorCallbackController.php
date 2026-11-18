<?php

declare(strict_types=1);

namespace App\Connector\Controllers;

use App\Connector\Actions\GetConnectorAccessToken;
use App\Connector\Actions\SaveConnector;
use App\Connector\Connector;
use App\Connector\ConnectorEloquentModel;
use App\Connector\ConnectorSession;
use App\Connector\Sdk\ConnectorRegistry;
use App\Connector\Sdk\ConnectsBySignIn;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SignedInUser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Where an outside service sends an owner or admin back after they sign in to
 * the account to connect. What the service gave us is saved as a connector,
 * or replaces the credentials of the one the team already has for the account.
 */
final class HandleConnectorCallbackController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $team = FindTeam::byIdForMember(id: $request->user()?->current_team_id, userId: (string) $request->user()?->id);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        Gate::authorize('manageConnectors', $team);

        $type = ConnectorRegistry::instance()->find((string) $request->route('type'));

        if (! $type instanceof ConnectsBySignIn || ! $type->isAvailable()) {
            throw new NotFoundHttpException;
        }

        $options = Types::stringList($request->session()->pull(ConnectorSession::PENDING_OPTIONS) ?? []);
        $company = $type->company();

        try {
            /** @var AbstractProvider $provider */
            $provider = Socialite::driver($type->signInDriver());
            $signedIn = $provider->redirectUrl(route('connectors.callback', ['type' => $type->key()]))->user();
        } catch (Throwable) {
            return self::failed(__("We couldn't sign in to :company. Try connecting again.", ['company' => $company]));
        }

        if (! $signedIn instanceof SignedInUser || blank($signedIn->token) || blank($signedIn->refreshToken)) {
            return self::failed(__(":company didn't give MomCRM lasting access to the account. Try connecting again.", ['company' => $company]));
        }

        $account = self::account($signedIn);

        if ($account === '') {
            return self::failed(__('That :company account has no email address.', ['company' => $company]));
        }

        $connector = SaveConnector::fromConnector(
            Connector::new()
                ->setTeamId($team->getId()->toString())
                ->setType($type->key())
                ->setAccount($account)
                ->setName(filled($signedIn->getName()) ? $signedIn->getName() : $account)
                ->setSettings(array_fill_keys($options, true))
                ->setConnectedById((string) $request->user()?->id),
            [ConnectorEloquentModel::REFRESH_TOKEN => $signedIn->refreshToken],
        );

        GetConnectorAccessToken::remember($connector, $signedIn->token, (int) $signedIn->expiresIn);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':account connected.', ['account' => $account])]);

        return to_route('connectors.index');
    }

    /**
     * The signed-in account's address: its mail address when the service
     * gives one, otherwise the name it signs in with.
     */
    private static function account(SignedInUser $signedIn): string
    {
        $mail = $signedIn->getRaw()['mail'] ?? null;

        return mb_strtolower(mb_trim(is_string($mail) && $mail !== '' ? $mail : (string) $signedIn->getEmail()));
    }

    private static function failed(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return to_route('connectors.index');
    }
}
