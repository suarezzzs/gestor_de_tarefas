/**
 * Este script inicializa o componente Alpine.js 'kanban'
 * que controla a funcionalidade de arrastar e soltar (drag and drop).
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('kanban', () => ({
        /**
         * Chamado quando o usuário começa a arrastar um card.
         * Armazena o ID da tarefa que está a ser movida.
         */
        handleDragStart(event, taskId) {
            event.dataTransfer.setData('taskId', taskId);
        },

        /**
         * Chamado quando o usuário solta um card numa nova coluna.
         */
        handleDrop(event, newStatus) {
            const taskId = event.dataTransfer.getData('taskId');
            const columnEl = event.target.closest('.kanban-column');

            if (!columnEl) return;

            // Lógica para reordenar visualmente o card na coluna antes de enviar para o servidor.
            const draggedEl = document.querySelector(`[wire\\:key="task-${taskId}"]`);
            if (!draggedEl) return;

            const afterElement = this.getDragAfterElement(columnEl, event.clientY);
            if (afterElement == null) {
                columnEl.appendChild(draggedEl);
            } else {
                columnEl.insertBefore(draggedEl, afterElement);
            }

            // Obtém a nova ordem de todos os cards na coluna.
            const orderedIds = Array.from(columnEl.children).map(child => child.getAttribute('wire:key').replace('task-', ''));

            // Dispara um evento para o Livewire, enviando todos os dados necessários.
            window.Livewire.dispatch('task-dropped', {
                taskId: taskId,
                newStatus: newStatus,
                orderedIds: orderedIds
            });
        },

        /**
         * Helper para encontrar a posição correta para soltar o card na lista.
         */
        getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.kanban-card:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
    }));
});
