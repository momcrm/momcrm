<?php

namespace App\Providers;

use App\Activity\Listeners\RecordAccountActivity;
use App\Passkey\PasskeyEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Team\Team;
use App\Team\TeamPolicy;
use App\TeamInvitation\Listeners\AcceptPendingTeamInvitation;
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
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
    }

    /**
     * Copy for the emails Laravel and Fortify send on our behalf.
     */
    protected function configureMail(): void
    {
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url): MailMessage => new MailMessage()
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
    }

    /**
     * Register policies. They are keyed to domain DTOs, which Laravel cannot discover.
     */
    protected function configurePolicies(): void
    {
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
