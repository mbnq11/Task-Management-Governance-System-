@extends('layouts.layout')

@section('content')
@vite(['resources/css/style.css', 'resources/js/app.js'])
{{-- مكتبة الشارت --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
// 1. المهام النشطة (Active)
$deptActive = \App\Models\Task::whereHas('assignee', function ($query) {
$query->where('department', Auth::user()->department);
})
->whereNotIn('status', ['completed', 'closed', 'archived'])
->orderBy('due_date', 'asc')
->with('assignee')
->get();

// 2. المهام المكتملة (Completed)
$deptCompleted = \App\Models\Task::whereHas('assignee', function ($query) {
$query->where('department', Auth::user()->department);
})
->whereIn('status', ['completed', 'closed'])
->latest('updated_at')
->take(20)
->with('assignee')
->get();

// 3. المهام المتأخرة (Overdue)
$deptOverdue = \App\Models\Task::whereHas('assignee', function ($query) {
$query->where('department', Auth::user()->department);
})
->where('status', '!=', 'completed')
->where('due_date', '<', now()->format('Y-m-d'))
    ->with('assignee')
    ->get();

    // 4. المهام المعادة للإدارة (Returned)
    $deptReturned = \App\Models\Task::whereHas('assignee', function ($query) {
    $query->where('department', Auth::user()->department);
    })
    ->where('status', 'returned')
    ->with('assignee')
    ->get();
    @endphp
@vite(['resources/css/style.css', 'resources/js/app.js'])

    {{-- 1. الترويسة --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="p-4 rounded-4 shadow-sm dashboard-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-building me-2" style="color:#fff !important;"></i>
                        إدارة {{ Auth::user()->department }}
                    </h2>
                    <p class="mb-0 opacity-75">قيادتك تصنع الفرق</p>
                </div>

                <div class="mt-3 mt-md-0">
                    <a href="{{ route('tasks.create') }}" class="btn fw-bold shadow-sm px-4 rounded-pill"
                        style="background-color:#D4AF91; border-color:#D4AF91; color:#392015;">
                        <i class="bi bi-plus-lg me-1" style="color:#392015 !important;"></i> إنشاء مهمة جديدة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(isset($deptReturned) && $deptReturned->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card alert-card-returned border-0 shadow-sm" style="border-right: 5px solid #973D4B !important;">
            
            <div class="card-body d-flex justify-content-between align-items-center p-4" 
                 style="background-color: #FFF5F5;">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width: 50px; height: 50px; background-color:#973D4B; box-shadow: 0 4px 6px rgba(151, 61, 75, 0.2);">
                        <i class="bi bi-arrow-return-left fs-3 text-white"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1" style="color:#973D4B;">تنبيه: توجد ({{ $deptReturned->count() }}) مهام معادة</h5>
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
                                <td class="ps-4 fw-bold text-dark">
                                    {{ Str::limit($task->title, 50) }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $task->assignee->name ?? 'غير محدد' }}</span>
                                </td>
                                <td class="text-muted small">
                                    <i class="bi bi-clock me-1"></i>{{ $task->updated_at->diffForHumans() }}
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
    {{-- 3. شريط المؤشرات (KPIs) --}}
    <div class="row g-4 mb-4">

        {{-- النشطة --}}
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#completedListCollapse">
                <i class="bi bi-check-circle-fill kpi-icon-bg" style="color: rgba(0,123,105,0.22) !important;"></i>
                <div class="kpi-label">المهام المكتملة</div>
                <div class="kpi-value" style="color:#007B69;">{{ $stats['completed'] ?? 0 }}</div>
                <div class="mt-2 text-muted small">
                    <i class="bi bi-chevron-down me-1" style="color:#007B69 !important;"></i> التفاصيل
                </div>
            </div>
        </div>

        {{-- المتأخرة --}}
        <div class="col-md-3">
            <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#overdueListCollapse">
                <i class="bi bi-exclamation-triangle-fill kpi-icon-bg" style="color: rgba(151,61,75,0.22) !important;"></i>
                <div class="kpi-label">المهام المتأخرة</div>
                <div class="kpi-value" style="color:#973D4B;">{{ $deptOverdue->count() }}</div>
                <div class="mt-2 text-muted small">
                    <i class="bi bi-chevron-down me-1" style="color:#973D4B !important;"></i> التفاصيل
                </div>
            </div>
        </div>

        {{-- نسبة الإنجاز --}}
        <div class="col-md-3">
            <div class="kpi-card p-4" style="cursor: default;">
                <i class="bi bi-pie-chart-fill kpi-icon-bg" style="color: rgba(227,167,120,0.22) !important;"></i>
                <div class="kpi-label">نسبة الإنجاز العام</div>
                <div class="kpi-value" style="color:#E3A778;">{{ floor($stats['performance_rate'] ?? 0) }}%</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar"
                        style="width: {{ $stats['performance_rate'] ?? 0 }}%; background-color:#E3A778;"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- 4. القوائم التفصيلية --}}
    <div class="row">

        {{-- أ. قائمة المهام النشطة --}}
        <div class="col-12 collapse" id="activeListCollapse">
            <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #392015;">
                <div class="table-header text-white d-flex justify-content-between align-items-center"
                    style="background: linear-gradient(135deg, #392015 0%, #A37956 100%);">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-lightning me-2" style="color:#fff !important;"></i>تفاصيل
                        المهام النشطة</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse"
                        data-bs-target="#activeListCollapse"></button>
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
                                        <span class="avatar-manager me-2"
                                            style="background-color:#392015; color:#fff;">
                                            {{ substr($task->assignee->name ?? 'U', 0, 1) }}
                                        </span>
                                        {{ $task->assignee->name ?? 'غير محدد' }}
                                    </div>
                                </td>
                                <td>
                                    @if ($task->status == 'returned')
                                    <span class="badge" style="background-color:#973D4B; color:#fff;">معادة</span>
                                    @elseif($task->status == 'in_progress')
                                    <span class="badge" style="background-color:#D4AF91; color:#392015;">جارية</span>
                                    @else
                                    <span class="badge bg-secondary">{{ $task->status }}</span>
                                    @endif
                                </td>
                                <td>{{ $task->due_date }}</td>
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
                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm rounded-pill"
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

        {{-- ب. قائمة المهام المكتملة --}}
        <div class="col-12 collapse" id="completedListCollapse">
            <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #007B69;">
                <div class="table-header text-white d-flex justify-content-between align-items-center"
                    style="background-color:#007B69;">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-check-all me-2" style="color:#fff !important;"></i>أرشيف
                        المهام المكتملة</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse"
                        data-bs-target="#completedListCollapse"></button>
                </div>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-4">المهمة</th>
                                <th>أنجزت بواسطة</th>
                                <th>تاريخ الإنجاز</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deptCompleted as $task)
                            <tr>
                                <td class="ps-4 fw-bold">{{ Str::limit($task->title, 40) }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar-manager me-2"
                                            style="background-color:#007B69; color:#fff;">
                                            {{ substr($task->assignee->name ?? 'U', 0, 1) }}
                                        </span>
                                        {{ $task->assignee->name ?? 'غير محدد' }}
                                    </div>
                                </td>
                                <td>{{ $task->updated_at->format('Y-m-d') }}</td>
                                <td>
                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm rounded-pill"
                                        style="border:1px solid #007B69; color:#007B69;">
                                        عرض
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">لم يتم إكمال أي مهام مؤخراً.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ج. قائمة المهام المتأخرة --}}
        <div class="col-12 collapse" id="overdueListCollapse">
            <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #973D4B;">
                <div class="table-header text-white d-flex justify-content-between align-items-center"
                    style="background-color:#973D4B;">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-alarm-fill me-2" style="color:#fff !important;"></i>قائمة
                        المهام المتأخرة</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse"
                        data-bs-target="#overdueListCollapse"></button>
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
                            @php $days = floor(\Carbon\Carbon::parse($task->due_date)->diffInDays(now())); @endphp
                            <tr>
                                <td class="ps-4 fw-bold">{{ Str::limit($task->title, 40) }}</td>
                                <td>{{ $task->assignee->name ?? 'غير محدد' }}</td>
                                <td style="color:#973D4B;">{{ $task->due_date }}</td>
                                <td><span class="badge rounded-pill"
                                        style="background-color:#973D4B; color:#fff;">+{{ $days }} يوم</span>
                                </td>
                                <td>
                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm rounded-pill"
                                        style="border:1px solid #973D4B; color:#973D4B;">
                                        متابعة
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">لا يوجد تأخير.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


    <div class="tasks-scroll-area shadow-sm rounded-4">
        @include('dashboards.partials.my_tasks')
    </div>
    </div>

    </div>

    @endsection