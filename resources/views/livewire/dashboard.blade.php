<!-- resources/views/livewire/dashboard.blade.php -->
<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-6">Mis Proyectos</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($projects as $project)
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h3 class="text-xl font-semibold mb-2">{{ $project->name }}</h3>
                                <p class="text-gray-600 mb-4">{{ $project->description }}</p>
                                <a href="{{ route('projects.show', $project) }}" class="text-blue-600 hover:text-blue-800">
                                    Ver proyecto →
                                </a>
                            </div>
                        @endforeach
                        
                        <div class="border-2 border-dashed rounded-lg p-4 flex items-center justify-center hover:bg-gray-50 cursor-pointer">
                            <button wire:click="$emit('openModal', 'create-project')" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <span class="block mt-2">Nuevo Proyecto</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>