<?php
//صلاحية انشاء المهمام
namespace App\Support\Tasks;

use Illuminate\Support\Facades\Auth;

trait TaskAuthorization
{
    private function checkCreationPermissions()
    {
        if (in_array(Auth::user()->role, ['auditor', 'team_leader', 'employee'])) {
            abort(403, 'عذراً، لا تملك صلاحية إنشاء مهام جديدة.');
        }
    }

    private function checkAuditorRestriction()
    {
        if (Auth::user()->role === 'auditor') abort(403);
    }
}
