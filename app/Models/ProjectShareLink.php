<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectShareLink extends Model
{
    protected $fillable = [
        'project_id',
        'token',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public static function generateToken()
    {
        return Str::random(32);
    }

    public function getFullUrlAttribute()
    {
        return url("/join-project/{$this->token}");
    }
}
