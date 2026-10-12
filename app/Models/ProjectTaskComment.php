<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'comment',
        'user_id',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
