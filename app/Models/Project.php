<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }
}
