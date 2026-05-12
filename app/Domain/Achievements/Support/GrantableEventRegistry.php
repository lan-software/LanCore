<?php

namespace App\Domain\Achievements\Support;

use App\Domain\Achievements\Contracts\HasGrantingUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;

/**
 * Source of truth for "which events can grant an achievement".
 *
 * An event class qualifies when ALL of:
 *   1. It is a concrete (non-abstract) class.
 *   2. It is reachable by name from PHP (autoloadable under the standard
 *      `App\` PSR-4 root, or explicitly allowlisted — currently only
 *      `Illuminate\Auth\Events\Registered`).
 *   3. It exposes a `User` instance via ONE of:
 *      a. Implements `HasGrantingUser`.
 *      b. Has a public property `user` typed as `App\Models\User`.
 *      c. Has a public method `user()` whose return type is `User` or `?User`.
 *
 * Discovery scans `app/` for files in any namespace segment named `Events`.
 * Results are cached for the process lifetime (Octane-safe: per worker).
 *
 * @see docs/mil-std-498/SRS.md ACH-F-006
 */
class GrantableEventRegistry
{
    /**
     * Classes that don't live under app/ but are still grantable.
     * Keep this list short; prefer the marker interface for domain events.
     *
     * @var list<class-string>
     */
    private const ALWAYS_INCLUDED = [
        Registered::class,
    ];

    /**
     * @var list<class-string>|null
     */
    private ?array $cache = null;

    public function __construct(private readonly ?string $basePath = null) {}

    /**
     * Return the set of grantable event class names (FQCN).
     *
     * @return list<class-string>
     */
    public function eventClasses(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $base = $this->basePath ?? app_path();
        $found = [];

        foreach (self::ALWAYS_INCLUDED as $fqcn) {
            if ($this->isQualified($fqcn)) {
                $found[$fqcn] = true;
            }
        }

        $finder = (new Finder)
            ->in($base)
            ->path('/(^|\/)Events(\/|$)/')
            ->files()
            ->name('*.php');

        foreach ($finder as $file) {
            $fqcn = $this->fqcnFromPath($file->getRealPath(), $base);
            if ($fqcn === null) {
                continue;
            }

            if ($this->isQualified($fqcn)) {
                $found[$fqcn] = true;
            }
        }

        $classes = array_keys($found);
        sort($classes);

        return $this->cache = $classes;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(): array
    {
        return array_map(
            fn (string $fqcn): array => [
                'value' => $fqcn,
                'label' => $this->labelFor($fqcn),
            ],
            $this->eventClasses(),
        );
    }

    public function isGrantable(string $fqcn): bool
    {
        return in_array($fqcn, $this->eventClasses(), true);
    }

    /**
     * Resolve the user a given event refers to (or null when none).
     * Mirrors the resolution order used by `ProcessAchievements`.
     */
    public function userFor(object $event): ?User
    {
        if ($event instanceof HasGrantingUser) {
            return $event->grantingUser();
        }

        if ($event instanceof Registered) {
            return $event->user instanceof User ? $event->user : null;
        }

        if (property_exists($event, 'user') && $event->user instanceof User) {
            return $event->user;
        }

        if (method_exists($event, 'user')) {
            $candidate = $event->user();

            return $candidate instanceof User ? $candidate : null;
        }

        return null;
    }

    public function labelFor(string $fqcn): string
    {
        $short = (string) Str::afterLast($fqcn, '\\');

        return trim((string) preg_replace('/(?<!^)([A-Z])/', ' $1', $short));
    }

    /**
     * Reset the in-memory cache. Intended for tests; not normally needed.
     */
    public function flush(): void
    {
        $this->cache = null;
    }

    private function isQualified(string $fqcn): bool
    {
        if (! class_exists($fqcn)) {
            return false;
        }

        $reflection = new ReflectionClass($fqcn);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            return false;
        }

        if ($reflection->implementsInterface(HasGrantingUser::class)) {
            return true;
        }

        if ($this->hasUserProperty($reflection)) {
            return true;
        }

        if ($this->hasUserMethod($reflection)) {
            return true;
        }

        // Special-case: Laravel's Registered event ships `User|Authenticatable`
        // untyped before 11.x; treat it as qualified by class identity.
        return $fqcn === Registered::class;
    }

    private function hasUserProperty(ReflectionClass $reflection): bool
    {
        if (! $reflection->hasProperty('user')) {
            return false;
        }

        $property = $reflection->getProperty('user');
        if (! $property->isPublic() || $property->isStatic()) {
            return false;
        }

        return $this->typeReferencesUser($property);
    }

    private function hasUserMethod(ReflectionClass $reflection): bool
    {
        if (! $reflection->hasMethod('user')) {
            return false;
        }

        $method = $reflection->getMethod('user');
        if (! $method->isPublic() || $method->isStatic() || $method->isAbstract()) {
            return false;
        }

        if ($method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        return $this->typeReferencesUser($method);
    }

    private function typeReferencesUser(ReflectionProperty|ReflectionMethod $member): bool
    {
        $type = $member instanceof ReflectionProperty
            ? $member->getType()
            : $member->getReturnType();

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        return $type->getName() === User::class;
    }

    private function fqcnFromPath(string $path, string $base): ?string
    {
        $relative = ltrim(str_replace([$base, '\\'], ['', '/'], $path), '/');
        $relative = Str::beforeLast($relative, '.php');

        if ($relative === '') {
            return null;
        }

        $fqcn = 'App\\'.str_replace('/', '\\', $relative);

        return class_exists($fqcn) ? $fqcn : null;
    }
}
