<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installer extends Model
{
    protected $fillable = [
        'company_name',
        'email',
        'license_number',
        'state',
        'is_active',
        'meta', // JSON column for extra data
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
