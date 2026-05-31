<?php

namespace App\Domain\Event\Enums\Concerns;

/**
 * Shared helpers for int-backed flag enums that are persisted as a single
 * summed bitset integer, following the LAN Party Publishing Standard v2
 * (sleeping, alcohol, smoking, age, and food policies).
 */
trait InteractsWithBitset
{
    /**
     * Decompose a stored bitset integer into the matching enum cases.
     *
     * @return array<int, self>
     */
    public static function fromBitset(int $bitset): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case): bool => $case->value !== 0 && ($bitset & $case->value) === $case->value,
        ));
    }

    /**
     * Compose a bitset integer from a list of flag values.
     *
     * @param  array<int, int|string>  $values
     */
    public static function toBitset(array $values): int
    {
        return array_reduce(
            $values,
            fn (int $carry, int|string $value): int => $carry | (int) $value,
            0,
        );
    }

    /**
     * Flag options as value/label pairs for admin checkbox groups.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    abstract public function label(): string;
}
