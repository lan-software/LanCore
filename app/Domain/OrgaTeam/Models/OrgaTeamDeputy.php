<?php

namespace App\Domain\OrgaTeam\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['orga_team_id', 'user_id', 'sort_order'])]
class OrgaTeamDeputy extends Pivot
{
    use HasUlids;

    protected $table = 'orga_team_deputies';

    public $timestamps = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgaTeam(): BelongsTo
    {
        return $this->belongsTo(OrgaTeam::class);
    }
}
