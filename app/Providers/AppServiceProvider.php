<?php

namespace App\Providers;

use App\Domain\Event\Models\Event;
use App\Domain\Event\Policies\EventPolicy;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Policies\ProgramPolicy;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Shop\PaymentProviders\OnSitePaymentProvider;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Shop\PaymentProviders\StripePaymentProvider;
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
use App\Models\User;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentProviderManager::class, function ($app): PaymentProviderManager {
            $manager = new PaymentProviderManager;
            $manager->register($app->make(StripePaymentProvider::class));
            $manager->register($app->make(OnSitePaymentProvider::class));

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configureStorageMacros();
    }

    /**
     * Register model policies.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Venue::class, VenuePolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Program::class, ProgramPolicy::class);
        Gate::policy(Sponsor::class, SponsorPolicy::class);
        Gate::policy(SponsorLevel::class, SponsorLevelPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketType::class, TicketTypePolicy::class);
        Gate::policy(TicketCategory::class, TicketCategoryPolicy::class);
        Gate::policy(Addon::class, AddonPolicy::class);
        Gate::policy(Voucher::class, VoucherPolicy::class);
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

    /**
     * Register a Storage macro for generating public file URLs.
     *
     * When anonymous bucket access is disabled, returns a proxy URL
     * through this application. Otherwise, returns the direct S3 URL.
     */
    protected function configureStorageMacros(): void
    {
        Storage::macro('fileUrl', function (string $path): string {
            if (config('filesystems.disks.s3.anonymous_bucket_access')) {
                return Storage::url($path);
            }

            return route('storage.file', ['path' => $path]);
        });
    }
}
