<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'user_id',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function taskHistories()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function shareLink()
    {
        return $this->hasOne(ProjectShareLink::class);
    }
}
