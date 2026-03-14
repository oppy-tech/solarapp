<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'title',
        'ahj_id',
        'project_type_id', // e.g. 'PV' or 'PV+ST'
        'status',          // 'draft', 'submitted', 'approved', 'revision_required'
        'submitted_at',    // datetime
        'approved_at',     // datetime, null if not approved
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function ahj()
    {
        return $this->belongsTo(Ahj::class);
    }
}
