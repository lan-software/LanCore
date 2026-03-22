<?php

namespace App\Models;

use App\Concerns\HasModelCache;
use App\Enums\RoleName;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, HasModelCache;

    /**
     * @return array<int, string>
     */
    protected static function dropdownColumns(): array
    {
        return ['*'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => RoleName::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
