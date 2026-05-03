<?php

namespace App\Domain\DataLifecycle\Providers;

use App\Domain\DataLifecycle\Anonymizers\AchievementsAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\CompetitionAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\DomainAnonymizerRegistry;
use App\Domain\DataLifecycle\Anonymizers\EmailLogAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\NewsAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\NotificationAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\OrgaTeamAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\PolicyAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\SessionsAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\ShopAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\SponsoringAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\TicketingAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\UserAnonymizer;
use App\Domain\DataLifecycle\Events\UserDeletionCancelled;
use App\Domain\DataLifecycle\Events\UserDeletionConfirmed;
use App\Domain\DataLifecycle\Events\UserDeletionRequested;
use App\Domain\DataLifecycle\Listeners\SendDeletionCancelledEmail;
use App\Domain\DataLifecycle\Listeners\SendDeletionConfirmationEmail;
use App\Domain\DataLifecycle\Listeners\SendDeletionScheduledEmail;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use App\Domain\DataLifecycle\Policies\DeletionRequestPolicy;
use App\Domain\DataLifecycle\Policies\RetentionPolicyPolicy;
use App\Domain\DataLifecycle\RetentionEvaluators\AccountingEvaluator;
use App\Domain\DataLifecycle\RetentionEvaluators\AuditEvaluator;
use App\Domain\DataLifecycle\RetentionEvaluators\ConsentEvaluator;
use App\Domain\DataLifecycle\RetentionEvaluators\Contracts\RetentionEvaluator;
use App\Domain\DataLifecycle\RetentionEvaluators\RetentionEvaluatorRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Wires up the DataLifecycle domain: anonymizer registry, retention
 * evaluator registry, and policy registrations. Order of anonymizers
 * matters — the users-row scrub MUST run last so per-domain anonymizers
 * can still resolve User properties.
 *
 * @see docs/mil-std-498/SDD.md §5.10 "Data Lifecycle Implementation"
 */
class DataLifecycleServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string<DomainAnonymizer>>
     */
    private const ANONYMIZERS = [
        SessionsAnonymizer::class,
        NotificationAnonymizer::class,
        SponsoringAnonymizer::class,
        OrgaTeamAnonymizer::class,
        AchievementsAnonymizer::class,
        CompetitionAnonymizer::class,
        NewsAnonymizer::class,
        TicketingAnonymizer::class,
        ShopAnonymizer::class,
        PolicyAnonymizer::class,
        EmailLogAnonymizer::class,
        // The users-row scrub must run last.
        UserAnonymizer::class,
    ];

    /**
     * @var list<class-string<RetentionEvaluator>>
     */
    private const EVALUATORS = [
        AccountingEvaluator::class,
        AuditEvaluator::class,
        ConsentEvaluator::class,
    ];

    public function register(): void
    {
        $this->app->singleton(DomainAnonymizerRegistry::class);
        $this->app->singleton(RetentionEvaluatorRegistry::class);
    }

    public function boot(): void
    {
        $anonymizers = $this->app->make(DomainAnonymizerRegistry::class);
        foreach (self::ANONYMIZERS as $class) {
            $anonymizers->register($this->app->make($class));
        }

        $evaluators = $this->app->make(RetentionEvaluatorRegistry::class);
        foreach (self::EVALUATORS as $class) {
            $evaluators->register($this->app->make($class));
        }

        Gate::policy(DeletionRequest::class, DeletionRequestPolicy::class);
        Gate::policy(RetentionPolicy::class, RetentionPolicyPolicy::class);

        Event::listen(UserDeletionRequested::class, SendDeletionConfirmationEmail::class);
        Event::listen(UserDeletionConfirmed::class, SendDeletionScheduledEmail::class);
        Event::listen(UserDeletionCancelled::class, SendDeletionCancelledEmail::class);
    }
}
