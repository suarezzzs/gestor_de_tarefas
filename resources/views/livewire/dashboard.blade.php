<div class="flex h-[calc(100vh-65px)] bg-slate-900 text-slate-300" 
     x-data="{ 
        showTaskModal: @entangle('showTaskModal'), 
        showNewProjectModal: @entangle('showNewProjectModal') 
     }"
     @keydown.escape.window="showTaskModal = false; showNewProjectModal = false">
    
    <!-- Sidebar para Projetos -->
    <aside class="w-64 flex-shrink-0 p-4">
        <div class="auth-card h-full p-4 flex flex-col rounded-lg">
            <h3 class="text-lg font-bold text-white mb-4">Meus Projetos</h3>
            <nav class="flex-grow space-y-2">
                @forelse ($projects as $project)
                    <a href="#" wire:click.prevent="selectProject({{ $project->id }})" 
                       class="flex items-center gap-3 p-2 rounded-lg transition-colors {{ optional($activeProject)->id == $project->id ? 'bg-violet-500/30 text-white font-semibold' : 'hover:bg-slate-700/50' }}">
                        <i class="fa-solid fa-rocket w-5 text-center"></i>
                        <span>{{ $project->name }}</span>
                    </a>
                @empty
                    <p class="text-slate-500 text-sm px-2">Crie seu primeiro projeto!</p>
                @endforelse
            </nav>
            <button wire:click="openNewProjectModal" class="w-full mt-4 bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">
                <i class="fa-solid fa-plus mr-2"></i>Novo Projeto
            </button>
        </div>
    </aside>

    <!-- Área Principal do Kanban -->
    <main class="flex-grow p-4 flex flex-col">
        @if ($activeProject)
            <header class="mb-4 flex-shrink-0">
                <div class="auth-card p-4 flex justify-between items-center rounded-lg">
                    <div>
                        <h2 class="text-2xl font-bold text-white">{{ $activeProject->name }}</h2>
                        <p class="text-sm text-slate-400">{{ $activeProject->description ?? 'Organize as tarefas de desenvolvimento.' }}</p>
                    </div>
                    {{-- Botões de Ação (Convidar, etc.) virão aqui no futuro --}}
                </div>
            </header>

            <div class="flex-grow grid grid-cols-1 md:grid-cols-4 gap-4 overflow-y-auto" x-data="kanban()">
                @php
                    $columns = ['backlog' => 'Backlog', 'doing' => 'Em Andamento', 'review' => 'Revisão', 'done' => 'Concluído'];
                    $columnColors = ['backlog' => 'slate-600', 'doing' => 'blue-500', 'review' => 'yellow-500', 'done' => 'green-500'];
                @endphp

                @foreach ($columns as $status => $title)
                    <div class="auth-card p-3 rounded-lg flex flex-col bg-slate-900/70">
                        <h3 class="font-bold text-white p-2 border-b-2 border-{{ $columnColors[$status] }} mb-3">
                            {{ $title }} ({{ $tasks->where('status', $status)->count() }})
                        </h3>
                        <div class="kanban-column flex-grow space-y-3 p-1" data-status="{{ $status }}" x-on:drop.prevent="handleDrop($event, '{{ $status }}')" x-on:dragover.prevent>
                            @foreach ($tasks->where('status', $status) as $task)
                                <div class="kanban-card border-l-4 border-{{ $priorityMap[$task->priority]['color'] ?? 'gray-500' }}" 
                                     draggable="true" 
                                     x-on:dragstart="handleDragStart($event, {{ $task->id }})"
                                     wire:click="openTaskModal({{ $task->id }})"
                                     wire:key="task-{{ $task->id }}">
                                    <h4 class="font-bold text-white">{{ $task->title }}</h4>
                                    <div class="flex justify-between items-center mt-3">
                                        <span class="text-xs text-slate-500">{{ $task->due_date ? 'Entrega: ' . \Carbon\Carbon::parse($task->due_date)->format('d/m') : '' }}</span>
                                        <img class="h-6 w-6 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($task->user->name ?? '?') }}&background=random" alt="{{ $task->user->name ?? 'Sem responsável' }}" title="{{ $task->user->name ?? 'Sem responsável' }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
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

    <!-- Modal: Criar Novo Projeto -->
    <div x-show="showNewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none;">
        <div @click="showNewProjectModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div @click.outside="showNewProjectModal = false" class="auth-card w-full max-w-lg rounded-xl p-8 z-10">
            <h3 class="text-2xl font-bold text-white mb-6">Criar Novo Projeto</h3>
            <form wire:submit.prevent="createProject" class="space-y-4">
                <div>
                    <input wire:model.defer="newProjectName" type="text" placeholder="Nome do Projeto" class="form-input text-lg" autofocus>
                    @error('newProjectName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <textarea wire:model.defer="newProjectDescription" placeholder="Descrição breve do projeto (opcional)" rows="3" class="form-input"></textarea>
                
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" @click="showNewProjectModal = false" class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded-lg">
                        <span wire:loading.remove wire:target="createProject">Criar Projeto</span>
                        <span wire:loading wire:target="createProject">Criando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Detalhes da Tarefa (Slide-over) -->
    <div x-show="showTaskModal" @keydown.escape.window="$wire.closeAllModals()" class="fixed inset-0 overflow-hidden z-50" style="display: none;">
        <div x-show="showTaskModal" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="$wire.closeAllModals()"></div>
        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <div x-show="showTaskModal" @click.outside="$wire.closeAllModals()" x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="w-screen max-w-md">
                <div class="h-full flex flex-col bg-slate-900 shadow-xl overflow-y-scroll auth-card border-l border-slate-700">
                    @if ($selectedTask)
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <h2 class="text-2xl font-bold text-white">{{ $selectedTask->title }}</h2>
                                <button @click="$wire.closeAllModals()" class="text-slate-400 hover:text-white">&times;</button>
                            </div>
                            <div class="mt-2 flex items-center gap-4">
                                <span class="text-sm px-2 py-1 rounded-full bg-{{ $priorityMap[$selectedTask->priority]['color'] }}/20 text-{{ $priorityMap[$selectedTask->priority]['color'] }}">
                                    Prioridade: {{ $priorityMap[$selectedTask->priority]['text'] }}
                                </span>
                                @if($selectedTask->due_date)
                                <span class="text-sm text-slate-400"><i class="fa-regular fa-calendar mr-1"></i> {{ \Carbon\Carbon::parse($selectedTask->due_date)->format('d M, Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="border-t border-slate-700 p-6 space-y-6">
                            <div>
                                <h4 class="font-bold text-white mb-2">Descrição</h4>
                                <p class="text-slate-300 prose prose-invert max-w-none">{{ $selectedTask->description ?: 'Nenhuma descrição fornecida.' }}</p>
                            </div>
                            <div>
                                <h4 class="font-bold text-white mb-2">Checklist</h4>
                                <div class="space-y-2">
                                @forelse ($selectedTask->checklistItems as $item)
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" class="form-checkbox" {{ $item->is_completed ? 'checked' : '' }}>
                                        <span class="{{ $item->is_completed ? 'line-through text-slate-500' : 'text-slate-300' }}">{{ $item->content }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Nenhum item no checklist.</p>
                                @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
