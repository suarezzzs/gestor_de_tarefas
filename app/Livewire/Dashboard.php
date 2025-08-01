<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    use AuthorizesRequests;

    public $projects;
    public $activeProject;
    public $tasks;
    public $projectMembers = [];

    public $selectedTask;

    public $newProjectName;
    public $newProjectDescription;
    public $showNewProjectModal = false;

    public $showNewTaskModal = false;
    public $newTaskTitle;
    public $newTaskDescription;
    public $newTaskStatus = 'backlog';
    public $newTaskPriority = 'normal';
    public $newTaskUserId;
    public $newTaskDueDate;
    public $newTaskTests = []; // Array para armazenar os testes do checklist

    // Novas propriedades
    public $showHistoryModal = false;
    public $showShareModal = false;
    public $showDeleteProjectModal = false;
    public $showDeleteTaskModal = false;
    public $taskToDelete = null;
    public $projectToDelete = null;
    public $shareLink = '';

    public $priorityMap = [
        'low' => ['text' => 'Baixa', 'color' => 'gray-500'],
        'normal' => ['text' => 'Normal', 'color' => 'sky-500'],
        'high' => ['text' => 'Alta', 'color' => 'yellow-500'],
        'urgent' => ['text' => 'Urgente', 'color' => 'red-500'],
    ];

    public function mount()
    {
        $this->loadProjects();
        if ($this->projects->isNotEmpty()) {
            $this->selectProject($this->projects->first()->id);
        }
    }

    public function loadProjects()
    {
        $this->projects = Auth::user()->projects()->orderBy('created_at')->get();
    }

    public function selectProject($projectId)
    {
        $project = Project::with('members')->findOrFail($projectId);
        $this->authorize('view', $project);

        $this->activeProject = $project;
        $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
        $this->projectMembers = $this->activeProject->members;
        $this->closeAllModals();
    }

    public function openTaskModal($taskId)
    {
        $this->selectedTask = Task::with('checklistItems', 'user')->find($taskId);
        $this->authorize('view', $this->selectedTask);
    }

    public function openNewProjectModal()
    {
        $this->newProjectName = '';
        $this->newProjectDescription = '';
        $this->showNewProjectModal = true;
    }
    
    public function openNewTaskModal()
    {
        $this->reset([
            'newTaskTitle', 'newTaskDescription', 'newTaskStatus', 
            'newTaskPriority', 'newTaskUserId', 'newTaskDueDate', 'newTaskTests'
        ]);
        $this->newTaskStatus = 'backlog';
        $this->newTaskPriority = 'normal';
        $this->newTaskTests = []; // Inicializar array vazio
        $this->showNewTaskModal = true;
    }

    public function addTest()
    {
        $this->newTaskTests[] = '';
    }

    public function removeTest($index)
    {
        unset($this->newTaskTests[$index]);
        $this->newTaskTests = array_values($this->newTaskTests); // Reindexar array
    }

    public function toggleChecklistItem($taskId, $itemIndex)
    {
        $task = Task::with('checklistItems')->find($taskId);
        $this->authorize('update', $task);
        
        $checklistItem = $task->checklistItems->get($itemIndex);
        if ($checklistItem) {
            $checklistItem->update([
                'is_completed' => !$checklistItem->is_completed
            ]);
            
            // Recarregar a task selecionada
            $this->selectedTask = Task::with('checklistItems', 'user')->find($taskId);
        }
    }

    public function createTask()
    {
        $this->authorize('createTask', $this->activeProject);

        $validatedData = $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskDescription' => 'nullable|string',
            'newTaskStatus' => 'required|in:backlog,analysis,doing,review,done',
            'newTaskPriority' => 'required|in:low,normal,high,urgent',
            'newTaskUserId' => 'nullable|exists:users,id',
            'newTaskDueDate' => 'nullable|date',
            'newTaskTests' => 'nullable|array',
            'newTaskTests.*' => 'nullable|string|max:255',
        ]);

        // Obter a próxima ordem para a nova tarefa
        $maxOrder = $this->activeProject->tasks()->max('order') ?? -1;
        $nextOrder = $maxOrder + 1;

        $task = $this->activeProject->tasks()->create([
            'title' => $validatedData['newTaskTitle'],
            'description' => $validatedData['newTaskDescription'],
            'status' => $validatedData['newTaskStatus'],
            'priority' => $validatedData['newTaskPriority'],
            'user_id' => $validatedData['newTaskUserId'] ?: Auth::id(), // Usa o usuário atual se nenhum for selecionado
            'due_date' => $validatedData['newTaskDueDate'],
            'order' => $nextOrder,
        ]);

        // Criar os itens do checklist se houver
        if (!empty($validatedData['newTaskTests'])) {
            foreach ($validatedData['newTaskTests'] as $test) {
                if (!empty(trim($test))) {
                    $task->checklistItems()->create([
                        'content' => trim($test),
                        'is_completed' => false,
                    ]);
                }
            }
        }

        $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
        $this->closeAllModals();
    }

    public function closeAllModals()
    {
        $this->showNewProjectModal = false;
        $this->showNewTaskModal = false;
        $this->showHistoryModal = false;
        $this->showDeleteProjectModal = false;
        $this->showDeleteTaskModal = false;
        $this->selectedTask = null;
        $this->taskToDelete = null;
        $this->projectToDelete = null;
        // Não fechar o modal de compartilhamento aqui, pois pode estar sendo usado
        // $this->showShareModal = false;
        // $this->shareLink = '';
    }

    public function openHistoryModal()
    {
        $this->showHistoryModal = true;
    }

    public function openShareModal()
    {
        $this->authorize('update', $this->activeProject);
        
        // Criar ou obter link de compartilhamento
        $shareLink = $this->activeProject->shareLink;
        if (!$shareLink) {
            $shareLink = $this->activeProject->shareLink()->create([
                'token' => \App\Models\ProjectShareLink::generateToken(),
                'expires_at' => now()->addDays(7),
            ]);
        }
        
        $this->shareLink = $shareLink->full_url;
        $this->showShareModal = true;
    }

    public function closeShareModal()
    {
        $this->showShareModal = false;
        $this->shareLink = '';
    }

    public function openDeleteProjectModal()
    {
        $this->authorize('delete', $this->activeProject);
        $this->projectToDelete = $this->activeProject;
        $this->showDeleteProjectModal = true;
    }

    public function deleteProject()
    {
        $this->authorize('delete', $this->projectToDelete);
        
        // Mover todas as tarefas para o histórico
        foreach ($this->projectToDelete->tasks as $task) {
            TaskHistory::create([
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'user_id' => $task->user_id,
                'action' => 'deleted',
                'deleted_by' => Auth::id(),
                'deleted_at' => now(),
            ]);
        }
        
        $this->projectToDelete->delete();
        $this->closeAllModals();
        $this->loadProjects();
        $this->activeProject = null;
        $this->tasks = collect();
    }

    public function openDeleteTaskModal($taskId)
    {
        $task = Task::find($taskId);
        $this->authorize('delete', $task); // Corrigido para usar a policy de Task
        $this->taskToDelete = $task;
        $this->showDeleteTaskModal = true;
    }

    public function deleteTask()
    {
        $this->authorize('delete', $this->taskToDelete); // Corrigido para usar a policy de Task
        
        // Criar registro no histórico
        TaskHistory::create([
            'project_id' => $this->taskToDelete->project_id,
            'task_id' => $this->taskToDelete->id,
            'title' => $this->taskToDelete->title,
            'description' => $this->taskToDelete->description,
            'status' => $this->taskToDelete->status,
            'priority' => $this->taskToDelete->priority,
            'due_date' => $this->taskToDelete->due_date,
            'user_id' => $this->taskToDelete->user_id,
            'action' => 'deleted',
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
        ]);
        
        $this->taskToDelete->delete();
        $this->closeAllModals();
        $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
    }

    public function createProject()
    {
        $this->authorize('create', Project::class);

        $validatedData = $this->validate([
            'newProjectName' => 'required|string|max:255',
            'newProjectDescription' => 'nullable|string',
        ]);

        $project = Project::create([
            'name' => $validatedData['newProjectName'],
            'description' => $validatedData['newProjectDescription'],
            'user_id' => Auth::id(),
        ]);

        $project->members()->attach(Auth::id(), ['role' => 'admin']);

        $this->closeAllModals();
        $this->loadProjects();
        $this->selectProject($project->id);
    }

    #[On('task-dropped')]
    public function updateTaskStatus($taskId, $newStatus, $orderedIds)
    {
        $task = Task::find($taskId);
        $this->authorize('update', $task->project);
        
        $task->update(['status' => $newStatus]);
        
        foreach ($orderedIds as $index => $id) {
            Task::where('id', $id)->update(['order' => $index]);
        }

        $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
