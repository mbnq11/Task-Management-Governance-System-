<?php

namespace App\Models;
//  يعرف من كل مستخدم 
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'department', 'supervisor_id', 'is_active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // علاقة المدير المباشر (يتبع لمن؟)
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // علاقة المرؤوسين (من يتبع لي؟)
    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    // المهام المسندة للمستخدم لإنجازها
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // المهام التي قام هذا المستخدم بإنشائها للآخرين
    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    // فحص الصلاحيات 
    public function isCiso() { return $this->role === 'ciso'; }
    public function isManager() { return $this->role === 'manager'; }
    public function isTeamLeader() { return $this->role === 'team_leader'; }
}