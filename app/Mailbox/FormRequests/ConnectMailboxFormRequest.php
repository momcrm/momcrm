<?php

declare(strict_types=1);

namespace App\Mailbox\FormRequests;

use App\Mailbox\MailboxSession;
use App\Mailbox\MicrosoftGraph;
use App\SocialAccount\SocialProviderEnum;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConnectMailboxFormRequest extends FormRequest
{
    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        $team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Gate::authorize('manageEmail', $team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'address' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * Remembers which shared mailbox is wanted, if any, and hands over to
     * Microsoft. Only the permissions that kind of mailbox needs are asked for.
     */
    public function process(): RedirectResponse
    {
        if (! SocialProviderEnum::Microsoft->isConfigured()) {
            throw new NotFoundHttpException;
        }

        $address = mb_strtolower(mb_trim($this->string('address')->toString()));

        if ($address === '') {
            $this->session()->forget(MailboxSession::PENDING_SHARED_ADDRESS);
        } else {
            $this->session()->put(MailboxSession::PENDING_SHARED_ADDRESS, $address);
        }

        /** @var AbstractProvider $microsoft */
        $microsoft = Socialite::driver(SocialProviderEnum::Microsoft->value);

        return $microsoft
            ->scopes($address === '' ? MicrosoftGraph::SCOPES : MicrosoftGraph::SHARED_SCOPES)
            ->redirectUrl(route('mailboxes.callback'))
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }
}
