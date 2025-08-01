<x-slot name="footer">
    <x-danger-button wire:click="deleteTask" class="sm:ml-3">
        Apagar
    </x-danger-button>
    
    <x-secondary-button @click="show = false" class="sm:ml-3">
        Cancelar
    </x-secondary-button>
</x-slot>