<?php
// ممهم علشان المدقق يقدر يطبع تقرير للمهمة من سجل المهمة
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;

class TaskExportController extends Controller
{

    public function printReport($id)
    {
        $task = Task::with(['creator', 'assignee', 'team'])->findOrFail($id);
        return view('tasks.print', compact('task'));
    }

    public function printAuditorTask($id)
    {
        if (Auth::user()->role !== 'auditor') abort(403);
        
        $task = Task::with(['creator', 'assignee', 'team'])->findOrFail($id);
        
        return view('tasks.print', compact('task'));
    }

}