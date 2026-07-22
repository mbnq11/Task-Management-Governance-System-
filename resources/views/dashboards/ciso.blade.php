@extends('layouts.layout')

@section('content')
@vite(['resources/css/style.css', 'resources/js/app.js'])

{{-- مكتبة الشارت خارجية --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- 1. الترويسة --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 rounded-4 shadow-sm dashboard-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-shield-lock-fill me-2" style="color:#fff !important;"></i>
                    المدير العام للأمن السيبراني
                </h2>
                <p class="mb-0 opacity-75">رؤيتك ترسم المستقبل</p>
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
{{-- 3. شريط المؤشرات  --}}
<div class="row g-4 mb-4">

    {{-- إجمالي المهام --}}
    <div class="col-md-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#activeTasksList">
            <i class="bi bi-grid-fill kpi-icon-bg" style="color: rgba(57,32,21,0.18) !important;"></i>
            <div class="kpi-label">مهام قيد التنفيذ</div>
            <div class="kpi-value" style="color:#392015;">{{ $openTasksCount ?? 0 }}</div>
            <div class="mt-2 text-muted small">
                <i class="bi bi-arrow-down-circle" style="color:#392015 !important;"></i>
                التفاصيل: <strong>{{ $openTasksCount ?? 0 }}</strong>
            </div>
        </div>
    </div>

    {{-- نسبة الإنجاز --}}
    <div class="col-md-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#completedTasksList">
            <i class="bi bi-pie-chart-fill kpi-icon-bg" style="color: rgba(0,123,105,0.22) !important;"></i>
            <div class="kpi-label"> المكتملة المهام</div>
            <div class="kpi-value" style="color:#007B69;">{{ floor($completionRate) }}%</div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar" style="width: {{ $completionRate }}%; background-color:#007B69;"></div>
            </div>
        </div>
    </div>

    {{-- المغلقة والمؤرشفة --}}
    <div class="col-md-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#closedArchivedList">
            <i class="bi bi-check-circle-fill kpi-icon-bg" style="color: rgba(163,121,86,0.22) !important;"></i>
            <div class="kpi-label">مغلقة / مؤرشفة</div>
            <div class="kpi-value" style="color:#A37956;">
                {{ $closedCount }} <span class="fs-6 text-muted">/ {{ $archivedCount }}</span>
            </div>
            <div class="mt-2 text-muted small"> تفاصيل المهام المغلقة والمؤرشفة</div>
        </div>
    </div>

    {{-- المخاطر --}}
    <div class="col-md-3">
        <div class="kpi-card p-4" data-bs-toggle="collapse" data-bs-target="#overdueListCollapse">
            <i class="bi bi-exclamation-triangle-fill kpi-icon-bg"
                style="color: rgba(151,61,75,0.22) !important;"></i>
            <div class="kpi-label"> المهام المتأخرة </div>
            <div class="kpi-value" style="color:#973D4B;">{{ $overdueTasks }}</div>
            <div class="mt-2 text-muted small">
                <span class="fw-bold" style="color:#973D4B;">{{ floor($overdueRate) }}%</span> متأخرة عن الموعد
            </div>
        </div>
    </div>

</div>

{{-- 4. القوائم المنسدلة للتفاصيل --}}
<div class="row mb-4">

    {{-- A. المهام النشطة --}}
    <div class="col-12 collapse" id="activeTasksList">
        <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #392015;">
            <div class="table-header text-white d-flex justify-content-between align-items-center"
                style="background: linear-gradient(135deg, #392015 0%, #A37956 100%);">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-lightning-charge me-2" style="color:#fff !important;"></i>
                    تفاصيل المهام النشطة
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse"
                    data-bs-target="#activeTasksList"></button>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    {{-- التعديل هنا: تثبيت الهيدر --}}
                    <thead class="table-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>المسؤول</th>
                            <th>الحالة</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($openList as $task)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ Str::limit($task->title, 40) }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $task->assignee->name ?? 'غير محدد' }}</span>
                            </td>
                            <td>
                                @if ($task->status == 'in_progress')
                                <span class="badge" style="background-color:#007B69; color:#fff;">جارية</span>
                                @else
                                <span class="badge" style="background-color:#E3A778; color:#392015;">جاري العمل عليها</span>
                                @endif
                            </td>
                            <td>{{ $task->due_date }}</td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm rounded-pill"
                                    style="border:1px solid #392015; color:#392015;">
                                    متابعة
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">لا توجد مهام نشطة حالياً.</td>
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
            <div class="table-header text-white d-flex justify-content-between align-items-center"
                style="background-color:#007B69;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-check-all me-2" style="color:#fff !important;"></i>آخر المهام المكتملة</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse"
                    data-bs-target="#completedTasksList"></button>
            </div>

            {{-- التعديل هنا: تحديد ارتفاع وعمل سكرول --}}
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    {{-- التعديل هنا: تثبيت الهيدر --}}
                    <thead class="table-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>أنجزت بواسطة</th>
                            <th>تاريخ الإنجاز</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complianceList as $task)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ Str::limit($task->title, 40) }}</td>
                            <td><span class="badge bg-light"
                                    style="color:#007B69; border:1px solid #007B69;">{{ $task->assignee->name ?? 'غير محدد' }}</span>
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
                            <td colspan="4" class="text-center py-3 text-muted">لا توجد مهام مكتملة حديثاً.
                            </td>
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
            <div class="table-header text-white d-flex justify-content-between align-items-center"
                style="background-color:#973D4B;">
                <h6 class="mb-0 fw-bold"><i class="bi bi-cone-striped me-2" style="color:#fff !important;"></i>تفاصيل المهام المتأخرة</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse"
                    data-bs-target="#overdueListCollapse"></button>
            </div>

            {{-- التعديل هنا: تحديد ارتفاع وعمل سكرول --}}
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    {{-- التعديل هنا: تثبيت الهيدر --}}
                    <thead class="table-light sticky-top" style="z-index: 1;">
                        <tr>
                            <th class="ps-4">المهمة</th>
                            <th>المسؤول</th>
                            <th>التأخير</th>
                            <th>إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueList as $task)
                        @php $days = floor(\Carbon\Carbon::parse($task->due_date)->diffInDays(now())); @endphp
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ Str::limit($task->title, 40) }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $task->assignee->name ?? 'غير محدد' }}</span>
                            </td>
                            <td><span class="badge" style="background-color:#973D4B; color:#fff;">+{{ $days }}
                                    يوم</span></td>
                            <td>
                                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm rounded-pill"
                                    style="border:1px solid #973D4B; color:#973D4B;">
                                    متابعة
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">لا توجد مهام متأخرة.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- D. المغلقة والمؤرشفة --}}
    <div class="col-12 collapse" id="closedArchivedList">
        <div class="row">
            {{-- العمود الأول: المغلقة --}}
            <div class="col-md-6">
                <div class="card table-card shadow-sm mb-3" style="border-top:4px solid #392015;">
                    <div class="table-header text-white" style="background-color:#392015;">
                        <h6 class="mb-0 fw-bold">آخر المهام المغلقة</h6>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            @forelse($closedList as $task)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><span class="fw-bold">{{ Str::limit($task->title, 30) }}</span></div>
                                <span class="badge"
                                    style="background-color:#D4AF91; color:#392015;">{{ $task->updated_at->format('Y-m-d') }}</span>
                            </li>
                            @empty
                            <li class="list-group-item text-muted text-center">لا يوجد بيانات</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- العمود الثاني: المؤرشفة --}}
            <div class="col-md-6">
                <div class="card table-card shadow-sm mb-3" style="border-top: 4px solid #973D4B;">
                    <div class="table-header text-white" style="background-color:#973D4B;">
                        <h6 class="mb-0 fw-bold">آخر المهام المؤرشفة</h6>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush">
                            @forelse($archivedList as $task)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><span class="fw-bold">{{ Str::limit($task->title, 30) }}</span></div>
                                <span class="badge bg-light text-dark border">{{ $task->updated_at->format('Y-m-d') }}</span>
                            </li>
                            @empty
                            <li class="list-group-item text-muted text-center">لا يوجد بيانات</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 5. العمود الرئيسي --}}
<div class="col-lg-12">

    {{-- بداية الصف لتقسيم الشاشة يمين ويسار --}}
    <div class="row">

        {{-- الجزء الأيمن: مراقب تدفق المهام --}}
        <div class="col-lg-7">
            <div class="card table-card h-100 shadow-sm" style="border-top:4px solid #007B69;">
                {{-- عنوان البطاقة --}}
                <div class="table-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom"
                    style="background-color: rgba(0,123,105,0.08);">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-arrow-left-right me-2" style="color:#007B69;"></i>
                        مراقبة تدفق المهام
                    </h6>
                    <span class="badge rounded-pill" style="background-color:#007B69; color:#fff;">نشط حالياً</span>
                </div>

                {{-- جسم البطاقة (جدول قابل للتمرير) --}}
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top" style="z-index: 1;">
                                <tr>
                                    <th class="ps-4">المهمة</th>
                                    <th>من</th>
                                    <th class="text-center"><i class="bi bi-arrow-left text-muted"></i></th>
                                    <th>إلى</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departmentFlow ?? [] as $flow)
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <a href="{{ route('tasks.show', $flow->id) }}" class="text-decoration-none" style="color: #392015;">
                                            {{ Str::limit($flow->title, 25) }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $flow->from_dept ?? 'المكتب' }}</span>
                                    </td>
                                    <td class="text-center"><i class="bi bi-arrow-left text-muted small"></i></td>
                                    <td>
                                        <span class="badge" style="background-color:#392015; color:#fff;">
                                            {{ $flow->to_dept ?? 'التقنية' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $flow->status == 'returned' ? 'bg-danger' : '' }}"
                                            style="{{ $flow->status == 'returned' ? '' : 'background-color:#007B69; color:#fff;' }}">
                                            {{ $flow->status_label ?? 'معالجة' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <div class="opacity-50 mb-2"><i class="bi bi-inbox fs-1"></i></div>
                                        لا توجد حركات تحويل حديثة
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- الجزء الأيسر: الرسم البياني --}}
        <div class="col-lg-5">
            <div class="card table-card mb-4" style="border-top:4px solid #392015; height: 100%;">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up me-2"></i>تحليل الأداء
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-wrap w-100">
                        <canvas id="cisoTrendChart" data-labels="{{ json_encode($chartLabels) }}"
                            data-completed="{{ json_encode($chartCompletedData) }}"
                            data-created="{{ json_encode($chartCreatedData) }}">
                        </canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>


    {{-- قائمة مهامي --}}
    <div class="tasks-scroll-area shadow-sm rounded-4">
        @include('dashboards.partials.my_tasks')
    </div>

</div>
</div>

@endsection