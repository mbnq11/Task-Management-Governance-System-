<?php

namespace App\Models;
//يمثل "المهمة" (Task)
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title', 
        'description', 
        'status', 
        'assigned_to', 
        'created_by', 
        'due_date',
        'priority', 
        'complexity', 
        'completion_percentage', 
        'sub_category'           
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team()
    {
        return $this->belongsToMany(User::class, 'task_team', 'task_id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'asc');
    }
}