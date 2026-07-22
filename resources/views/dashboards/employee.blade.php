@extends('layouts.layout')

@php
use Illuminate\Support\Str;
use Carbon\Carbon;

$uid = Auth::id();

/**
 * كل المهام المرئية للموظف:
 * 1) المسندة له مباشرة
 * 2) أو هو ضمن فريق المهمة
 */
$visibleForEmployee = function ($q) use ($uid) {
    $q->where('assigned_to', $uid)
      ->orWhereHas('team', function ($t) use ($uid) {
          $t->where('users.id', $uid);
      });
};

/**
 * تعريف الحالات (مهم جداً لتطابق KPI مع الجداول)
 */
$openStatuses = ['pending','in_progress','submitted','reviewed','endorsed','returned'];
$doneStatuses = ['completed','closed','archived'];

// Active (قيد التنفيذ)
$deptActive = \App\Models\Task::where($visibleForEmployee)
    ->whereIn('status', $openStatuses)
    ->orderByRaw('due_date IS NULL, due_date ASC')
    ->with(['assignee','team'])
    ->get();

// Completed (مكتملة/مغلقة/مؤرشفة)
$deptCompleted = \App\Models\Task::where($visibleForEmployee)
    ->whereIn('status', $doneStatuses)
    ->latest('updated_at')
    ->take(50)
    ->with(['assignee','team'])
    ->get();

// Overdue (متأخرة) - من النشطة فقط وبشرط due_date موجود
$deptOverdue = \App\Models\Task::where($visibleForEmployee)
    ->whereIn('status', $openStatuses)
    ->whereNotNull('due_date')
    ->whereDate('due_date', '<', now())
    ->with(['assignee','team'])
    ->get();

// Returned (معادة) - (ضمن النشطة أصلاً لكن خليها منفصلة للتنبيه)
$deptReturned = \App\Models\Task::where($visibleForEmployee)
    ->where('status', 'returned')
    ->with(['assignee','team'])
    ->get();

/**
 * Stats (KPI)
 */
$completedCount = $deptCompleted->count();
$activeCount    = $deptActive->count();
$total          = $completedCount + $activeCount;

$stats = [
    'completed' => $completedCount,
    'performance_rate' => $total > 0 ? ($completedCount / $total) * 100 : 0,
];
@endphp

@vite(['resources/css/style.css', 'resources/js/app.js'])

@section('content')
{{-- مكتبة الشارت --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- 1. الترويسة --}}
<div class="page">
    <div class="col-12">
        <div class="p-4 rounded-4 shadow-sm dashboard-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-person-badge-fill me-2" style="color:#fff !important;"></i>
                    {{ Auth::user()->name }}
                </h2>
                <p class="mb-0 opacity-75">جهدك يصنع الإنجاز</p>
            </div>
        </div>
    </div>
</div>

{{-- 2. تنبيه المهام المعادة --}}
@if($deptReturned->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card alert-card-returned border-0 shadow-sm" style="border-right: 5px solid #973D4B !important;">
            <div class="card-body d-flex justify-content-between align-items-center p-4" style="background-color: #FFF5F5;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width: 50px; height: 50px; background-color:#973D4B; box-shadow: 0 4px 6px rgba(151, 61, 75, 0.2);">
                        <i class="bi bi-arrow-return-left fs-3 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1" style="color:#973D4B;">
                            تنبيه: توجد ({{ $deptReturned->count() }}) مهام معادة
                        </h5>
                    </div>
                </div>

                <button class="btn fw-bold rounded-pill px-4"
                    style="border:1px solid #973D4B; color:#973D4B; background: transparent;"
                    type="button" data-bs-toggle="collapse" data-bs-target="#deptReturnedList">
                    عرض القائمة <i class="bi bi-chevron-down ms-1"></i>
                </button>
            </div>

            <div class="collapse" id="deptReturnedList">
                <div class="table-responsive border-top" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th class="ps-4" style="color:#973D4B;">المهمة</th>
                                <th style="color:#973D4B;">الموظف المسؤول</th>
                                <th style="color:#973D4B;">تاريخ الإعادة</th>
                                <th style="color:#973D4B;">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deptReturned as $task)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ Str::limit($task->title, 50) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $task->assignee->name ?? 'غير محدد' }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <i class="bi bi-clock me-1"></i>{{ $task->updated_at?->diffForHumans() }}
                                </td>
                                <td>
                                    <a href="{{ route('tasks.show', $task->id) }}"
                                        class="btn btn-sm text-white rounded-pill px-3 shadow-sm"
                                        style="background-color:#973D4B; border:none;">
                                        متابعة وتصحيح
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endif

{{-- 3. المؤشرات (KPIs) --}}
<div class="row g-4 mb-4">

    {{-- قيد التنفيذ --}}
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#activeListCollapse">
            <i class="bi bi-lightning-charge-fill kpi-icon-bg" style="color: rgba(57,32,21,0.18) !important;"></i>
            <div class="kpi-label">مهام قيد التنفيذ</div>
            <div class="kpi-value" style="color:#392015;">{{ $deptActive->count() }}</div>
            <div class="mt-2 text-muted small">
                <i class="bi bi-chevron-down me-1" style="color:#392015 !important;"></i> التفاصيل
            </div>
        </div>
    </div>

    {{-- المكتملة --}}
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#completedListCollapse">
            <i class="bi bi-check-circle-fill kpi-icon-bg" style="color: rgba(0,123,105,0.22) !important;"></i>
            <div class="kpi-label">المهام المكتملة</div>
            <div class="kpi-value" style="color:#007B69;">{{ $stats['completed'] }}</div>
            <div class="mt-2 text-muted small">
                <i class="bi bi-chevron-down me-1" style="color:#007B69 !important;"></i> التفاصيل
            </div>
        </div>
    </div>

    {{-- المتأخرة --}}
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#overdueListCollapse">
            <i class="bi bi-exclamation-triangle-fill kpi-icon-bg" style="color: rgba(151,61,75,0.22) !important;"></i>
            <div class="kpi-label">مهام متأخرة</div>
            <div class="kpi-value" style="color:#973D4B;">{{ $deptOverdue->count() }}</div>
            <div class="mt-2 text-muted small">
                <i class="bi bi-chevron-down me-1" style="color:#973D4B !important;"></i> التفاصيل
            </div>
        </div>
    </div>

    {{-- نسبة الإنجاز --}}
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi-card p-4" style="cursor: default;">
            <i class="bi bi-pie-chart-fill kpi-icon-bg" style="color: rgba(227,167,120,0.22) !important;"></i>
            <div class="kpi-label">نسبة الإنجاز العام</div>
            <div class="kpi-value" style="color:#E3A778;">{{ (int) floor($stats['performance_rate']) }}%</div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar" style="width: {{ $stats['performance_rate'] }}%; background-color:#E3A778;"></div>
            </div>
        </div>
    </div>

</div>

{{-- 4. القوائم التفصيلية --}}

{{-- أ. النشطة --}}
<div class="row g-3 mb-4">
    <div class="col-12 collapse" id="activeListCollapse">
        <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #392015;">
            <div class="table-header text-white d-flex justify-content-between align-items-center"
                style="background: linear-gradient(135deg, #392015 0%, #A37956 100%);">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-lightning me-2" style="color:#fff !important;"></i> تفاصيل المهام النشطة
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#activeListCollapse"></button>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>الموظف المسؤول</th>
                            <th>الحالة</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الأولوية</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deptActive as $task)
                        <tr>
                            <td class="ps-4 fw-bold">{{ Str::limit($task->title, 40) }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar-employee me-2" style="background-color:#392015; color:#fff;">
                                        {{ substr($task->assignee->name ?? 'U', 0, 1) }}
                                    </span>
                                    {{ $task->assignee->name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>
                                @if($task->status == 'returned')
                                    <span class="badge" style="background-color:#973D4B; color:#fff;">معادة</span>
                                @elseif($task->status == 'in_progress')
                                    <span class="badge" style="background-color:#D4AF91; color:#392015;">جارية</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $task->status }}</span>
                                @endif
                            </td>
                            <td>{{ $task->due_date ?? '—' }}</td>
                            <td>
                                @if ($task->priority == 'critical')
                                    <span class="fw-bold" style="color:#dc3545;">حرجة</span>
                                @elseif ($task->priority == 'high')
                                    <span class="fw-bold" style="color:#973D4B;">عالية</span>
                                @elseif ($task->priority == 'medium')
                                    <span class="fw-bold" style="color:#E3A778;">متوسطة</span>
                                @else
                                    <span class="fw-bold" style="color:#007B69;">عادية</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}"
                                    class="btn btn-sm rounded-pill"
                                    style="border:1px solid #392015; color:#392015;">
                                    متابعة
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">لا توجد مهام نشطة حالياً.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ب. المكتملة --}}
    <div class="col-12 collapse" id="completedListCollapse">
        <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #007B69;">
            <div class="table-header text-white d-flex justify-content-between align-items-center" style="background-color:#007B69;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-check-all me-2" style="color:#fff !important;"></i>أرشيف المهام المكتملة</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#completedListCollapse"></button>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>أنجزت بواسطة</th>
                            <th>تاريخ الإنجاز</th>
                            <th>المدة المستغرقة</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deptCompleted as $task)
                        @php $daysTook = Carbon::parse($task->created_at)->diffInDays($task->updated_at); @endphp
                        <tr>
                            <td class="ps-4 fw-bold">{{ Str::limit($task->title, 40) }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar-employee me-2" style="background-color:#007B69; color:#fff;">
                                        {{ substr($task->assignee->name ?? 'U', 0, 1) }}
                                    </span>
                                    {{ $task->assignee->name ?? 'غير محدد' }}
                                </div>
                            </td>
                            <td>{{ $task->updated_at?->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge" style="background-color: rgba(0,123,105,0.12); color:#007B69; border:1px solid rgba(0,123,105,0.35);">
                                    {{ $daysTook == 0 ? 'أقل من يوم' : $daysTook . ' يوم' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}"
                                    class="btn btn-sm rounded-pill"
                                    style="border:1px solid #007B69; color:#007B69;">
                                    عرض
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">لم يتم إكمال أي مهام مؤخراً.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ج. المتأخرة --}}
    <div class="col-12 collapse" id="overdueListCollapse">
        <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #973D4B;">
            <div class="table-header text-white d-flex justify-content-between align-items-center" style="background-color:#973D4B;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-alarm-fill me-2" style="color:#fff !important;"></i>قائمة المهام المتأخرة</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#overdueListCollapse"></button>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>الموظف</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>مدة التأخير</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deptOverdue as $task)
                        @php $days = Carbon::parse($task->due_date)->diffInDays(now()); @endphp
                        <tr>
                            <td class="ps-4 fw-bold">{{ Str::limit($task->title, 40) }}</td>
                            <td>{{ $task->assignee->name ?? 'غير محدد' }}</td>
                            <td style="color:#973D4B;">{{ $task->due_date }}</td>
                            <td><span class="badge rounded-pill" style="background-color:#973D4B; color:#fff;">+{{ $days }} يوم</span></td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}"
                                    class="btn btn-sm rounded-pill"
                                    style="border:1px solid #973D4B; color:#973D4B;">
                                    متابعة
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">لا توجد مهام متأخرة.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- قائمة مهامي --}}
<div class="tasks-scroll-area shadow-sm rounded-4">
    @include('dashboards.partials.my_tasks')
</div>

@endsection
