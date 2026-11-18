<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Mailbox\Actions\ConnectMailbox;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxSession;
use App\Mailbox\MicrosoftGraph;
use App\SocialAccount\SocialProviderEnum;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as MicrosoftUser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Where Microsoft sends an owner or admin back after they sign in to a
 * mailbox. The mailbox is saved only once we have opened its inbox, so a
 * mailbox that cannot be read is never shown as connected.
 */
final class HandleMailboxCallbackController
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

        Gate::authorize('manageEmail', $team);

        if (! SocialProviderEnum::Microsoft->isConfigured()) {
            throw new NotFoundHttpException;
        }

        $sharedAddress = $request->session()->pull(MailboxSession::PENDING_SHARED_ADDRESS);
        $sharedAddress = is_string($sharedAddress) && $sharedAddress !== '' ? $sharedAddress : null;

        try {
            /** @var AbstractProvider $microsoft */
            $microsoft = Socialite::driver(SocialProviderEnum::Microsoft->value);
            $microsoftUser = $microsoft->redirectUrl(route('mailboxes.callback'))->user();
        } catch (Throwable) {
            return self::failed(__("We couldn't sign in to Microsoft. Try connecting the mailbox again."));
        }

        if (! $microsoftUser instanceof MicrosoftUser || blank($microsoftUser->token) || blank($microsoftUser->refreshToken)) {
            return self::failed(__("Microsoft didn't give MomCRM lasting access to the mailbox. Try connecting it again."));
        }

        $address = $sharedAddress ?? self::ownAddress($microsoftUser);

        if ($address === '') {
            return self::failed(__('That Microsoft account has no mailbox.'));
        }

        try {
            MicrosoftGraph::withToken($microsoftUser->token, $sharedAddress)->inbox();
        } catch (CouldNotReachMailbox) {
            return self::failed($sharedAddress === null
                ? __("We couldn't open the inbox of :address. Check that the account has a Microsoft 365 mailbox.", ['address' => $address])
                : __("We couldn't open the inbox of :address. Check the address, and that the account you signed in with has access to it.", ['address' => $address]));
        }

        $teamId = $team->getId()->toString();
        $existing = FindMailbox::byAddress($address);

        if ($existing->isNotNull() && $existing->getTeamId()->toString() !== $teamId) {
            return self::failed(__(':address is already connected to another workspace.', ['address' => $address]));
        }

        $mailbox = ConnectMailbox::fromMailbox(
            Mailbox::new()
                ->setTeamId($teamId)
                ->setAddress($address)
                ->setDisplayName($sharedAddress === null ? $microsoftUser->getName() : null)
                ->setIsShared($sharedAddress !== null)
                ->setConnectedById((string) $request->user()?->id),
            $microsoftUser->refreshToken,
        );

        GetMailboxAccessToken::remember($mailbox, $microsoftUser->token, (int) $microsoftUser->expiresIn);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':address connected.', ['address' => $address])]);

        return to_route('mailboxes.index');
    }

    /**
     * The signed-in person's own address: their mail address when Microsoft
     * gives one, otherwise the name they sign in with.
     */
    private static function ownAddress(MicrosoftUser $microsoftUser): string
    {
        $mail = $microsoftUser->getRaw()['mail'] ?? null;

        return mb_strtolower(mb_trim(is_string($mail) && $mail !== '' ? $mail : (string) $microsoftUser->getEmail()));
    }

    private static function failed(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return to_route('mailboxes.index');
    }
}
