<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    use AuthorizesRequests;

    public $projects;
    public ?int $activeProjectId = null;
    public $activeProject = null;
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
    public $newTaskTests = [];

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
        try {
            $project = Project::with('members')->findOrFail($projectId);
            $this->authorize('view', $project);

            $this->activeProjectId = $project->id;
            $this->activeProject = $project;
            $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
            $this->projectMembers = $this->activeProject->members;
            $this->closeAllModals();
        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Você não tem permissão para ver este projeto.');
            $this->activeProjectId = null;
            $this->activeProject = null;
            $this->tasks = collect();
        }
    }

    public function createTask()
    {
        try {
            if (!$this->activeProject) return;
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

            $maxOrder = $this->activeProject->tasks()->max('order') ?? -1;
            
            $task = $this->activeProject->tasks()->create([
                'title' => $validatedData['newTaskTitle'],
                'description' => $validatedData['newTaskDescription'],
                'status' => $validatedData['newTaskStatus'],
                'priority' => $validatedData['newTaskPriority'],
                'user_id' => $validatedData['newTaskUserId'] ?: Auth::id(),
                'due_date' => $validatedData['newTaskDueDate'],
                'order' => $maxOrder + 1,
            ]);

            if (!empty($validatedData['newTaskTests'])) {
                foreach ($validatedData['newTaskTests'] as $test) {
                    if (!empty(trim($test))) {
                        $task->checklistItems()->create(['content' => trim($test), 'is_completed' => false]);
                    }
                }
            }

            $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
            $this->closeAllModals();

        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Você não tem permissão para criar tarefas neste projeto.');
            $this->closeAllModals();
        }
    }
    
    public function createProject()
    {
        $this->validate([
            'newProjectName' => 'required|string|max:255',
            'newProjectDescription' => 'nullable|string',
        ]);
        
        $project = Auth::user()->projects()->create([
            'name' => $this->newProjectName,
            'description' => $this->newProjectDescription,
            'owner_id' => Auth::id(),
        ]);
        
        $project->members()->attach(Auth::id(), ['role' => 'admin']);
        
        $this->closeAllModals();
        $this->loadProjects();
        $this->selectProject($project->id);
    }

    public function deleteTask()
    {
        try {
            if (!$this->taskToDelete) return;

            $this->authorize('delete', $this->taskToDelete);
            
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
            $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
            $this->closeAllModals();

        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Você não tem permissão para apagar esta tarefa.');
            $this->closeAllModals();
        }
    }
    
    public function deleteProject()
    {
        try {
            if (!$this->projectToDelete) return;
        
            $this->authorize('delete', $this->projectToDelete);
            
            foreach ($this->projectToDelete->tasks as $task) {
            }
            
            $this->projectToDelete->delete();
            $this->closeAllModals();
            $this->loadProjects();
            $this->activeProjectId = null;
            $this->activeProject = null;
            $this->tasks = collect();
            
            if ($this->projects->isNotEmpty()) {
                $this->selectProject($this->projects->first()->id);
            }

        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Você não tem permissão para apagar este projeto.');
            $this->closeAllModals();
        }
    }

    #[On('task-dropped')]
    public function updateTaskStatus($taskId, $newStatus, $orderedIds)
    {
        try {
            $task = Task::find($taskId);
            if (!$task || !$this->activeProject || $task->project_id !== $this->activeProject->id) {
                return;
            }
        
            $this->authorize('update', $task);
            $task->update(['status' => $newStatus]);
            
            foreach ($orderedIds as $index => $id) {
                Task::where('id', $id)->where('project_id', $this->activeProjectId)->update(['order' => $index]);
            }
    
            $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();

        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Você não tem permissão para mover tarefas.');
            $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
        }
    }
    
    public function openNewProjectModal()
    {
        $this->reset(['newProjectName', 'newProjectDescription']);
        $this->showNewProjectModal = true;
    }

    public function openNewTaskModal()
    {
        if (!$this->activeProject) return;

        $this->reset([
            'newTaskTitle', 'newTaskDescription', 'newTaskStatus', 
            'newTaskPriority', 'newTaskUserId', 'newTaskDueDate', 'newTaskTests'
        ]);
        $this->newTaskStatus = 'backlog';
        $this->newTaskPriority = 'normal';
        $this->showNewTaskModal = true;
    }
    
    public function openHistoryModal()
    {
        if (!$this->activeProject) return;
        $this->showHistoryModal = true;
    }
    
    public function openShareModal()
    {
        $this->shareLink = url("/projects/{$this->activeProjectId}/share");
        $this->showShareModal = true;
    }
    
    public function closeShareModal()
    {
        $this->showShareModal = false;
    }

    public function openDeleteTaskModal($taskId)
    {
        try {
            $task = Task::find($taskId);
            $this->authorize('delete', $task);
            $this->taskToDelete = $task;
            $this->showDeleteTaskModal = true;
        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Acesso negado.');
        }
    }

    public function openDeleteProjectModal()
    {
        try {
            if (!$this->activeProject) return;
            $this->authorize('delete', $this->activeProject);
            $this->projectToDelete = $this->activeProject;
            $this->showDeleteProjectModal = true;
        } catch (AuthorizationException $e) {
            $this->dispatch('access-denied', 'Acesso negado.');
        }
    }
    
    public function openTaskModal($taskId)
    {
        $this->selectedTask = Task::with('checklistItems')->find($taskId);
    }

    public function toggleChecklistItem($taskId, $itemId)
    {
        // Lógica de toggle de checklist
        $item = ChecklistItem::find($itemId);
        if ($item) {
            $item->is_completed = !$item->is_completed;
            $item->save();
            $this->selectedTask->refresh();
        }
    }

    public function closeAllModals()
    {
        $this->showNewProjectModal = false;
        $this->showNewTaskModal = false;
        $this->showHistoryModal = false;
        $this->showDeleteProjectModal = false;
        $this->showDeleteTaskModal = false;
        $this->showShareModal = false;
        $this->selectedTask = null;
        $this->taskToDelete = null;
        $this->projectToDelete = null;
    }

    public function addTest()
    {
        $this->newTaskTests[] = '';
    }

    public function removeTest($index)
    {
        unset($this->newTaskTests[$index]);
        $this->newTaskTests = array_values($this->newTaskTests);
    }
    
    public function render()
    {
        return view('livewire.dashboard');
    }
}