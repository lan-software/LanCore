<?php

namespace App\Domain\Policy\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Registry of GdprDataSource implementations. A single GdprServiceProvider
 * registers each domain's source here in `boot()`. The artisan command
 * iterates over `all()` to build the export.
 */
class GdprDataSourceRegistry
{
    /**
     * @var array<string, GdprDataSource>
     */
    private array $sources = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  class-string<GdprDataSource>  $sourceClass
     */
    public function register(string $sourceClass): void
    {
        $instance = $this->container->make($sourceClass);

        if (! $instance instanceof GdprDataSource) {
            throw new InvalidArgumentException(
                sprintf('%s does not implement %s', $sourceClass, GdprDataSource::class),
            );
        }

        $this->sources[$instance->key()] = $instance;
    }

    /**
     * @return array<string, GdprDataSource>
     */
    public function all(): array
    {
        return $this->sources;
    }
}
