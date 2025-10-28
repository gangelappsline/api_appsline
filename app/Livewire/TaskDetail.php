<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Task;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class TaskDetail extends Component
{
    use WithFileUploads;
    
    public $task;
    public $commentContent;
    public $attachment;
    
    protected $rules = [
        'commentContent' => 'required|string',
        'attachment' => 'nullable|file|max:10240',
    ];

    public function mount(ProjectTask $task)
    {
        $this->task = $task;
    }

    public function addComment()
    {
        $this->validate(['commentContent' => 'required|string']);
        
        ProjectTaskComment::create([
            'content' => $this->commentContent,
            'task_id' => $this->task->id,
            'user_id' => auth()->id(),
        ]);
        
        $this->reset('commentContent');
        $this->task->refresh();
    }

    public function uploadAttachment()
    {
        $this->validate(['attachment' => 'file|max:10240']);
        
        $path = $this->attachment->store('attachments');
        
        $this->task->attachments()->create([
            'name' => $this->attachment->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $this->attachment->getClientMimeType(),
            'size' => $this->attachment->getSize(),
            'user_id' => auth()->id(),
        ]);
        
        $this->reset('attachment');
        $this->task->refresh();
    }

    public function downloadAttachment($attachmentId)
    {
        $attachment = $this->task->attachments()->findOrFail($attachmentId);
        return Storage::download($attachment->path, $attachment->name);
    }

    public function deleteAttachment($attachmentId)
    {
        $attachment = $this->task->attachments()->findOrFail($attachmentId);
        Storage::delete($attachment->path);
        $attachment->delete();
        $this->task->refresh();
    }

    public function render()
    {
        return view('livewire.task-detail');
    }
}