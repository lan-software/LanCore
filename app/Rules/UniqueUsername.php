<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Case-insensitive uniqueness check for the public-facing username.
 *
 * Laravel's built-in `Rule::unique()` ANDs an exact-match clause onto
 * any custom `where` callback, which cannot be turned off — so we
 * implement the lookup directly here.
 *
 * @see docs/mil-std-498/SRS.md USR-F-022
 */
class UniqueUsername implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $query = User::query()->whereRaw('LOWER(username) = ?', [strtolower($value)]);

        if ($this->ignoreId !== null) {
            $query->whereKeyNot($this->ignoreId);
        }

        if ($query->exists()) {
            $fail('validation.unique')->translate(['attribute' => $attribute]);
        }
    }
}
