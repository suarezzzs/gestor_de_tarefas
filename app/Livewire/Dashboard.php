<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Dashboard extends Component
{
    use AuthorizesRequests;

    public $projects;
    public $activeProject;
    public $tasks;

    // Propriedades para o modal de detalhes da tarefa
    public $selectedTask;
    public $showTaskModal = false;

    // Propriedades para o modal de criação de projeto
    public $newProjectName;
    public $newProjectDescription;
    public $showNewProjectModal = false;

    // Mapeamento de prioridades para cores e texto
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
        // Carrega apenas os projetos aos quais o usuário pertence.
        $this->projects = Auth::user()->projects()->orderBy('created_at')->get();
    }

    public function selectProject($projectId)
    {
        $project = Project::findOrFail($projectId);
        
        // Usa a ProjectPolicy para garantir que o usuário pode ver este projeto.
        $this->authorize('view', $project);

        $this->activeProject = $project;
        $this->tasks = $this->activeProject->tasks()->with('user')->orderBy('order')->get();
        $this->closeAllModals();
    }

    public function openTaskModal($taskId)
    {
        $this->selectedTask = Task::with('checklistItems', 'user')->find($taskId);
        $this->showTaskModal = true;
    }

    public function openNewProjectModal()
    {
        $this->newProjectName = '';
        $this->newProjectDescription = '';
        $this->showNewProjectModal = true;
    }
    
    public function closeAllModals()
    {
        $this->showTaskModal = false;
        $this->showNewProjectModal = false;
        $this->selectedTask = null;
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
            'user_id' => Auth::id(), // Define o criador como o dono principal
        ]);

        // Anexa o criador como um membro com a função 'admin'.
        // Este é o passo crucial para a colaboração!
        $project->members()->attach(Auth::id(), ['role' => 'admin']);

        $this->closeAllModals();
        $this->loadProjects();
        $this->selectProject($project->id);
    }

    public function updateTaskStatus($taskId, $newStatus, $orderedIds)
    {
        $task = Task::find($taskId);
        // Adicionar verificação de policy para mover tarefas no futuro
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
