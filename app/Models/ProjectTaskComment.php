<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_task_id',
        'comment',
        'created_by',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
