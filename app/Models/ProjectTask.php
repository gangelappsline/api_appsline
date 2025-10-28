<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $fillable = [
        'due_date',
        'status',
        'project_id',
        'title',
        'description',
        'assigned_to',
        'estimated_hours',
        'actual_hours',
        'priority',
        'category',
        'tags',
        'completed_at',
        'created_by'
    ];
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class,  "assigned_to", "id");
    }

    public function comments()
    {
        return $this->hasMany(ProjectTaskComment::class)->latest();
    }

    public function files()
    {
        return $this->hasMany(ProjectTaskFile::class)->latest();
    }
}
