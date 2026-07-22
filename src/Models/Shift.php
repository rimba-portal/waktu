<?php

declare(strict_types=1);

namespace Rimba\Time\Models;

use App\Trees\Organization\Models\OrgTeam;
use App\Trees\Organization\Models\OrgUnit;
use App\Trees\Organization\Models\Staff;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'org_unit_id',
    'org_team_id',
    'staff_id',
    'type',
    'name',
    'description',
    'start_time',
    'end_time',
    'start_date',
    'end_date',
    'attributes',
])]
class Shift extends Model
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
            'org_unit_id' => 'integer',
            'org_team_id' => 'integer',
            'staff_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'attributes' => 'array',
        ];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function orgTeam(): BelongsTo
    {
        return $this->belongsTo(OrgTeam::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
