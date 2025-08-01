<x-modal entangleProperty="showNewTaskModal">

    <x-slot name="title">
        Nova Tarefa
    </x-slot>

    <x-slot name="body">
        
        <div class="space-y-4">
            <div>
                <label for="newTaskTitle" class="block text-sm font-medium text-gray-700">Título</label>
                <input wire:model.defer="newTaskTitle" type="text" id="newTaskTitle" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                @error('newTaskTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            
            <div>
                <label for="newTaskDescription" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea wire:model.defer="newTaskDescription" id="newTaskDescription" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        
        <button wire:click="createTask" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
            Criar Tarefa
        </button>
        
        <button type="button" @click="show = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Cancelar
        </button>
    </x-slot>

</x-modal>

