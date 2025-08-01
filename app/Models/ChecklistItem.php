<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $fillable = [
        'content',
        'is_completed',
        'task_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
