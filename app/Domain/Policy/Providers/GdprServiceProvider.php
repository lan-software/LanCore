<?php

namespace App\Domain\Policy\Providers;

use App\Domain\Achievements\Gdpr\AchievementsDataSource;
use App\Domain\Competition\Gdpr\CompetitionDataSource;
use App\Domain\News\Gdpr\NewsDataSource;
use App\Domain\Notification\Gdpr\NotificationDataSource;
use App\Domain\OrgaTeam\Gdpr\OrgaTeamDataSource;
use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceRegistry;
use App\Domain\Policy\Gdpr\PolicyDataSource;
use App\Domain\Profile\Gdpr\ProfileDataSource;
use App\Domain\Shop\Gdpr\ShopDataSource;
use App\Domain\Sponsoring\Gdpr\SponsoringDataSource;
use App\Domain\Ticketing\Gdpr\TicketingDataSource;
use App\Gdpr\AuditDataSource;
use App\Gdpr\SessionsDataSource;
use Illuminate\Support\ServiceProvider;

/**
 * Single chokepoint registering every GdprDataSource implementation.
 * Adding a new domain to the export = appending one line to ::SOURCES.
 *
 * @see docs/mil-std-498/SDD.md "GDPR Export Implementation"
 */
class GdprServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string<GdprDataSource>>
     */
    private const SOURCES = [
        ProfileDataSource::class,
        PolicyDataSource::class,
        SessionsDataSource::class,
        AuditDataSource::class,
        ShopDataSource::class,
        TicketingDataSource::class,
        CompetitionDataSource::class,
        NewsDataSource::class,
        NotificationDataSource::class,
        OrgaTeamDataSource::class,
        SponsoringDataSource::class,
        AchievementsDataSource::class,
    ];

    public function register(): void
    {
        $this->app->singleton(GdprDataSourceRegistry::class);
    }

    public function boot(): void
    {
        /** @var GdprDataSourceRegistry $registry */
        $registry = $this->app->make(GdprDataSourceRegistry::class);

        foreach (self::SOURCES as $source) {
            $registry->register($source);
        }
    }
}
