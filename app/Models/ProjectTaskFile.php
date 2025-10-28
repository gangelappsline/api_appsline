<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTaskFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_task_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'description',
        'user_id',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
