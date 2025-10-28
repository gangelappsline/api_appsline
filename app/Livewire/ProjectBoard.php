<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Task;
use App\Models\StatusColumn;

class ProjectBoard extends Component
{
    public $project;
    public $statusColumns;
    public $newTask = [
        'title' => '',
        'description' => '',
        'status_column_id' => '',
        'user_id' => ''
    ];
    
    protected $listeners = ['taskUpdated' => 'refreshBoard'];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->refreshBoard();
    }

    public function refreshBoard()
    {
        $this->statusColumns = $this->project->statusColumns()->with(['tasks' => function($query) {
            $query->orderBy('order');
        }])->orderBy('order')->get();
    }

    public function updateTaskOrder($orderedIds)
    {
        foreach ($orderedIds as $status) {
            foreach ($status['items'] as $index => $task) {
                ProjectTask::find($task['value'])->update([
                    'status_column_id' => $status['value'],
                    'order' => $index
                ]);
            }
        }
        
        $this->emit('taskUpdated');
    }

    public function createTask()
    {
        $this->validate([
            'newTask.title' => 'required|string|max:255',
            'newTask.status_column_id' => 'required|exists:status_columns,id',
            'newTask.user_id' => 'required|exists:users,id',
        ]);
        
        $lastTask = ProjectTask::where('status_column_id', $this->newTask['status_column_id'])
            ->orderBy('order', 'desc')
            ->first();

        ProjectTask::create([
            'title' => $this->newTask['title'],
            'description' => $this->newTask['description'],
            'status_column_id' => $this->newTask['status_column_id'],
            'project_id' => $this->project->id,
            'user_id' => $this->newTask['user_id'],
            'order' => $lastTask ? $lastTask->order + 1 : 0,
        ]);
        
        $this->reset('newTask');
        $this->emit('taskUpdated');
        $this->dispatchBrowserEvent('notify', ['message' => 'Tarea creada con éxito']);
    }

    public function render()
    {
        return view('livewire.project-board');
    }
}