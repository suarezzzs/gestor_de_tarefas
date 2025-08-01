<div class="flex h-screen">
    <aside class="w-64 flex-shrink-0 bg-[#0c0b15] p-4 flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-8 px-2">
                <i class="fa-solid fa-rocket text-violet-400 text-2xl"></i>
                <span class="text-xl font-bold text-white">TaskFlow</span>
            </div>
            <h3 class="text-sm font-semibold text-slate-400 px-2 mb-2">MEUS PROJETOS</h3>
            <nav class="flex flex-col gap-1">
                @forelse ($projects as $project)
                    <a href="#" wire:click.prevent="selectProject({{ $project->id }})" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ $activeProjectId == $project->id ? 'bg-violet-600 text-white font-semibold' : 'hover:bg-slate-700/50 text-slate-300' }}">
                        <i class="fa-solid fa-rocket w-5 text-center"></i>
                        <span>{{ $project->name }}</span>
                    </a>
                @empty
                    <p class="text-slate-500 text-sm px-3">Crie seu primeiro projeto!</p>
                @endforelse
            </nav>
        </div>
        <button wire:click="openNewProjectModal" class="w-full bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
            <i class="fa-solid fa-plus mr-2"></i>Novo Projeto
        </button>
    </aside>

    <main class="flex-1 p-8 flex flex-col overflow-y-auto">
        @if ($activeProject)
            <header class="flex justify-between items-center mb-6 flex-shrink-0">
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ $activeProject->name }}</h1>
                    <div class="flex items-center gap-4 mt-2">
                        <p class="text-slate-400">Membros:</p>
                        <div class="flex -space-x-2">
                            @if($projectMembers && $projectMembers->count() > 0)
                                @foreach($projectMembers->take(3) as $member)
                                    <img class="inline-block h-8 w-8 rounded-full ring-2 ring-[#0c0b15]" 
                                         src="https://i.pravatar.cc/32?u={{ $member->name }}" 
                                         alt="{{ $member->name }}" 
                                         title="{{ $member->name }} {{ $member->pivot && $member->pivot->role === 'admin' ? '(Admin)' : '' }}">
                                @endforeach
                                @if($projectMembers->count() > 3)
                                    <div class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 ring-2 ring-[#0c0b15] text-xs text-white">
                                        +{{ $projectMembers->count() - 3 }}
                                    </div>
                                @endif
                            @else
                                <div class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 ring-2 ring-[#0c0b15] text-xs text-white">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button wire:click="openHistoryModal" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                        <i class="fa-solid fa-history mr-2"></i>Histórico
                    </button>
                    <button wire:click="openShareModal" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                        <i class="fa-solid fa-share mr-2"></i>Add Colaborador
                    </button>
                    <button wire:click="openDeleteProjectModal" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                        <i class="fa-solid fa-trash mr-2"></i>Deletar Projeto
                    </button>
                    <div class="relative">
                        <button id="notification-btn" class="text-slate-400 hover:text-white text-xl transition-colors">
                            <i class="fa-solid fa-bell"></i>
                            <span id="notification-dot" class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full" style="display: none;"></span>
                        </button>
                    </div>
                    <button wire:click="openNewTaskModal" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-5 rounded-lg transition-colors">
                        <i class="fa-solid fa-plus mr-2"></i>Criar Tarefa
                    </button>
                </div>
            </header>

            <div id="kanban-board" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 flex-1">
                @php
                    $columns = [
                        'backlog' => ['title' => 'Backlog', 'color' => 'slate-500'],
                        'analysis' => ['title' => 'Análise', 'color' => 'purple-500'],
                        'doing' => ['title' => 'Em Andamento', 'color' => 'blue-500'],
                        'review' => ['title' => 'Testes', 'color' => 'yellow-500'],
                        'done' => ['title' => 'Finalizado', 'color' => 'green-500']
                    ];
                @endphp

                @foreach ($columns as $status => $column)
                    <div class="flex flex-col">
                        <h2 class="text-lg font-semibold mb-2 pb-2 border-b-2 border-{{ $column['color'] }} text-white">
                            {{ $column['title'] }} ({{ $tasks->where('status', $status)->count() }})
                        </h2>
                        <div class="kanban-column space-y-4 pt-2 flex-1" data-status="{{ $status }}" 
                             @dragover.prevent @drop="$wire.updateTaskStatus($event.dataTransfer.getData('taskId'), '{{ $status }}', Array.from(event.target.children).map(el => el.id.split('-')[1]))">
                            @foreach ($tasks->where('status', $status) as $index => $task)
                                @php
                                    $isOwner = $task->user_id == Auth::id();
                                    $priorityColors = ['low' => 'sky', 'normal' => 'sky', 'high' => 'yellow', 'urgent' => 'red'];
                                    $color = $priorityColors[$task->priority] ?? 'slate';
                                    $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;
                                    $isExpired = $dueDate && $dueDate->isPast() && $status !== 'done';
                                    $expiredClass = $isExpired ? 'border-red-500/50 bg-red-500/10' : 'border-slate-700 bg-slate-800';
                                @endphp
                                <div id="task-{{ $task->id }}" 
                                     class="task-card {{ $expiredClass }} p-4 rounded-lg {{ $isOwner ? 'cursor-grab' : 'opacity-60 cursor-not-allowed' }}" 
                                     style="animation-delay: {{ $index * 50 }}ms;" 
                                     draggable="{{ $isOwner }}" 
                                     @dragstart="event.dataTransfer.setData('taskId', {{ $task->id }});"
                                     wire:click="{{ $isOwner ? "openTaskModal($task->id)" : '' }}"
                                     wire:key="task-{{ $task->id }}">
                                    <div class="flex justify-between items-start">
                                        <h3 class="font-bold text-white pr-2">{{ $task->title }}</h3>
                                        <div class="flex items-center gap-2">
                                            @if(!$isOwner)
                                                <i class="fa-solid fa-lock text-slate-500"></i>
                                            @endif
                                            @if(Auth::user()->projects()->where('project_id', $activeProject->id)->wherePivot('role', 'admin')->exists())
                                                <button wire:click.stop="openDeleteTaskModal({{ $task->id }})" class="text-red-400 hover:text-red-300 text-sm">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center mt-3">
                                        <span class="px-2 py-1 text-xs rounded-full bg-{{ $color }}-500/30 text-{{ $color }}-300">{{ $priorityMap[$task->priority]['text'] }}</span>
                                        <img class="h-6 w-6 rounded-full" src="https://i.pravatar.cc/32?u={{ $task->user->name ?? 'unknown' }}" alt="{{ $task->user->name ?? 'Sem responsável' }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            
            @php
                $expiredTasks = $tasks->filter(function($task) {
                    $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;
                    return $dueDate && $dueDate->isPast() && $task->status !== 'done';
                });
            @endphp
            @if($expiredTasks->count() > 0)
                <div class="mt-8 flex-shrink-0">
                    <h2 class="text-xl font-bold text-red-400 mb-4">
                        <i class="fa-solid fa-fire-flame-curved mr-2"></i>Tarefas Expiradas
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        @foreach($expiredTasks as $task)
                            @php
                                $priorityColors = ['low' => 'sky', 'normal' => 'sky', 'high' => 'yellow', 'urgent' => 'red'];
                                $color = $priorityColors[$task->priority] ?? 'slate';
                            @endphp
                            <div class="task-card border-red-500/50 bg-red-500/10 p-4 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-white pr-2">{{ $task->title }}</h3>
                                </div>
                                <div class="flex justify-between items-center mt-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $color }}-500/30 text-{{ $color }}-300">{{ $priorityMap[$task->priority]['text'] }}</span>
                                    <img class="h-6 w-6 rounded-full" src="https://i.pravatar.cc/32?u={{ $task->user->name ?? 'unknown' }}" alt="{{ $task->user->name ?? 'Sem responsável' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <i class="fa-solid fa-folder-open text-6xl text-slate-600"></i>
                    <h3 class="mt-4 text-xl font-bold text-white">Nenhum projeto selecionado</h3>
                    <p class="text-slate-400">Crie ou selecione um projeto na barra lateral para começar.</p>
                </div>
            </div>
        @endif
    </main>
    
    <x-modal entangleProperty="showNewProjectModal">
        <x-slot name="title">Criar Novo Projeto</x-slot>
        <x-slot name="body">
            <form wire:submit.prevent="createProject" class="space-y-4">
                <div>
                    <input wire:model.defer="newProjectName" type="text" placeholder="Nome do Projeto" class="form-input text-lg" autofocus>
                    @error('newProjectName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <textarea wire:model.defer="newProjectDescription" placeholder="Descrição breve do projeto (opcional)" rows="3" class="form-input"></textarea>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
            <button type="submit" wire:click="createProject" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">
                <span wire:loading.remove wire:target="createProject">Criar Projeto</span>
                <span wire:loading wire:target="createProject">A Criar...</span>
            </button>
        </x-slot>
    </x-modal>

    <x-modal entangleProperty="showNewTaskModal">
        <x-slot name="title">Criar Nova Tarefa</x-slot>
        <x-slot name="body">
            <form wire:submit.prevent="createTask" class="space-y-4">
                <input wire:model.defer="newTaskTitle" type="text" placeholder="Título da Tarefa" class="form-input text-lg" autofocus>
                @error('newTaskTitle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                
                <textarea wire:model.defer="newTaskDescription" placeholder="Descrição da tarefa..." rows="3" class="form-input"></textarea>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-slate-400">Responsável</label>
                        <select wire:model.defer="newTaskUserId" class="form-input">
                            <option value="">Ninguém</option>
                            @foreach($projectMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Prioridade</label>
                        <select wire:model.defer="newTaskPriority" class="form-input">
                            <option value="low">Baixa</option>
                            <option value="normal">Normal</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-400">Data de Entrega</label>
                    <input wire:model.defer="newTaskDueDate" type="date" class="form-input">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs text-slate-400">Casos de Teste</label>
                        <button type="button" wire:click="addTest" class="text-violet-400 hover:text-violet-300 text-sm">
                            <i class="fa-solid fa-plus mr-1"></i>Adicionar Teste
                        </button>
                    </div>
                    <div class="space-y-2">
                        @forelse($newTaskTests as $index => $test)
                            <div class="flex items-center gap-2">
                                <input wire:model.defer="newTaskTests.{{ $index }}" type="text" 
                                       placeholder="Ex: Verificação de login" 
                                       class="form-input flex-1 text-sm">
                                <button type="button" wire:click="removeTest({{ $index }})" 
                                        class="text-red-400 hover:text-red-300">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">Nenhum teste adicionado</p>
                        @endforelse
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
            <button type="submit" wire:click="createTask" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">
                <span wire:loading.remove wire:target="createTask">Criar Tarefa</span>
                <span wire:loading wire:target="createTask">A Criar...</span>
            </button>
        </x-slot>
    </x-modal>

    @if($selectedTask)
    <x-modal entangleProperty="showTaskModal">
        <x-slot name="title">{{ $selectedTask->title }}</x-slot>
        <x-slot name="body">
            <div class="space-y-6">
                <div class="flex items-center gap-4 text-sm text-slate-400">
                    <span>Status: <span class="font-semibold text-white">{{ $columns[$selectedTask->status]['title'] ?? $selectedTask->status }}</span></span>
                    <span>Responsável: <span class="font-semibold text-white">{{ $selectedTask->user->name ?? 'N/A' }}</span></span>
                    <span>Prioridade: <span class="font-semibold text-white">{{ $priorityMap[$selectedTask->priority]['text'] ?? $selectedTask->priority }}</span></span>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-2">Descrição</h4>
                    <p class="text-slate-300">{{ $selectedTask->description ?: 'Nenhuma descrição fornecida.' }}</p>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-4">Casos de Teste</h4>
                    <div class="space-y-2">
                        @forelse ($selectedTask->checklistItems as $index => $item)
                            <div class="flex items-center gap-3 p-3 rounded-lg {{ $item->is_completed ? 'bg-green-500/10 border border-green-500/20' : 'bg-slate-800 border border-slate-700' }}">
                                <button wire:click="toggleChecklistItem({{ $selectedTask->id }}, {{ $item->id }})" 
                                        class="flex-shrink-0 w-5 h-5 rounded-full border-2 {{ $item->is_completed ? 'bg-green-500 border-green-500' : 'border-slate-500' }} flex items-center justify-center">
                                    @if($item->is_completed)
                                        <i class="fa-solid fa-check text-white text-xs"></i>
                                    @endif
                                </button>
                                <span class="text-slate-300 {{ $item->is_completed ? 'line-through text-slate-500' : '' }}">{{ $item->content }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Nenhum checklist para esta tarefa.</p>
                        @endforelse
                    </div>
                </div>

                @if($selectedTask->due_date)
                <div>
                    <h4 class="font-bold text-white mb-2">Data de Vencimento</h4>
                    <p class="text-slate-300">{{ \Carbon\Carbon::parse($selectedTask->due_date)->format('d/m/Y') }}</p>
                </div>
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            <button wire:click="closeAllModals" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Fechar</button>
        </x-slot>
    </x-modal>
    @endif

    <x-modal entangleProperty="showHistoryModal">
        <x-slot name="title">Histórico do Projeto</x-slot>
        <x-slot name="body">
            <div class="space-y-4">
                @if($activeProject)
                    @php
                        $histories = $activeProject->taskHistories()->with(['user', 'deletedBy'])->orderBy('deleted_at', 'desc')->get();
                    @endphp
                    @forelse($histories as $history)
                        @php
                            $actionColors = [
                                'completed' => 'bg-green-500/20 border-green-500/50',
                                'expired' => 'bg-red-500/20 border-red-500/50',
                                'deleted' => 'bg-red-500/20 border-red-500/50'
                            ];
                            $actionTexts = [
                                'completed' => 'Finalizada',
                                'expired' => 'Expirada',
                                'deleted' => 'Deletada'
                            ];
                        @endphp
                        <div class="p-4 rounded-lg border {{ $actionColors[$history->action] ?? 'bg-slate-800 border-slate-700' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-white">{{ $history->title }}</h4>
                                    <p class="text-sm text-slate-400 mt-1">{{ $history->description ?: 'Sem descrição' }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                        <span>Responsável: {{ $history->user->name ?? 'N/A' }}</span>
                                        <span>Prioridade: {{ $priorityMap[$history->priority]['text'] ?? $history->priority }}</span>
                                        @if($history->due_date)
                                            <span>Vencimento: {{ \Carbon\Carbon::parse($history->due_date)->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $history->action === 'completed' ? 'bg-green-500/30 text-green-300' : 'bg-red-500/30 text-red-300' }}">
                                        {{ $actionTexts[$history->action] }}
                                    </span>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ \Carbon\Carbon::parse($history->deleted_at)->format('d/m/Y H:i') }}
                                    </p>
                                    @if($history->deletedBy)
                                        <p class="text-xs text-slate-500">por {{ $history->deletedBy->name }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500 text-center py-8">Nenhum histórico encontrado.</p>
                    @endforelse
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            <button @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Fechar</button>
        </x-slot>
    </x-modal>

    <x-modal entangleProperty="showShareModal">
        <x-slot name="title">Compartilhar Projeto</x-slot>
        <x-slot name="body">
            <div class="space-y-4">
                <p class="text-slate-300">Compartilhe este link com outros usuários para adicioná-los ao projeto:</p>
                <div class="flex gap-2">
                    <input type="text" value="{{ $shareLink }}" readonly class="form-input flex-1" id="share-link-input">
                    <button onclick="window.copyShareLink()" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
                <p class="text-xs text-slate-500">Este link expira em 7 dias.</p>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button wire:click="closeShareModal" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Fechar</button>
        </x-slot>
    </x-modal>

    <x-modal entangleProperty="showDeleteProjectModal" type="danger">
        <x-slot name="title">Deletar Projeto</x-slot>
        <x-slot name="body">
            <p class="text-slate-300">Tem certeza que deseja deletar o projeto <strong>{{ $projectToDelete->name ?? '' }}</strong>?</p>
            <p class="text-sm text-red-400">Esta ação não pode ser desfeita. Todas as tarefas serão movidas para o histórico.</p>
        </x-slot>
        <x-slot name="footer">
            <x-danger-button wire:click="deleteProject">
                <span wire:loading.remove wire:target="deleteProject">Deletar</span>
                <span wire:loading wire:target="deleteProject">Deletando...</span>
            </x-danger-button>
            <button @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
        </x-slot>
    </x-modal>

    <x-modal entangleProperty="showDeleteTaskModal" type="danger">
        <x-slot name="title">Deletar Tarefa</x-slot>
        <x-slot name="body">
            <p class="text-slate-300">Tem certeza que deseja deletar a tarefa <strong>{{ $taskToDelete->title ?? '' }}</strong>?</p>
            <p class="text-sm text-red-400">Esta ação não pode ser desfeita. A tarefa será movida para o histórico.</p>
        </x-slot>
        <x-slot name="footer">
            <x-danger-button wire:click="deleteTask">
                <span wire:loading.remove wire:target="deleteTask">Deletar</span>
                <span wire:loading wire:target="deleteTask">Deletando...</span>
            </x-danger-button>
            <button @click="show = false" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
        </x-slot>
    </x-modal>
</div>

<script>
    function copyShareLink() {
        const input = document.getElementById('share-link-input');
        input.select();
        document.execCommand('copy');
        
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-check"></i>';
        button.classList.add('bg-green-600');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-600');
        }, 2000);
    }
</script>