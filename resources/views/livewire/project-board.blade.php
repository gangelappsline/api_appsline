<!-- resources/views/livewire/project-board.blade.php -->
<div>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">{{ $project->name }}</h2>
                <button 
                    x-data
                    @click="$dispatch('open-modal', { name: 'create-task' })"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md"
                >
                    Nueva Tarea
                </button>
            </div>
            
            <div 
                x-data="{
                    statusColumns: @js($statusColumns->toArray()),
                    init() {
                        this.$wire.on('taskUpdated', () => {
                            this.$wire.refreshBoard().then(() => {
                                this.statusColumns = this.$wire.statusColumns;
                            });
                        });
                    }
                }"
                class="grid grid-cols-1 md:grid-cols-4 gap-4"
                wire:ignore
            >
                <template x-for="statusColumn in statusColumns" :key="statusColumn.id">
                    <div class="bg-gray-100 rounded-lg p-4">
                        <h3 class="font-semibold text-lg mb-4" x-text="statusColumn.name"></h3>
                        
                        <div 
                            x-data="{
                                statusId: statusColumn.id,
                                init() {
                                    this.$nextTick(() => {
                                        new Sortable(this.$el, {
                                            group: 'tasks',
                                            animation: 150,
                                            ghostClass: 'bg-blue-50',
                                            onEnd: (evt) => {
                                                const orderedIds = this.statusColumns.map(column => {
                                                    return {
                                                        value: column.id,
                                                        items: Array.from(document.querySelectorAll(`[data-status-id='${column.id}'] .task-item`)).map(el => {
                                                            return { value: parseInt(el.getAttribute('data-task-id')) };
                                                        })
                                                    };
                                                });
                                                this.$wire.updateTaskOrder(orderedIds);
                                            }
                                        });
                                    });
                                }
                            }"
                            data-status-id="statusColumn.id"
                            class="space-y-3 min-h-[100px]"
                        >
                            <template x-for="task in statusColumn.tasks" :key="task.id">
                                <div 
                                    data-task-id="task.id"
                                    class="task-item bg-white p-3 rounded shadow cursor-move hover:shadow-md transition-shadow"
                                >
                                    <h4 class="font-medium" x-text="task.title"></h4>
                                    <p class="text-sm text-gray-600 mt-1" x-text="task.description"></p>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-xs text-gray-500" x-text="new Date(task.due_date).toLocaleDateString()"></span>
                                        <button 
                                            @click="$dispatch('open-modal', { 
                                                name: 'task-detail', 
                                                task: task 
                                            })"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                        >
                                            Ver
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal para crear tarea -->
    <x-modal name="create-task" maxWidth="lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Nueva Tarea</h3>
            
            <form wire:submit.prevent="createTask">
                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título</label>
                        <input 
                            type="text" 
                            id="title" 
                            wire:model="newTask.title" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        @error('newTask.title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea 
                            id="description" 
                            wire:model="newTask.description" 
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status_column_id" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select 
                                id="status_column_id" 
                                wire:model="newTask.status_column_id" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Seleccionar estado</option>
                                @foreach($statusColumns as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </select>
                            @error('newTask.status_column_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">Asignar a</label>
                            <select 
                                id="user_id" 
                                wire:model="newTask.user_id" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Seleccionar usuario</option>
                                @foreach($project->teamMembers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('newTask.user_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button 
                            type="button" 
                            @click="$dispatch('close-modal')"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Crear Tarea
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-modal>
</div>