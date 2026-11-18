<?php

declare(strict_types=1);

namespace App\Connector\FormRequests;

use App\Connector\Actions\SaveConnector;
use App\Connector\Connector;
use App\Connector\ConnectorTypeEnum;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Connector\PublicHost;
use App\Mailbox\Contracts\MailServers;
use App\Mailbox\MailServerSettings;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Connects a mailbox on any mail server, by where its IMAP and SMTP servers
 * are and how to sign in to them.
 */
final class StoreConnectorFormRequest extends FormRequest
{
    public Team $team;

    public function __construct()
    {
        parent::__construct();

        $this->team = Team::new();
    }

    /**
     * @throws CouldNotViewTeam
     */
    public function authorize(): Response
    {
        $this->team = FindTeam::byIdForMember(
            id: $this->user()?->current_team_id,
            userId: (string) $this->user()?->id,
        );

        if ($this->team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return Gate::authorize('manageConnectors', $this->team);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $host = ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-:\[\]]+$/'];
        $port = ['required', 'integer', 'between:1,65535'];
        $encryption = ['required', Rule::in(MailServerSettings::ENCRYPTIONS)];

        return [
            'from_address' => ['required', 'string', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'imap_host' => $host,
            'imap_port' => $port,
            'imap_encryption' => $encryption,
            'imap_username' => ['required', 'string', 'max:255'],
            'imap_password' => ['required', 'string', 'max:1000'],
            'smtp_host' => $host,
            'smtp_port' => $port,
            'smtp_encryption' => $encryption,
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'imap_host.regex' => __('Enter the server\'s name, such as imap.yourcompany.com, without "https://" or a path.'),
            'smtp_host.regex' => __('Enter the server\'s name, such as smtp.yourcompany.com, without "https://" or a path.'),
        ];
    }

    /**
     * Signs in to both servers before saving anything, so a connector that
     * cannot read or cannot send is never listed. Connecting an address the
     * team already has replaces its servers and passwords.
     *
     * @throws ValidationException
     */
    public function process(): RedirectResponse
    {
        $validated = $this->validated();

        $settings = MailServerSettings::fromArray([
            ...$validated,
            'smtp_username' => filled($validated['smtp_username'] ?? null) ? $validated['smtp_username'] : $validated['imap_username'],
            'smtp_password' => filled($validated['smtp_password'] ?? null) ? $validated['smtp_password'] : $validated['imap_password'],
        ]);

        foreach (['imap_host' => $settings->imapHost, 'smtp_host' => $settings->smtpHost] as $field => $host) {
            if (! PublicHost::allows($host)) {
                throw ValidationException::withMessages([
                    $field => __("MomCRM can't connect to :host. Check the name, and that the server can be reached from the internet.", ['host' => $host]),
                ]);
            }
        }

        $servers = app(MailServers::class);

        try {
            $servers->inbox($settings);
        } catch (CouldNotReachConnector $exception) {
            throw self::refused($exception, 'imap', 'IMAP', $settings->imapHost, $settings->imapPort);
        }

        try {
            $servers->checkSmtp($settings);
        } catch (CouldNotReachConnector $exception) {
            throw self::refused($exception, 'smtp', 'SMTP', $settings->smtpHost, $settings->smtpPort);
        }

        SaveConnector::fromConnector(
            Connector::new()
                ->setTeamId($this->team->getId()->toString())
                ->setType(ConnectorTypeEnum::Imap)
                ->setAccount($settings->fromAddress)
                ->setName($settings->fromName ?? $settings->fromAddress)
                ->setSettings($settings->toSettings())
                ->setConnectedById((string) $this->user()?->id),
            $settings->toCredentials(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':account connected.', ['account' => $settings->fromAddress])]);

        return to_route('connectors.index');
    }

    /**
     * Why a server would not let us in, against the field to look at.
     */
    private static function refused(CouldNotReachConnector $exception, string $prefix, string $protocol, string $host, int $port): ValidationException
    {
        return ValidationException::withMessages(match (true) {
            $exception->needsReconnect => ["{$prefix}_password" => __('The :protocol server did not accept that username and password.', ['protocol' => $protocol])],
            $exception->isTemporary => ["{$prefix}_host" => __("We couldn't reach the :protocol server at :host on port :port. Check the server, port and encryption.", ['protocol' => $protocol, 'host' => $host, 'port' => $port])],
            default => ["{$prefix}_host" => $exception->getMessage()],
        });
    }
}
