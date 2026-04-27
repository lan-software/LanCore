<?php

namespace App\Providers;

use App\Domain\Achievements\Listeners\ProcessAchievements;
use App\Domain\Achievements\Models\Achievement;
use App\Domain\Achievements\Policies\AchievementPolicy;
use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Announcement\Events\AnnouncementsViewed;
use App\Domain\Announcement\Listeners\HandleAnnouncementPublishedWebhooks;
use App\Domain\Announcement\Listeners\SendAnnouncementNotification;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Announcement\Policies\AnnouncementPolicy;
use App\Domain\Competition\Events\MatchCompleted;
use App\Domain\Competition\Events\MatchReadyForOrchestration;
use App\Domain\Event\Events\EventPublished;
use App\Domain\Event\Listeners\HandleEventPublishedWebhooks;
use App\Domain\Event\Models\Event;
use App\Domain\Event\Policies\EventPolicy;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Games\Policies\GameModePolicy;
use App\Domain\Games\Policies\GamePolicy;
use App\Domain\Integration\Events\IntegrationAccessed;
use App\Domain\Integration\Listeners\HandleIntegrationAccessedWebhooks;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Policies\IntegrationAppPolicy;
use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\News\Events\NewsArticleRead;
use App\Domain\News\Listeners\HandleNewsArticlePublishedWebhooks;
use App\Domain\News\Listeners\SendNewsNotification;
use App\Domain\News\Models\NewsArticle;
use App\Domain\News\Models\NewsComment;
use App\Domain\News\Policies\NewsArticlePolicy;
use App\Domain\News\Policies\NewsCommentPolicy;
use App\Domain\Notification\Events\NotificationPreferencesUpdated;
use App\Domain\Notification\Events\NotificationsArchived;
use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Notification\Events\TicketDiscoverySettingsUpdated;
use App\Domain\Notification\Events\UserAttributesUpdated;
use App\Domain\Notification\Events\UserRolesChanged;
use App\Domain\Notification\Listeners\HandleProfileUpdatedWebhooks;
use App\Domain\Notification\Listeners\HandleUserRolesChangedWebhooks;
use App\Domain\Notification\Listeners\SendUserAttributesUpdatedNotification;
use App\Domain\Notification\Listeners\SendUserRolesChangedNotification;
use App\Domain\Orchestration\Actions\ResolveMatchHandler;
use App\Domain\Orchestration\Handlers\Tmt2MatchHandler;
use App\Domain\Orchestration\Listeners\HandleMatchCompleted as OrchestrationHandleMatchCompleted;
use App\Domain\Orchestration\Listeners\HandleMatchReadyForOrchestration as OrchestrationHandleMatchReady;
use App\Domain\Orchestration\Models\GameServer;
use App\Domain\Orchestration\Models\OrchestrationJob;
use App\Domain\Orchestration\Policies\GameServerPolicy;
use App\Domain\Orchestration\Policies\OrchestrationJobPolicy;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Domain\OrgaTeam\Policies\OrgaSubTeamPolicy;
use App\Domain\OrgaTeam\Policies\OrgaTeamPolicy;
use App\Domain\Program\Events\ProgramTimeSlotApproaching;
use App\Domain\Program\Listeners\SendProgramTimeSlotNotification;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\ProgramPolicy;
use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use App\Domain\Seating\Listeners\NotifyAffectedAssignees;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Policies\SeatPlanPolicy;
use App\Domain\Shop\Actions\FulfillOrder;
use App\Domain\Shop\Events\CartItemAdded;
use App\Domain\Shop\Events\TicketPurchased;
use App\Domain\Shop\Http\Controllers\PayPalWebhookController;
use App\Domain\Shop\Listeners\HandleStripeCheckoutCompleted;
use App\Domain\Shop\Listeners\HandleTicketPurchasedWebhooks;
use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Domain\Shop\Models\PaymentProviderCondition;
use App\Domain\Shop\Models\PurchaseRequirement;
use App\Domain\Shop\Models\ShopSetting;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Shop\PaymentProviders\OnSitePaymentProvider;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Shop\PaymentProviders\PayPalPaymentProvider;
use App\Domain\Shop\PaymentProviders\StripePaymentProvider;
use App\Domain\Shop\Policies\GlobalPurchaseConditionPolicy;
use App\Domain\Shop\Policies\PaymentProviderConditionPolicy;
use App\Domain\Shop\Policies\PurchaseRequirementPolicy;
use App\Domain\Shop\Policies\VoucherPolicy;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Sponsoring\Policies\SponsorLevelPolicy;
use App\Domain\Sponsoring\Policies\SponsorPolicy;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Policies\AddonPolicy;
use App\Domain\Ticketing\Policies\TicketCategoryPolicy;
use App\Domain\Ticketing\Policies\TicketPolicy;
use App\Domain\Ticketing\Policies\TicketTypePolicy;
use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Policies\VenuePolicy;
use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Listeners\HandleUserRegisteredWebhooks;
use App\Domain\Webhook\Listeners\SendWebhookPayload;
use App\Domain\Webhook\Models\Webhook;
use App\Domain\Webhook\Policies\WebhookPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\ModelCacheService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Telescope\TelescopeServiceProvider;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModelCacheService::class);

        $this->app->singleton(PaymentProviderManager::class, function ($app): PaymentProviderManager {
            $manager = new PaymentProviderManager;
            $manager->register($app->make(StripePaymentProvider::class));
            $manager->register($app->make(OnSitePaymentProvider::class));
            $manager->register($app->make(PayPalPaymentProvider::class));

            return $manager;
        });

        $paypalClientFactory = fn (): PayPalClient => tap(new PayPalClient, static function (PayPalClient $client): void {
            $client->setApiCredentials(config('paypal'));
            $client->getAccessToken();
        });

        $this->app->bind(PayPalPaymentProvider::class, fn () => new PayPalPaymentProvider($paypalClientFactory));

        $this->app->bind(PayPalWebhookController::class, fn ($app) => new PayPalWebhookController(
            $paypalClientFactory,
            $app->make(FulfillOrder::class),
        ));

        $this->app->tag([Tmt2MatchHandler::class], 'match_handlers');
        $this->app->when(ResolveMatchHandler::class)
            ->needs('$handlers')
            ->giveTagged('match_handlers');

        if ($this->app->environment('local') && class_exists(TelescopeServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
            // $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configureEvents();
        $this->configurePaypalAutoEnable();
    }

    /**
     * Auto-enable the PayPal payment method the first time credentials are
     * detected at boot. An explicit admin-toggled value (true or false) is
     * never overridden — only the initial "never set" state is filled in.
     */
    protected function configurePaypalAutoEnable(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        try {
            if (! Schema::hasTable('shop_settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        if (! $this->paypalCredentialsPresent()) {
            return;
        }

        $methods = ShopSetting::get('enabled_payment_methods');

        if (is_array($methods) && array_key_exists('paypal', $methods)) {
            return;
        }

        $current = is_array($methods) ? $methods : ShopSetting::enabledPaymentMethods();
        $current['paypal'] = true;
        ShopSetting::set('enabled_payment_methods', $current);
    }

    protected function paypalCredentialsPresent(): bool
    {
        $mode = (string) config('paypal.mode', 'sandbox');
        $env = config("paypal.{$mode}", []);

        return ! empty($env['client_id'] ?? '') && ! empty($env['client_secret'] ?? '');
    }

    /**
     * Register model policies.
     */
    protected function configurePolicies(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperadmin()) {
                return true;
            }
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Achievement::class, AchievementPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Venue::class, VenuePolicy::class);
        Gate::policy(Game::class, GamePolicy::class);
        Gate::policy(GameMode::class, GameModePolicy::class);
        Gate::policy(IntegrationApp::class, IntegrationAppPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Program::class, ProgramPolicy::class);
        Gate::policy(Sponsor::class, SponsorPolicy::class);
        Gate::policy(SponsorLevel::class, SponsorLevelPolicy::class);
        Gate::policy(OrgaTeam::class, OrgaTeamPolicy::class);
        Gate::policy(OrgaSubTeam::class, OrgaSubTeamPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketType::class, TicketTypePolicy::class);
        Gate::policy(TicketCategory::class, TicketCategoryPolicy::class);
        Gate::policy(Addon::class, AddonPolicy::class);
        Gate::policy(Voucher::class, VoucherPolicy::class);
        Gate::policy(PurchaseRequirement::class, PurchaseRequirementPolicy::class);
        Gate::policy(GlobalPurchaseCondition::class, GlobalPurchaseConditionPolicy::class);
        Gate::policy(PaymentProviderCondition::class, PaymentProviderConditionPolicy::class);
        Gate::policy(SeatPlan::class, SeatPlanPolicy::class);
        Gate::policy(NewsArticle::class, NewsArticlePolicy::class);
        Gate::policy(NewsComment::class, NewsCommentPolicy::class);
        Gate::policy(Webhook::class, WebhookPolicy::class);
        Gate::policy(GameServer::class, GameServerPolicy::class);
        Gate::policy(OrchestrationJob::class, OrchestrationJobPolicy::class);
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

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Register event listeners.
     */
    protected function configureEvents(): void
    {
        EventFacade::listen(AnnouncementPublished::class, SendAnnouncementNotification::class);
        EventFacade::listen(NewsArticlePublished::class, SendNewsNotification::class);
        EventFacade::listen(ProgramTimeSlotApproaching::class, SendProgramTimeSlotNotification::class);
        EventFacade::listen(UserRolesChanged::class, SendUserRolesChangedNotification::class);
        EventFacade::listen(UserRolesChanged::class, HandleUserRolesChangedWebhooks::class);
        EventFacade::listen(UserAttributesUpdated::class, SendUserAttributesUpdatedNotification::class);
        EventFacade::listen(Registered::class, HandleUserRegisteredWebhooks::class);
        EventFacade::listen(AnnouncementPublished::class, HandleAnnouncementPublishedWebhooks::class);
        EventFacade::listen(NewsArticlePublished::class, HandleNewsArticlePublishedWebhooks::class);
        EventFacade::listen(EventPublished::class, HandleEventPublishedWebhooks::class);
        EventFacade::listen(WebhookDispatched::class, SendWebhookPayload::class);
        EventFacade::listen(SeatAssignmentInvalidated::class, NotifyAffectedAssignees::class);

        // Stripe webhook listener for checkout fulfillment
        EventFacade::listen(WebhookReceived::class, HandleStripeCheckoutCompleted::class);

        // Webhook listeners for new user-action events
        EventFacade::listen(TicketPurchased::class, HandleTicketPurchasedWebhooks::class);
        EventFacade::listen(ProfileUpdated::class, HandleProfileUpdatedWebhooks::class);
        EventFacade::listen(IntegrationAccessed::class, HandleIntegrationAccessedWebhooks::class);

        // Orchestration listeners
        EventFacade::listen(MatchReadyForOrchestration::class, OrchestrationHandleMatchReady::class);
        EventFacade::listen(MatchCompleted::class, OrchestrationHandleMatchCompleted::class);

        // Achievement processing — listen to all grantable events
        EventFacade::listen(Registered::class, ProcessAchievements::class);
        EventFacade::listen(AnnouncementPublished::class, ProcessAchievements::class);
        EventFacade::listen(AnnouncementsViewed::class, ProcessAchievements::class);
        EventFacade::listen(CartItemAdded::class, ProcessAchievements::class);
        EventFacade::listen(EventPublished::class, ProcessAchievements::class);
        EventFacade::listen(IntegrationAccessed::class, ProcessAchievements::class);
        EventFacade::listen(NewsArticlePublished::class, ProcessAchievements::class);
        EventFacade::listen(NewsArticleRead::class, ProcessAchievements::class);
        EventFacade::listen(NotificationPreferencesUpdated::class, ProcessAchievements::class);
        EventFacade::listen(NotificationsArchived::class, ProcessAchievements::class);
        EventFacade::listen(ProfileUpdated::class, ProcessAchievements::class);
        EventFacade::listen(TicketDiscoverySettingsUpdated::class, ProcessAchievements::class);
        EventFacade::listen(TicketPurchased::class, ProcessAchievements::class);
        EventFacade::listen(UserRolesChanged::class, ProcessAchievements::class);
    }
}
