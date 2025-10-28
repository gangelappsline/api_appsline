<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Project;

class Dashboard extends Component
{
    public $projects;

    public function mount()
    {
        $this->projects = Project::all();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}