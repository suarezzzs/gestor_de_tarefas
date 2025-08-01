/**
 * Este script inicializa a funcionalidade de drag and drop e outras interações.
 */

// Funções para notificações
function toggleNotifications() {
    const panel = document.getElementById('notification-panel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        if(panel.style.display === 'none') {
            const dot = document.getElementById('notification-dot');
            if (dot) dot.style.display = 'none';
        }
    }
}

function showNotification(message) {
    const dot = document.getElementById('notification-dot');
    const notificationList = document.getElementById('notification-list');
    
    if (dot) dot.style.display = 'block';
    if (notificationList) {
        const newNotif = document.createElement('p');
        newNotif.innerHTML = `<i class="fa-solid fa-arrows-up-down-left-right text-sky-400 mr-2"></i> ${message}`;
        notificationList.appendChild(newNotif);
    }
}

// Função para copiar link de compartilhamento
function copyShareLink() {
    const input = document.getElementById('share-link-input');
    if (input) {
        input.select();
        document.execCommand('copy');
        
        // Mostrar feedback
        const button = event.target.closest('button');
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fa-solid fa-check"></i>';
            button.classList.add('bg-green-600');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('bg-green-600');
            }, 2000);
        }
    }
}

// Funções de drag and drop
function handleDragStart(event, taskId) {
    console.log('Drag start:', taskId);
    event.dataTransfer.setData('taskId', taskId);
}

function handleDrop(event, newStatus) {
    console.log('Drop event:', newStatus);
    const taskId = event.dataTransfer.getData('taskId');
    const columnEl = event.target.closest('.kanban-column');

    if (!columnEl) {
        console.log('No column element found');
        return;
    }

    // Lógica para reordenar visualmente o card na coluna antes de enviar para o servidor.
    const draggedEl = document.querySelector(`#task-${taskId}`);
    if (!draggedEl) {
        console.log('No dragged element found');
        return;
    }

    const afterElement = getDragAfterElement(columnEl, event.clientY);
    if (afterElement == null) {
        columnEl.appendChild(draggedEl);
    } else {
        columnEl.insertBefore(draggedEl, afterElement);
    }

    // Obtém a nova ordem de todos os cards na coluna.
    const orderedIds = Array.from(columnEl.children).map(child => child.id.replace('task-', ''));

    console.log('Dispatching task-dropped event:', { taskId, newStatus, orderedIds });

    // Dispara um evento para o Livewire, enviando todos os dados necessários.
    if (window.Livewire) {
        window.Livewire.dispatch('task-dropped', {
            taskId: taskId,
            newStatus: newStatus,
            orderedIds: orderedIds
        });
    }
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.task-card:not(.dragging)')];
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

// Adicionar listeners de drag and drop
function addDragAndDropListeners() {
    const cards = document.querySelectorAll('.task-card[draggable="true"]');
    const columns = document.querySelectorAll('.kanban-column');
    let draggedCard = null;

    cards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            draggedCard = card;
            setTimeout(() => card.classList.add('dragging'), 0);
        });
        card.addEventListener('dragend', () => {
            draggedCard.classList.remove('dragging');
            draggedCard = null;
        });
    });

    columns.forEach(column => {
        column.addEventListener('dragover', e => {
            e.preventDefault();
            column.classList.add('drag-over');
        });
        column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
        column.addEventListener('drop', e => {
            e.preventDefault();
            column.classList.remove('drag-over');
            if (draggedCard) {
                column.appendChild(draggedCard);
                const taskId = draggedCard.id.replace('task-', '');
                const newStatus = column.dataset.status;
                
                // Disparar evento para o Livewire
                if (window.Livewire) {
                    window.Livewire.dispatch('task-dropped', {
                        taskId: taskId,
                        newStatus: newStatus,
                        orderedIds: []
                    });
                }
                
                showNotification(`<b>Você</b> moveu a tarefa para <b>${newStatus}</b>. O admin foi notificado.`);
            }
        });
    });
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    addDragAndDropListeners();
});

// Re-inicializar quando o Livewire atualizar
document.addEventListener('livewire:updated', function() {
    addDragAndDropListeners();
});

// Tornar funções globais para uso no Alpine.js
window.handleDragStart = handleDragStart;
window.handleDrop = handleDrop;
window.copyShareLink = copyShareLink;
window.toggleNotifications = toggleNotifications;
window.showNotification = showNotification;
