<?php

namespace App\Concerns;

use App\Services\ModelCacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Provides automatic cache invalidation for Eloquent models.
 *
 * When a model with this trait is saved or deleted, the corresponding
 * cache group (and any related groups) are flushed automatically.
 *
 * Models can override `cacheGroup()` and `relatedCacheGroups()` to
 * customise which cache groups are invalidated on mutation.
 */
trait HasModelCache
{
    public static function bootHasModelCache(): void
    {
        static::saved(function (): void {
            app(ModelCacheService::class)->flushGroup(
                array_merge([static::cacheGroup()], static::relatedCacheGroups()),
            );
        });

        static::deleted(function (): void {
            app(ModelCacheService::class)->flushGroup(
                array_merge([static::cacheGroup()], static::relatedCacheGroups()),
            );
        });
    }

    /**
     * The cache group name for this model. Defaults to the table name.
     */
    public static function cacheGroup(): string
    {
        return (new static)->getTable();
    }

    /**
     * Additional cache groups to flush when this model changes.
     *
     * @return array<int, string>
     */
    public static function relatedCacheGroups(): array
    {
        return [];
    }

    /**
     * Retrieve a cached set of dropdown options for this model.
     *
     * Override `dropdownQuery()` and `dropdownColumns()` to customise
     * the query and selected columns.
     *
     * @param  array<int, string>|null  $columns
     * @return Collection<int, static>
     */
    public static function dropdownOptions(?array $columns = null): Collection
    {
        $columns ??= static::dropdownColumns();

        return app(ModelCacheService::class)->remember(
            static::cacheGroup(),
            'dropdown_options',
            fn (): Collection => static::dropdownQuery()->get($columns),
        );
    }

    /**
     * The base query for dropdown options. Override to customise ordering.
     *
     * @return Builder<static>
     */
    protected static function dropdownQuery(): Builder
    {
        return static::query()->orderBy('name');
    }

    /**
     * The columns to select for dropdown options.
     *
     * @return array<int, string>
     */
    protected static function dropdownColumns(): array
    {
        return ['id', 'name'];
    }
}
