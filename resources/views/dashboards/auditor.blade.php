@extends('layouts.layout')

@section('content')
@vite(['resources/css/style.css', 'resources/js/app.js'])
{{-- مكتبة الشارت --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- 1. الترويسة --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 rounded-4 shadow-sm bg-white d-flex flex-wrap justify-content-between align-items-center" style="border-right: 5px solid #392015;">
            <div>
                <h2 class="fw-bold mb-1" style="color: #392015;">
                    <i class="bi bi-shield-check me-2"></i>
                    الرقابة والتدقيق والتحليل
                </h2>
                <p class="mb-0 opacity-75">متابعتك تضمن الجودة</p>
            </div>

            <div class="mt-3 mt-md-0 d-flex gap-2">
                {{-- أزرار التصدير --}}
                <a href="{{ route('reports.export.excel', request()->all()) }}"
                    class="btn fw-bold shadow-sm px-4 rounded-pill text-white"
                    style="background-color:#007B69; border:none;">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </a>
                <a href="{{ route('reports.print.general', request()->all()) }}" target="_blank"
                    class="btn fw-bold shadow-sm px-4 rounded-pill text-white"
                    style="background-color:#973D4B; border:none;">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </a>
            </div>
        </div>
    </div>
</div>

{{-- قسم الفلاتر --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body py-3">
                <form action="{{ url()->current() }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small text-muted fw-bold mb-1">الفترة الزمنية (الربع السنوي)</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar3" style="color:#392015"></i></span>
                            <select name="quarter" class="form-select border-start-0 shadow-none" onchange="this.form.submit()">
                                <option value="">-- عرض الكل --</option>
                                @foreach($availableQuarters ?? [] as $val => $label)
                                <option value="{{ $val }}" {{ request('quarter') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="small text-muted fw-bold mb-1">مستوى الأولوية</label>
                        <select name="priority" class="form-select shadow-sm">
                            <option value="">كافة الأولويات</option>
                            <option value="critical" {{ request('priority')=='critical'?'selected':'' }}>حرج</option>
                            <option value="high" {{ request('priority')=='high'?'selected':'' }}>عالية</option>
                            <option value="medium" {{ request('priority')=='medium'?'selected':'' }}>متوسطة</option>
                            <option value="low" {{ request('priority')=='low'?'selected':'' }}>منخفضة</option>
                        </select>

                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn text-white flex-grow-1 shadow-sm" style="background-color:#392015;">
                            <i class="bi bi-funnel me-1"></i> تصفية
                        </button>
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary shadow-sm">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 2. المؤشرات الحيوية (KPIs) --}}
<div class="row g-3 mb-4">

    {{-- إجمالي المهام --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 hover-scale" data-bs-toggle="collapse" data-bs-target="#activeTasksList">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold text-uppercase mb-1"> قيد التنفيذ</h6>
                    <h3 class="fw-bold mb-0" style="color:#392015;">{{ $totalTasks ?? 0 }}</h3>
                    <small class="text-muted"><span class="fw-bold text-dark">{{ ($openList ?? [])->count() }}</span> نشطة حالياً</small>
                </div>
                <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(57,32,21,0.1);">
                    <i class="bi bi-layers-fill fs-3" style="color: #392015;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- نسبة الإنجاز --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 hover-scale" data-bs-toggle="collapse" data-bs-target="#completedTasksList">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold text-uppercase mb-1"> المكتملة</h6>
                    <h3 class="fw-bold mb-0" style="color:#007B69;">{{ floor($complianceRate ?? 0) }}%</h3>
                    <div class="progress mt-2" style="height: 4px; width: 100px;">
                        <div class="progress-bar" style="width: {{ $complianceRate ?? 0 }}%; background-color:#007B69;"></div>
                    </div>
                </div>
                <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(0,123,105,0.1);">
                    <i class="bi bi-check-circle-fill fs-3" style="color: #007B69;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- المغلقة والمؤرشفة --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 hover-scale" data-bs-toggle="collapse" data-bs-target="#closedArchivedList">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold text-uppercase mb-1">مغلقة / مؤرشفة</h6>
                    <h3 class="fw-bold mb-0" style="color:#A37956;">{{ $closedCount ?? 0 }}</h3>
                    <small class="text-muted">أرشيف: {{ $archivedCount ?? 0 }}</small>
                </div>
                <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(163,121,86,0.1);">
                    <i class="bi bi-archive-fill fs-3" style="color: #A37956;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- المخاطر (المتأخرة) --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 hover-scale" data-bs-toggle="collapse" data-bs-target="#overdueListCollapse">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small fw-bold text-uppercase mb-1">متأخرة عن الموعد</h6>
                    <h3 class="fw-bold mb-0" style="color:#973D4B;">{{ $overdueTasks ?? 0 }}</h3>
                </div>
                <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background-color: rgba(151,61,75,0.1);">
                    <i class="bi bi-exclamation-triangle-fill fs-3" style="color: #973D4B;"></i>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- 3. القوائم المنسدلة للتفاصيل --}}
<div class="row mb-4">

    {{-- A. المهام النشطة --}}
    <div class="col-12 collapse" id="activeTasksList">
        <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #392015;">
            <div class="table-header d-flex justify-content-between align-items-center" style="background-color: #392015; color: white;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2"></i>تفاصيل المهام النشطة</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#activeTasksList"></button>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>المسؤول</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>إجراء المتابعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($openList ?? [] as $task)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('tasks.show', $task->id) }}" class="fw-bold task-link text-dark">
                                    {{ Str::limit($task->title, 60) }}
                                </a>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $task->assignee->name ?? 'غير محدد' }}</span></td>
                            <td>{{ $task->due_date }}</td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i> متابعة
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">لا توجد مهام نشطة حالياً.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- B. المهام المكتملة --}}
    <div class="col-12 collapse" id="completedTasksList">
        <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #007B69;">
            <div class="table-header d-flex justify-content-between align-items-center" style="background-color: #007B69; color: white;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-check-all me-2"></i>سجل المهام المكتملة</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#completedTasksList"></button>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>أنجزت بواسطة</th>
                            <th>تاريخ الإنجاز</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complianceList ?? [] as $task)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('tasks.show', $task->id) }}" class="fw-bold task-link text-dark">
                                    {{ Str::limit($task->title, 60) }}
                                </a>
                            </td>
                            <td><span class="badge bg-light" style="color:#007B69; border:1px solid #007B69;">{{ $task->assignee->name ?? 'غير محدد' }}</span></td>
                            <td>{{ optional($task->updated_at)->format('Y-m-d') }}</td>
                            <td><span class="badge bg-success">مكتمل</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">لا توجد بيانات.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- C. المهام المتأخرة --}}
    <div class="col-12 collapse" id="overdueListCollapse">
        <div class="card table-card border-danger shadow-sm mb-3" style="border-top:4px solid #973D4B;">
            <div class="table-header d-flex justify-content-between align-items-center" style="background-color: #973D4B; color: white;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>المهام المتأخرة (تستوجب المتابعة)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#overdueListCollapse"></button>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>المسؤول</th>
                            <th>مدة التأخير</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueList ?? [] as $task)
                        @php $days = $task->due_date ? floor(\Carbon\Carbon::parse($task->due_date)->diffInDays(now())) : 0; @endphp
                        <tr>
                            <td class="ps-4 fw-bold text-danger">{{ Str::limit($task->title, 60) }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $task->assignee->name ?? 'غير محدد' }}</span></td>
                            <td><span class="badge rounded-pill" style="background-color:#973D4B;">+{{ $days }} يوم</span></td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm rounded-pill px-3 fw-bold" style="background-color:#973D4B; color:#fff;">
                                    متابعة فورية
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted"> لا توجد مهام متأخرة.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- D. المغلقة والمؤرشفة --}}
    <div class="col-12 collapse" id="closedArchivedList">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card table-card shadow-sm h-100" style="border-top:4px solid #392015;">
                    <div class="card-header bg-white fw-bold py-3">آخر المهام المغلقة</div>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            @forelse($closedList ?? [] as $task)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('tasks.show', $task->id) }}" class="text-dark text-decoration-none">{{ Str::limit($task->title, 40) }}</a>
                                <span class="small text-muted">{{ optional($task->updated_at)->format('Y-m-d') }}</span>
                            </li>
                            @empty <li class="list-group-item text-center text-muted">لا توجد بيانات</li> @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card table-card shadow-sm h-100" style="border-top: 4px solid #A37956;">
                    <div class="card-header bg-white fw-bold py-3">الأرشيف</div>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            @forelse($archivedList ?? [] as $task)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('tasks.show', $task->id) }}" class="text-dark text-decoration-none">{{ Str::limit($task->title, 40) }}</a>
                                <span class="badge bg-secondary">{{ optional($task->updated_at)->format('Y-m-d') }}</span>
                            </li>
                            @empty <li class="list-group-item text-center text-muted">لا توجد بيانات</li> @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. آداء الموظفين وتتبع المهام (الجزء الرئيسي) --}}
<div class="row g-4">

    {{-- جدول أداء الفريق + الرسم البياني --}}
    <div class="col-lg-8">
        {{-- الجدول --}}
        <div class="card table-card mb-4 shadow-sm" style="border-top:4px solid #007B69;">
            <div class="table-header d-flex justify-content-between align-items-center bg-white border-bottom">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-person-lines-fill me-2" style="color:#007B69;"></i>تقرير أداء الفريق</h6>
                <span class="badge rounded-pill px-3 py-2" style="background-color:rgba(0,123,105,0.1); color:#007B69;">نقر للمتابعة</span>
            </div>

            <div class="card-body p-0 table-responsive scrollable-table-container" style="max-height: 500px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top" style="z-index: 2;">
                        <tr>
                            <th class="ps-3" style="min-width: 140px;">الموظف</th>
                            <th class="text-center">تقرير</th>
                            <th class="text-center bg-light">الكلي</th>
                            <th class="text-center text-success">منجز</th>
                            <th class="text-center text-primary">جاري</th>
                            <th class="text-center text-danger">معاد</th>
                            <th style="width: 25%;">الإنجاز</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeePerformance ?? [] as $name => $stats)
                        @php
                        $total = (int)($stats['total'] ?? 0);
                        $done = (int)($stats['done'] ?? 0);
                        $prog = (int)($stats['in_progress'] ?? 0);
                        $nd = (int)($stats['not_done'] ?? 0);
                        $pct = (float)($stats['completion_percent'] ?? 0);
                        @endphp

                        <tr>
                            <td class="ps-3 fw-bold small py-3">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2 border" style="width:32px; height:32px; color:#392015;">
                                        {{ substr($name, 0, 1) }}
                                    </div>
                                    {{ $name }}
                                </div>
                            </td>
                            <td class="text-center py-3">
                                <a href="{{ route('reports.print.employee', ['id' => $stats['id']] + request()->all()) }}"
                                    target="_blank"
                                    class="btn btn-sm btn-light text-danger border-0" title="طباعة تقرير">
                                    <i class="bi bi-file-pdf-fill"></i>
                                </a>
                            </td>
                            <td class="text-center fw-bold bg-light py-3">{{ $total }}</td>
                            <td class="text-center text-success fw-bold py-3">{{ $done }}</td>
                            <td class="text-center text-primary fw-bold py-3">{{ $prog }}</td>
                            <td class="text-center text-danger fw-bold py-3">{{ $nd }}</td>

                            <td class="py-3">
                                <div class="d-flex align-items-center">
                                    <span class="small me-2 fw-bold" style="min-width: 35px;">{{ round($pct) }}%</span>
                                    <div class="progress flex-grow-1 shadow-sm" style="height: 6px; border-radius: 3px;">
                                        <div class="progress-bar"
                                            style="width: {{ $pct }}%; background-color: {{ $pct >= 80 ? '#007B69' : ($pct >= 50 ? '#E3A778' : '#973D4B') }};">
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">لا توجد بيانات للموظفين في هذه الفترة.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- الرسم البياني --}}
        <div class="card table-card mb-4 shadow-sm" style="border-top:4px solid #392015;">
            <div class="table-header d-flex justify-content-between align-items-center bg-white px-3 py-2">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-graph-up-arrow me-2" style="color:#392015;"></i>
                    تحليل الأداء
                </h6>
            </div>

            <div class="card-body p-3">
                <div style="height: 260px;">
                    <canvas id="trendChart"
                        data-labels="{{ json_encode($chartLabels ?? []) }}"
                        data-created="{{ json_encode($chartCreatedData ?? []) }}"
                        data-completed="{{ json_encode($chartCompletedData ?? []) }}">
                    </canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- تتبع المهام (Audit Log) --}}
    <div class="col-lg-4">
        <div class="card table-card shadow-sm mb-4 h-100" style="border-top:4px solid #392015;">
            <div class="table-header text-white" style="background-color:#392015;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-activity me-2"></i>سجل تتبع المهام </h6>
            </div>

            <div class="p-3 bg-white border-bottom">
                <form method="GET" action="{{ url()->current() }}">
                    @foreach(request()->except(['audit_search', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach

                    <div class="input-group shadow-sm">
                        <input type="text"
                            name="audit_search"
                            class="form-control border-end-0"
                            placeholder="بحث ..."
                            value="{{ request('audit_search') }}"
                            style="background-color: #fcfcfc; border-color: #e0e0e0;">

                        <button class="btn" type="submit" style="background-color: #ffffff; color: white; border: 1px solid #e9e9e9;">
                            <i class="bi bi-search"></i>
                        </button>

                        @if(request('audit_search'))
                        <a href="{{ url()->current() }}?{{ http_build_query(request()->except('audit_search')) }}"
                            class="btn btn-light border border-start-0"
                            style="border-color: #e0e0e0; color: #dc3545;"
                            title="إلغاء البحث">
                            <i class="bi bi-x-lg"></i>
                        </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="card-body p-0 scrollable-table-container" style="background-color: #fff; max-height: 850px; overflow-y: auto;">
                @forelse($auditLogs ?? [] as $log)
                <div class="p-3 border-bottom position-relative task-audit-item" style="transition: background 0.2s;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-light text-dark border rounded-pill">#{{ $log->task_id }}</span>
                        <span class="text-muted small" style="font-size: 0.7rem;">
                            <i class="bi bi-clock me-1"></i>{{ optional($log->created_at)->diffForHumans() }}
                        </span>
                    </div>

                    <div class="mb-2">
                        {{-- الرابط الرئيسي للمتابعة --}}
                        <a href="{{ route('tasks.show', $log->task_id) }}" class="fw-bold text-dark text-decoration-none stretched-link" title="انقر للمتابعة">
                            {{ Str::limit($log->task->title ?? 'مهمة محذوفة', 55) }}
                        </a>
                    </div>

                    <div class="d-flex align-items-center small p-2 rounded" style="background-color: #f8f9fa;">
                        <i class="bi bi-person-circle me-2" style="color:#A37956;"></i>
                        <span class="text-muted me-1">لدى:</span>
                        <span class="fw-bold text-dark">
                            {{ $log->task->assignee->name ?? 'غير محدد' }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-25">
                        <i class="bi bi-search" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-muted">لا توجد سجلات مطابقة</h6>
                    @if(request('audit_search'))
                    <a href="{{ url()->current() }}?{{ http_build_query(request()->except('audit_search')) }}" class="btn btn-sm btn-link text-dark">إلغاء البحث</a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

@endsection