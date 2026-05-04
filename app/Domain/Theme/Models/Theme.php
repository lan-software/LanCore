<?php

namespace App\Domain\Theme\Models;

use App\Domain\Event\Models\Event;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array<string, string>|null $light_config
 * @property array<string, string>|null $dark_config
 *
 * @see docs/mil-std-498/SSS.md CAP-THM-001..004
 * @see docs/mil-std-498/SRS.md THM-F-001, THM-F-002
 * @see docs/mil-std-498/SDD.md §5.11
 */
#[Fillable(['name', 'description', 'light_config', 'dark_config'])]
class Theme extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    protected static function newFactory(): ThemeFactory
    {
        return ThemeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'light_config' => 'array',
            'dark_config' => 'array',
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
