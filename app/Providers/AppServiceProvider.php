<?php

namespace App\Providers;

use App\Activity\Listeners\RecordAccountActivity;
use App\Asset\Asset;
use App\Asset\AssetPolicy;
use App\Attachment\Attachment;
use App\Attachment\AttachmentPolicy;
use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionPolicy;
use App\Comment\Comment;
use App\Comment\CommentPolicy;
use App\Connector\ConnectorCapabilityEnum;
use App\Connector\Types\Google\GoogleConnector;
use App\Connector\Types\Imap\ImapConnector;
use App\Connector\Types\Imap\MailServers;
use App\Connector\Types\Imap\WebklexMailServers;
use App\Contact\Contact;
use App\Contact\ContactPolicy;
use App\Contract\Contract;
use App\Contract\ContractPolicy;
use App\Customer\Customer;
use App\Customer\CustomerPolicy;
use App\Environment\EnvironmentFile;
use App\Environment\EnvironmentPolicy;
use App\Environment\EnvironmentVariables;
use App\Group\Group;
use App\Group\GroupPolicy;
use App\Invoice\Invoice;
use App\Invoice\InvoicePolicy;
use App\Location\Location;
use App\Location\LocationPolicy;
use App\Milestone\Milestone;
use App\Milestone\MilestonePolicy;
use App\Passkey\FormRequests\PasskeyVerificationFormRequest;
use App\Passkey\PasskeyEloquentModel;
use App\Payment\Payment;
use App\Payment\PaymentPolicy;
use App\Project\Project;
use App\Project\ProjectPolicy;
use App\Record\Record;
use App\Record\RecordPolicy;
use App\Secret\Secret;
use App\Secret\SecretPolicy;
use App\Service\Service;
use App\Service\ServicePolicy;
use App\Shared\MorphMapKeyEnum;
use App\Team\Team;
use App\Team\TeamPolicy;
use App\TeamInvitation\Listeners\AcceptPendingTeamInvitation;
use App\Ticket\Ticket;
use App\Ticket\TicketPolicy;
use App\User\Listeners\SendWelcomeNotification;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Laravel\Passkeys\Http\Requests\PasskeyVerificationRequest;
use Laravel\Passkeys\Passkeys;
use Mom\Connectors\Capability;
use Mom\Connectors\ConnectorRegistry;
use Mom\Microsoft\MicrosoftConnector;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PasskeyVerificationRequest::class, PasskeyVerificationFormRequest::class);
        $this->app->bind(MailServers::class, WebklexMailServers::class);
        $this->callAfterResolving(ConnectorRegistry::class, function (ConnectorRegistry $registry): void {
            $registry
                ->capability(...array_map(
                    fn (ConnectorCapabilityEnum $capability): Capability => new Capability($capability->value, $capability->label(), $capability->contract()),
                    ConnectorCapabilityEnum::cases(),
                ))
                ->register(new MicrosoftConnector, new GoogleConnector, new ImapConnector);
        });
        $this->app->bind(EnvironmentFile::class, fn (): EnvironmentFile => new EnvironmentFile(
            path: $this->app->environmentFilePath(),
            backupDirectory: storage_path('app/private/environment-backups'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();

        Passkeys::usePasskeyModel(PasskeyEloquentModel::class);

        $this->configureMorphMap();
        $this->configureListeners();
        $this->configureMail();
        $this->configureErrorPages();
    }

    /**
     * Shows not-found and forbidden errors, and in production server errors,
     * as the app's own error page instead of Laravel's. JSON requests keep
     * their JSON errors, and local debugging keeps the full error page.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            $status = $response->statusCode();
            $request = $response->request;

            if ($request->expectsJson() && $request->header('X-Inertia') === null) {
                return null;
            }

            $isShown = in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));

            return $isShown
                ? $response->render('error', ['status' => $status])->withSharedData()
                : null;
        });
    }

    /**
     * Copy for the emails Laravel and Fortify send on our behalf.
     */
    protected function configureMail(): void
    {
        VerifyEmail::toMailUsing(fn (mixed $notifiable, string $url): MailMessage => new MailMessage()
            ->subject(__('Confirm your email to finish setting up MomCRM'))
            ->greeting($notifiable instanceof UserEloquentModel ? __('Hi :name,', ['name' => $notifiable->name]) : __('Hi,'))
            ->line(__("Thanks for signing up. Confirm your email address so we know it's really you."))
            ->action(__('Confirm my email'), $url)
            ->line(__("Didn't create a MomCRM account? You can ignore this email — nothing happens unless you click the button.")));
    }

    /**
     * Stable aliases for polymorphic columns (audit and activity subjects), so
     * stored rows do not depend on class names.
     *
     * Enforced: every Eloquent model must have an alias, otherwise
     * getMorphClass() throws. mom/data calls it on every model it wraps, so a
     * new model needs a case in MorphMapKeyEnum (AppServiceProviderTest checks this).
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap(MorphMapKeyEnum::map());
    }

    /**
     * Listeners live in their domains, which Laravel's event discovery does not scan.
     */
    protected function configureListeners(): void
    {
        Event::listen(RecordAccountActivity::EVENTS, RecordAccountActivity::class);
        Event::listen(Verified::class, SendWelcomeNotification::class);
        Event::listen(AcceptPendingTeamInvitation::EVENTS, AcceptPendingTeamInvitation::class);
        Event::listen(fn (SocialiteWasCalled $event) => $event->extendSocialite('microsoft', MicrosoftProvider::class));
    }

    /**
     * Register policies. They are keyed to domain DTOs, which Laravel cannot discover.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Secret::class, SecretPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(BankTransaction::class, BankTransactionPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(EnvironmentVariables::class, EnvironmentPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(Milestone::class, MilestonePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Record::class, RecordPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
