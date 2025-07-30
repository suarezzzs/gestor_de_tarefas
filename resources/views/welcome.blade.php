@extends('layouts.landing')

@section('content')

{{-- Seção Hero e Demonstração Animada --}}
<section id="home" class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden pt-24 pb-16">
    <div class="container mx-auto px-6 text-center z-10">
        
        {{-- Prova Social (Logos) --}}
        <div class="mb-12">
            <p class="text-sm text-slate-400 mb-4">USADO POR EQUIPES DE PONTA NA</p>
            <div class="flex justify-center items-center gap-x-8 md:gap-x-12 opacity-60 grayscale">
                <i class="fa-brands fa-amazon text-4xl" title="Amazon"></i>
                <i class="fa-brands fa-google text-4xl" title="Google"></i>
                <i class="fa-brands fa-microsoft text-4xl" title="Microsoft"></i>
                <i class="fa-brands fa-spotify text-4xl" title="Spotify"></i>
                <i class="fa-brands fa-paypal text-4xl" title="Paypal"></i>
            </div>
        </div>

        {{-- Título Principal --}}
        <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight mb-4 animate-fade-in-down">
            Menos Caos. <span class="text-violet-400">Mais Fluxo.</span>
        </h1>
        <p class="text-lg md:text-xl text-slate-300 mb-10 max-w-2xl mx-auto animate-fade-in-up">
            A ferramenta visual que a sua equipe de TI precisa para organizar tarefas, colaborar sem esforço e entregar projetos no prazo.
        </p>

        {{-- Demonstração Animada --}}
        <div class="demo-container max-w-4xl mx-auto animate-fade-in-up" style="animation-delay: 0.3s;">
            <div class="demo-header">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                    <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                </div>
                <span class="text-sm text-slate-400">Projeto Apollo - Kanban</span>
                <div class="w-16"></div>
            </div>
            <div class="demo-body">
                {{-- Colunas do Kanban --}}
                <div id="demo-col-backlog" class="demo-column">
                    <h4 class="demo-column-title border-gray-500">Backlog</h4>
                    <div id="animated-task" class="demo-card">
                        <p class="font-bold">Implementar autenticação OAuth 2.0</p>
                        <div class="flex items-center justify-between mt-2">
                            <span id="task-status-tag" class="px-2 py-0.5 text-xs rounded-full bg-red-500/30 text-red-300">Urgente</span>
                            <img src="https://i.pravatar.cc/24?u=carlos" class="w-6 h-6 rounded-full" alt="Avatar">
                        </div>
                    </div>
                </div>
                <div id="demo-col-doing" class="demo-column">
                    <h4 class="demo-column-title border-blue-500">Em Andamento</h4>
                </div>
                <div id="demo-col-done" class="demo-column">
                    <h4 class="demo-column-title border-green-500">Concluído</h4>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- Seção de Recursos --}}
<section id="features" class="py-20 md:py-24">
    <div class="container mx-auto px-6">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="feature-card">
                <div class="text-4xl text-violet-400 mb-4"><i class="fa-solid fa-columns"></i></div>
                <h3 class="text-xl font-bold text-white mb-2">Visão Clara</h3>
                <p>Organize tudo com quadros Kanban intuitivos. Saiba quem está a fazer o quê, a qualquer momento.</p>
            </div>
            <div class="feature-card" style="animation-delay: 0.2s;">
                <div class="text-4xl text-violet-400 mb-4"><i class="fa-solid fa-bolt"></i></div>
                <h3 class="text-xl font-bold text-white mb-2">Fluxo Rápido</h3>
                <p>Automatize tarefas repetitivas e crie regras para mover os cards, libertando a sua equipe para focar no que importa.</p>
            </div>
            <div class="feature-card" style="animation-delay: 0.4s;">
                <div class="text-4xl text-violet-400 mb-4"><i class="fa-solid fa-chart-pie"></i></div>
                <h3 class="text-xl font-bold text-white mb-2">Decisões Inteligentes</h3>
                <p>Use relatórios e dashboards para identificar gargalos e otimizar a performance da sua equipe com dados reais.</p>
            </div>
        </div>
    </div>
</section>

{{-- Adiciona o script de animação --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const task = document.getElementById('animated-task');
    const backlogCol = document.getElementById('demo-col-backlog');
    const doingCol = document.getElementById('demo-col-doing');
    const doneCol = document.getElementById('demo-col-done');
    const statusTag = document.getElementById('task-status-tag');

    if (!task || !backlogCol || !doingCol || !doneCol || !statusTag) return;

    const runAnimation = () => {
        // Reset para o início
        statusTag.textContent = 'Urgente';
        statusTag.className = 'px-2 py-0.5 text-xs rounded-full bg-red-500/30 text-red-300';
        backlogCol.appendChild(task);
        task.style.opacity = 1;

        // Animação Passo 1: Mover para "Em Andamento"
        setTimeout(() => {
            doingCol.appendChild(task);
            statusTag.textContent = 'Em Progresso';
            statusTag.className = 'px-2 py-0.5 text-xs rounded-full bg-blue-500/30 text-blue-300';
        }, 2500);

        // Animação Passo 2: Mover para "Concluído"
        setTimeout(() => {
            doneCol.appendChild(task);
            statusTag.textContent = 'Concluído';
            statusTag.className = 'px-2 py-0.5 text-xs rounded-full bg-green-500/30 text-green-300';
        }, 5000);
        
        // Animação Passo 3: Fade out e reiniciar
        setTimeout(() => {
            task.style.opacity = 0;
        }, 7000);
    };

    // Inicia a animação e a repete a cada 8 segundos
    runAnimation();
    setInterval(runAnimation, 8000);
});
</script>

@endsection