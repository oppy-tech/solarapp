<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ahj extends Model
{
    protected $fillable = [
        'name',
        'address_line_1',
        'city',
        'state',
        'zip',
        'contact_email',
        'stripe_publishable_key',
        'charges_fees', // boolean
        'is_live',      // boolean
        'meta',         // JSON column for extra data
    ];

    protected $casts = [
        'meta' => 'array',
        'charges_fees' => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
