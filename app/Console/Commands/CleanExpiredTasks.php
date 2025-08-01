<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanExpiredTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move expired tasks to history and delete them after 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning expired tasks...');

        // Tarefas expiradas há mais de 30 dias
        $expiredTasks = Task::where('due_date', '<', Carbon::now()->subDays(30))
            ->where('status', '!=', 'done')
            ->get();

        foreach ($expiredTasks as $task) {
            // Criar registro no histórico
            TaskHistory::create([
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'user_id' => $task->user_id,
                'action' => 'expired',
                'deleted_at' => Carbon::now(),
            ]);

            // Deletar a tarefa permanentemente
            $task->forceDelete();
        }

        // Tarefas finalizadas há mais de 30 dias
        $completedTasks = Task::where('status', 'done')
            ->where('updated_at', '<', Carbon::now()->subDays(30))
            ->get();

        foreach ($completedTasks as $task) {
            // Criar registro no histórico
            TaskHistory::create([
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'user_id' => $task->user_id,
                'action' => 'completed',
                'deleted_at' => Carbon::now(),
            ]);

            // Deletar a tarefa permanentemente
            $task->forceDelete();
        }

        $this->info("Processed {$expiredTasks->count()} expired tasks and {$completedTasks->count()} completed tasks.");
    }
}
