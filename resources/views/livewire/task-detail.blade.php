<!-- resources/views/livewire/task-detail.blade.php -->
<div>
    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-medium">{{ $task->title }}</h3>
            <p class="text-gray-600 mt-2">{{ $task->description }}</p>
            
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Estado</p>
                    <p class="font-medium">{{ $task->statusColumn->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Asignado a</p>
                    <p class="font-medium">{{ $task->user->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fecha límite</p>
                    <p class="font-medium">{{ $task->due_date ? $task->due_date->format('d/m/Y') : 'Sin fecha' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Creada</p>
                    <p class="font-medium">{{ $task->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
        
        <div class="border-t pt-4">
            <h4 class="font-medium mb-3">Comentarios</h4>
            
            <div class="space-y-4 mb-6">
                @foreach($task->comments as $comment)
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">{{ $comment->user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $comment->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if($comment->user_id == auth()->id())
                                <button 
                                    wire:click="deleteComment({{ $comment->id }})" 
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                        <p class="mt-2">{{ $comment->content }}</p>
                    </div>
                @endforeach
            </div>
            
            <form wire:submit.prevent="addComment" class="space-y-3">
                <textarea 
                    wire:model="commentContent" 
                    rows="3" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Escribe un comentario..."
                ></textarea>
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                >
                    Enviar comentario
                </button>
            </form>
        </div>
        
        <div class="border-t pt-4">
            <h4 class="font-medium mb-3">Archivos adjuntos</h4>
            
            <div class="space-y-2 mb-4">
                @foreach($task->attachments as $attachment)
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span>{{ $attachment->name }}</span>
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                wire:click="downloadAttachment({{ $attachment->id }})" 
                                class="text-blue-600 hover:text-blue-800"
                            >
                                Descargar
                            </button>
                            @if($attachment->user_id == auth()->id())
                                <button 
                                    wire:click="deleteAttachment({{ $attachment->id }})" 
                                    class="text-red-500 hover:text-red-700"
                                >
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <form wire:submit.prevent="uploadAttachment" class="space-y-3">
                <input type="file" wire:model="attachment">
                @error('attachment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                >
                    Subir archivo
                </button>
            </form>
        </div>
    </div>
</div>