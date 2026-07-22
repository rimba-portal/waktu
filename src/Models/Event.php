<?php

declare(strict_types=1);

namespace Rimba\Time\Models;

use App\Trees\Organization\Models\OrgCorp;
use App\Trees\Organization\Models\OrgTeam;
use App\Trees\Organization\Models\OrgUnit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'org_corp_id',
    'org_unit_id',
    'org_team_id',
    'type',
    'name',
    'description',
    'starts_at',
    'ends_at',
    'is_blocking',
    'attributes',
])]
class Event extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',

            'org_corp_id' => 'integer',
            'org_unit_id' => 'integer',
            'org_team_id' => 'integer',

            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'start_UTC' => 'datetime',
            'end_UTC' => 'datetime',

            'is_all_day' => 'boolean',
            'is_blocking' => 'boolean',

            'attributes' => 'array',
        ];
    }

    public function orgCorp(): BelongsTo
    {
        return $this->belongsTo(OrgCorp::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function orgTeam(): BelongsTo
    {
        return $this->belongsTo(OrgTeam::class);
    }
}
