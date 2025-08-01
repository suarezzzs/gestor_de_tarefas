<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        // Qualquer membro do projeto pode ver a task
        return $task->project && $task->project->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        // Qualquer usuário autenticado pode criar (mas normalmente é checado via ProjectPolicy)
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        // Qualquer membro do projeto pode atualizar a task
        $project = $task->project;
        if (!$project) return false;
        return $project->members()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Task $task): bool
    {
        // Apenas admin do projeto pode deletar
        $project = $task->project;
        if (!$project) return false;
        $member = $project->members()->where('user_id', $user->id)->first();
        return $member && $member->pivot->role === 'admin';
    }

    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
