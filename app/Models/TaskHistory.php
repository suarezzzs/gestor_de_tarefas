<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskHistory extends Model
{
    protected $fillable = [
        'project_id',
        'task_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'user_id',
        'action',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
