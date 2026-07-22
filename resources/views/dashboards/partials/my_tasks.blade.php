@php
    $userRole = auth()->user()->role ?? '';

    $adminRoles = ['manager', 'ciso'];

    $showOutgoing = in_array($userRole, $adminRoles, true);

    $showIncoming = true;

    $normalizeStatus = function ($task) {
        $status = $task->status ?? 'pending';

        $creatorRole  = $task->creator->role  ?? '';
        $assigneeRole = $task->assignee->role ?? '';

        if ($creatorRole === 'manager' && $assigneeRole === 'ciso') {
            if (in_array($status, ['submitted', 'reviewed'], true)) {
                $status = 'endorsed'; // مباشرة بانتظار اعتماد المدير العام
            }
        }

        return $status;
    };

    $renderStatusBadge = function ($task) use ($normalizeStatus) {
        $status = $normalizeStatus($task);

        if ($status === 'completed') {
            return '<span class="badge bg-success">مكتملة</span>';
        } elseif ($status === 'submitted') {
            return '<span class="badge bg-info text-dark">بانتظار اعتماد القائد</span>';
        } elseif ($status === 'reviewed') {
            return '<span class="badge bg-primary">بانتظار اعتماد مدير الإدارة</span>';
        } elseif ($status === 'endorsed') {
            return '<span class="badge bg-warning text-dark">بانتظار اعتماد المدير العام</span>';
        } elseif ($status === 'waiting_requester') {
            return '<span class="badge bg-info text-dark">بانتظار اعتماد الجهة الطالبة</span>';
        } elseif ($status === 'returned') {
            return '<span class="badge bg-danger">معادة للتعديل</span>';
        } elseif ($status === 'in_progress') {
            return '<span class="badge bg-primary">جاري التنفيذ</span>';
        } elseif ($status === 'closed') {
            return '<span class="badge bg-dark">مغلقة</span>';
        } elseif ($status === 'archived') {
            return '<span class="badge bg-purple">مؤرشفة</span>';
        } elseif ($status === 'pending' || $status === 'new') {
            return '<span class="badge bg-secondary">جديدة</span>';
        }

        return '<span class="badge bg-secondary">' . e($status) . '</span>';
    };
@endphp

@vite(['resources/css/style.css', 'resources/js/app.js'])

<div class="card shadow-sm border-0 mt-4 bg-white">

    <div class="card-header bg-white border-0 pt-4 pb-2">
        <ul class="nav nav-pills justify-content-center justify-content-md-start" id="taskTabs" role="tablist">

            {{-- تبويب المهام الصادرة: للمدير العام + مدير الإدارة  --}}
            @if($showOutgoing)
            <li class="nav-item">
                <button class="nav-link active" id="outgoing-tab" data-bs-toggle="tab" data-bs-target="#outgoing" type="button">
                    <i class="bi bi-send me-2"></i>المهام الصادرة
                    <span class="badge bg-white text-primary rounded-pill ms-2 border">{{ $sentTasks->count() }}</span>
                </button>
            </li>
            @endif

            {{-- تبويب المهام الواردة: للجميع --}}
            <li class="nav-item">
                <button class="nav-link {{ $showOutgoing ? '' : 'active' }}" id="incoming-tab" data-bs-toggle="tab" data-bs-target="#incoming" type="button">
                    <i class="bi bi-inbox me-2"></i>المهام الواردة
                    <span class="badge bg-secondary rounded-pill ms-2">{{ $receivedTasks->count() }}</span>
                </button>
            </li>

        </ul>
    </div>

    <div class="card-body p-3">
        <div class="tab-content" id="taskTabsContent">

            {{-- محتوى مهام الصادر: يظهر فقط للإدارة --}}
            @if($showOutgoing)
            <div class="tab-pane fade show active" id="outgoing" role="tabpanel">

                <div class="filter-toolbar mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-3 justify-content-between">

                        <div class="search-wrapper position-relative" style="min-width: 200px; flex: 1;">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="text" id="searchOutgoing" class="form-control border-0 bg-light ps-5 rounded-pill" placeholder="بحث...">
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">

                            <div class="btn-group filter-group-status shadow-sm" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all" onclick="applyFilters('outgoing', this)">الكل</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="pending" onclick="applyFilters('outgoing', this)">جديدة</button>
                                <button type="button" class="btn btn-sm btn-outline-primary filter-btn" data-filter="in_progress" onclick="applyFilters('outgoing', this)">جاري</button>
                                <button type="button" class="btn btn-sm btn-outline-info filter-btn" data-filter="approval" onclick="applyFilters('outgoing', this)">اعتماد</button>
                                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="completed" onclick="applyFilters('outgoing', this)">مكتملة</button>
                                <button type="button" class="btn btn-sm btn-outline-dark filter-btn" data-filter="closed" onclick="applyFilters('outgoing', this)">مغلقة</button>
                                <button type="button" class="btn btn-sm btn-outline-purple filter-btn" data-filter="archived" onclick="applyFilters('outgoing', this)">مؤرشفة</button>
                            </div>

                            <div class="vr d-none d-md-block"></div>

                            <div class="btn-group filter-group-priority shadow-sm" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all" onclick="applyFilters('outgoing', this)">الأولوية</button>
                                <button type="button" class="btn btn-sm btn-outline-dark filter-btn" data-filter="critical" onclick="applyFilters('outgoing', this)">حرج</button>
                                <button type="button" class="btn btn-sm btn-outline-danger filter-btn" data-filter="high" onclick="applyFilters('outgoing', this)">عالية</button>
                                <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="medium" onclick="applyFilters('outgoing', this)">متوسطة</button>
                                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="low" onclick="applyFilters('outgoing', this)">منخفضة</button>
                            </div>

                            <div class="vr d-none d-md-block"></div>

                            <div class="btn-group filter-group-complexity shadow-sm" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all" onclick="applyFilters('outgoing', this)">التعقيد</button>
                                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="S" onclick="applyFilters('outgoing', this)">بسيط</button>
                                <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="M" onclick="applyFilters('outgoing', this)">متوسط</button>
                                <button type="button" class="btn btn-sm btn-outline-danger filter-btn" data-filter="L" onclick="applyFilters('outgoing', this)">عالي</button>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="table-outgoing">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="text-end py-3" style="width: 30%;">المهمة</th>
                                <th class="text-center">الأولوية</th>
                                <th class="text-center">التعقيد</th>
                                <th class="text-end">المسؤول</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">التاريخ</th>
                                <th class="text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sentTasks as $task)
                            <tr class="task-row"
                                data-status="{{ $normalizeStatus($task) }}"
                                data-complexity="{{ trim($task->complexity) }}"
                                data-priority="{{ trim($task->priority) }}">

                                <td class="text-end">
                                    <div class="fw-bold text-dark">{{ $task->title }}</div>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-tag me-1"></i> {{ $task->sub_category ?? 'عام' }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($task->priority == 'critical') <span class="badge bg-dark rounded-pill">حرج</span>
                                    @elseif($task->priority == 'high') <span class="badge bg-danger rounded-pill">عالية</span>
                                    @elseif($task->priority == 'medium') <span class="badge bg-warning text-dark rounded-pill">متوسطة</span>
                                    @else <span class="badge bg-success rounded-pill">منخفضة</span> @endif
                                </td>

                                <td class="text-center">
                                    @if(trim($task->complexity) == 'L') <span class="badge bg-danger-subtle text-danger border border-danger">عالي</span>
                                    @elseif(trim($task->complexity) == 'M') <span class="badge bg-warning-subtle text-dark border border-warning">متوسط</span>
                                    @else <span class="badge bg-success-subtle text-success border border-success">بسيط</span> @endif
                                </td>

                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <span class="me-2">{{ $task->assignee->name ?? 'غير محدد' }}</span>
                                        <div class="avatar-circle bg-light border text-primary small d-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px;">
                                            {{ substr($task->assignee->name ?? '?', 0, 1) }}
                                        </div>
                                    </div>
                                </td>

                                <td class="text-center">{!! $renderStatusBadge($task) !!}</td>
                                <td class="text-center small text-muted">{{ $task->created_at->format('Y-m-d') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        متابعة
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">لا يوجد مهمة صادرة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- محتوى مهام الوارد: للجميع --}}
            <div class="tab-pane fade {{ $showOutgoing ? '' : 'show active' }}" id="incoming" role="tabpanel">

                <div class="filter-toolbar mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-3 justify-content-between">

                        <div class="search-wrapper position-relative" style="min-width: 200px; flex: 1;">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="text" id="searchIncoming" class="form-control border-0 bg-light ps-5 rounded-pill" placeholder="بحث...">
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">

                            <div class="btn-group filter-group-status shadow-sm" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all" onclick="applyFilters('incoming', this)">الكل</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn" data-filter="pending" onclick="applyFilters('incoming', this)">جديدة</button>
                                <button type="button" class="btn btn-sm btn-outline-primary filter-btn" data-filter="in_progress" onclick="applyFilters('incoming', this)">جاري</button>
                                <button type="button" class="btn btn-sm btn-outline-info filter-btn" data-filter="approval" onclick="applyFilters('incoming', this)">اعتماد</button>
                                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="completed" onclick="applyFilters('incoming', this)">مكتملة</button>
                                <button type="button" class="btn btn-sm btn-outline-dark filter-btn" data-filter="closed" onclick="applyFilters('incoming', this)">مغلقة</button>
                                <button type="button" class="btn btn-sm btn-outline-purple filter-btn" data-filter="archived" onclick="applyFilters('incoming', this)">مؤرشفة</button>
                            </div>

                            <div class="vr d-none d-md-block"></div>

                            <div class="btn-group filter-group-priority shadow-sm" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all" onclick="applyFilters('incoming', this)">الأولوية</button>
                                <button type="button" class="btn btn-sm btn-outline-dark filter-btn" data-filter="critical" onclick="applyFilters('incoming', this)">حرج</button>
                                <button type="button" class="btn btn-sm btn-outline-danger filter-btn" data-filter="high" onclick="applyFilters('incoming', this)">عالية</button>
                                <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="medium" onclick="applyFilters('incoming', this)">متوسطة</button>
                                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="low" onclick="applyFilters('incoming', this)">منخفضة</button>
                            </div>

                            <div class="vr d-none d-md-block"></div>

                            <div class="btn-group filter-group-complexity shadow-sm" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all" onclick="applyFilters('incoming', this)">التعقيد</button>
                                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="S" onclick="applyFilters('incoming', this)">بسيط</button>
                                <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="M" onclick="applyFilters('incoming', this)">متوسط</button>
                                <button type="button" class="btn btn-sm btn-outline-danger filter-btn" data-filter="L" onclick="applyFilters('incoming', this)">عالي</button>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="table-incoming">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="text-end py-3" style="width: 30%;">المهمة</th>
                                <th class="text-center">الأولوية</th>
                                <th class="text-center">التعقيد</th>
                                <th class="text-end">المرسل</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">الاستحقاق</th>
                                <th class="text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receivedTasks as $task)
                            <tr class="task-row"
                                data-status="{{ $normalizeStatus($task) }}"
                                data-complexity="{{ trim($task->complexity) }}"
                                data-priority="{{ trim($task->priority) }}">

                                <td class="text-end">
                                    <div class="fw-bold text-dark">{{ $task->title }}</div>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-tag me-1"></i> {{ $task->sub_category ?? 'عام' }}
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($task->priority == 'critical') <span class="badge bg-dark rounded-pill">حرج</span>
                                    @elseif($task->priority == 'high') <span class="badge bg-danger rounded-pill">عالية</span>
                                    @elseif($task->priority == 'medium') <span class="badge bg-warning text-dark rounded-pill">متوسطة</span>
                                    @else <span class="badge bg-success rounded-pill">منخفضة</span> @endif
                                </td>

                                <td class="text-center">
                                    @if(trim($task->complexity) == 'L') <span class="badge bg-danger-subtle text-danger border border-danger">عالي</span>
                                    @elseif(trim($task->complexity) == 'M') <span class="badge bg-warning-subtle text-dark border border-warning">متوسط</span>
                                    @else <span class="badge bg-success-subtle text-success border border-success">بسيط</span> @endif
                                </td>

                                <td class="text-end">{{ $task->creator->name ?? 'نظام' }}</td>

                                <td class="text-center">{!! $renderStatusBadge($task) !!}</td>

                                <td class="text-center">
                                    @if($task->due_date < date('Y-m-d') && $task->status != 'completed')
                                        <div class="text-danger fw-bold d-flex flex-column align-items-center">
                                            <span>{{ $task->due_date }}</span>
                                            <span class="badge bg-danger text-white rounded-pill px-2 mt-1" style="font-size: 0.6rem;">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> متأخرة
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-dark">{{ $task->due_date }}</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">عرض</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">لا يوجد مهمة واردة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</div>
