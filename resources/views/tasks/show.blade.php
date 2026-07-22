@extends('layouts.layout')

@section('content')

@php
$isCrossDepartment = $isCrossDepartment ?? false;
$allAttachments = $allAttachments ?? collect();
$selectedIds = $selectedIds ?? [];
$finalAttachments = $finalAttachments ?? collect();

$isExecutive = $isExecutive ?? false;
$filteredComments = $filteredComments ?? ($task->comments ?? collect());

$isFinished = in_array($task->status, ['completed', 'closed', 'archived'], true);
@endphp

{{-- 1. الترويسة بتصميم CISO --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="p-4 rounded-4 shadow-sm dashboard-header d-flex flex-wrap justify-content-between align-items-center">

            {{-- القسم الأيمن: تفاصيل العنوان --}}
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill shadow-sm" style="background-color:#FFFFFF; color:#392015;">#{{ $task->id }}</span>
                    <span class="badge bg-light text-dark border border-opacity-25 shadow-sm">
                        {{ $task->sub_category ?? 'عام' }}
                    </span>

                    @if($isExecutive)
                    <span class="badge rounded-pill shadow-sm" style="background-color:#FFFFFF; color:#fff;">Executive</span>
                    @endif
                </div>

                <h3 class="fw-bold mb-1" style="color:#FFFFFF;">

                    @if($isExecutive) <i class="bi bi-star-fill text-warning me-2"></i> @endif
                    {{ $task->title }}
                </h3>

                <div class="mt-2">
                    <p class="mb-0 opacity-75 small d-inline-block" style="color:#FFFFFF;">

                        <i class="bi bi-person-circle me-1" style="color:#A37956;"></i> المنشئ: <strong>{{ $task->creator->name ?? 'غير محدد' }}</strong>
                        <span class="mx-2 text-muted">|</span>
                        <i class="bi bi-building me-1" style="color:#A37956;"></i> إدارة: <strong>{{ $task->creator->department ?? 'غير محدد' }}</strong>
                    </p>

                    @if(!$isFinished)
                    @php
                    $due = \Carbon\Carbon::parse($task->due_date);
                    $isOverdue = \Carbon\Carbon::now()->gt($due);
                    $diff = $due->diff(\Carbon\Carbon::now());
                    @endphp
                    <span class="ms-3 fw-bold px-2 py-1 rounded small shadow-sm"
                        style="{{ $isOverdue ? 'background-color:#973D4B; color:#fff;' : 'background-color:#007B69; color:#fff;' }}">
                        <i class="bi {{ $isOverdue ? 'bi-exclamation-triangle-fill' : 'bi-hourglass-split' }}"></i>
                        @if($isOverdue) متأخرة منذ: @else المتبقي: @endif
                        {{ $diff->d }} يوم، {{ $diff->h }} ساعة
                    </span>
                    @else
                    <span class="ms-3 fw-bold px-2 py-1 rounded small shadow-sm" style="background-color:#392015; color:#fff;">
                        <i class="bi bi-check-circle-fill"></i> المهمة منتهية
                    </span>
                    @endif
                </div>
            </div>

            {{-- القسم الأيسر: الحالة وأزرار الطباعة --}}
            <div class="text-md-end mt-3 mt-md-0">
                <div class="mb-2">
                    @include('dashboards.partials.status_badge', ['status' => $task->status])
                </div>

                @if(Auth::user()->role === 'auditor')
                <a href="{{ route('auditor.tasks.print', $task->id) }}" target="_blank"
                    class="btn btn-sm fw-bold shadow-sm rounded-pill px-3"
                    style="background-color:#fff; color:#973D4B; border:1px solid #973D4B;">
                    <i class="bi bi-printer me-2"></i> طباعة (مدقق)
                </a>
                @else
                <a href="{{ route('tasks.print', $task->id) }}" target="_blank"
                    class="btn btn-sm fw-bold shadow-sm rounded-pill px-3"
                    style="background-color:#fff; color:#392015; border:1px solid #392015;">
                    <i class="bi bi-printer me-2"></i> طباعة التقرير
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- العمود الأيمن: تفاصيل المهمة --}}
    <div class="col-lg-8">

        {{-- بطاقة الوصف ونسبة الإنجاز --}}
        {{-- بطاقة الوصف (قابلة للطي) --}}
<div class="card shadow-sm mb-4 border-0 rounded-4" style="background-color: #fcfcfc; border: 1px solid #e0e0e0 !important;">
    {{-- زر الفتح والإغلاق --}}
    <button class="btn w-100 d-flex justify-content-between align-items-center p-3 text-start shadow-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#taskDescriptionCollapse"
            aria-expanded="true"
            aria-controls="taskDescriptionCollapse">
        
        <h6 class="fw-bold mb-0 small text-uppercase" style="color:#A37956;">
            <i class="bi bi-card-text me-1"></i> وصف وتفاصيل المهمة
        </h6>
        
        {{-- أيقونة السهم --}}
        <i class="bi bi-chevron-down transition-icon" style="color:#A37956;"></i>
    </button>

    {{-- المحتوى الذي "ينزل جوا" --}}
    <div class="collapse show" id="taskDescriptionCollapse">
        <div class="card-body pt-0 px-3 pb-3">
            <hr class="mt-0 mb-3 opacity-25">
            {{-- تم إضافة سكرول هنا في حال كان النص طويلاً جداً --}}
            <div style="max-height: 200px; overflow-y: auto;">
                <p class="text-secondary mb-0" style="white-space: pre-line; font-size: 0.95rem; line-height: 1.6;">
                    {{ $task->description }}
                </p>
            </div>
        </div>
    </div>
</div>

        {{-- المخرجات النهائية --}}
        @if(!$isExecutive && $isCrossDepartment && $finalAttachments->count())
        <div class="card shadow-sm mb-4 border-0 rounded-4" style="border-top: 4px solid #198754 !important;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-success">
                    <i class="bi bi-check2-circle me-2"></i>المخرجات النهائية المختارة
                </h6>
            </div>
            <div class="card-body p-3">
                @foreach($finalAttachments as $c)
                <a class="btn btn-sm w-100 text-start mb-2 shadow-sm border"
                    style="color: #198754; background-color: #f8fff9;"
                    href="{{ asset('storage/' . $c->attachment) }}" target="_blank">
                    <i class="bi bi-file-earmark-check me-2"></i>
                    تحميل/معاينة المخرج النهائي
                    <span class="text-muted small float-end">{{ $c->created_at->format('Y-m-d H:i') }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- لوحات سير العمل --}}
        @if(!$isExecutive)

        {{-- بطاقة فريق العمل --}}
        <div class="card shadow-sm mb-4 border-0 rounded-4" style="border-top: 4px solid #392015 !important;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0" style="color:#392015;">
                    <i class="bi bi-people me-2"></i>فريق العمل
                </h6>
            </div>

            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2">
                    {{-- المسؤول الرئيسي --}}
                    <div class="d-flex align-items-center rounded-pill pe-3 ps-1 py-1 border"
                        style="background-color: rgba(57,32,21,0.05); border-color: rgba(57,32,21,0.2) !important;">
                        <div class="rounded-circle text-white me-2 d-flex align-items-center justify-content-center"
                            style="width: 28px; height: 28px; background-color: #392015;">
                            {{ substr($task->assignee->name ?? '?', 0, 1) }}
                        </div>
                        <span class="fw-bold small" style="color:#392015;">
                            {{ $task->assignee->name ?? 'غير محدد' }}
                        </span>
                        <span class="badge ms-2" style="background-color:#392015; font-size: 0.65rem;">مسؤول</span>
                    </div>

                    {{-- باقي الأعضاء --}}
                    @foreach($task->team as $member)
                    @if($member->id != $task->assigned_to)
                    <div class="d-flex align-items-center bg-white border rounded-pill pe-3 ps-1 py-1 shadow-sm">
                        <div class="rounded-circle bg-light border text-muted me-2 d-flex align-items-center justify-content-center"
                            style="width: 28px; height: 28px; font-size: 0.8rem;">
                            {{ substr($member->name, 0, 1) }}
                        </div>
                        <span class="text-dark small fw-bold">{{ $member->name }}</span>

                        @php
                        $canDelete = in_array(Auth::user()->role, ['ciso', 'manager'])
                        || (Auth::user()->role == 'team_leader' && $member->role == 'employee');
                        @endphp

                        @if($canDelete && !in_array($task->status, ['completed', 'closed', 'archived']))
                        <form action="{{ route('tasks.remove_member', ['task_id' => $task->id, 'user_id' => $member->id]) }}"
                            method="POST" class="ms-2">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="btn btn-link p-0 m-0 d-flex"
                                style="color:#973D4B;"
                                onclick="return confirm('حذف العضو؟')">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>

                @php
                $employeesForAdd = $availableEmployees ?? collect();

                $auth = Auth::user();
                $executorDept = $task->assignee?->department;

                $deptTree = config('departments.children', []);
                $allowedDepts = $executorDept
                ? array_merge([$executorDept], $deptTree[$executorDept] ?? [])
                : [];

                if ($auth->role === 'ciso' && $executorDept) {
                $employeesForAdd = $employeesForAdd->filter(function ($u) use ($allowedDepts) {
                return in_array($u->role, ['employee','team_leader'], true)
                && in_array($u->department, $allowedDepts, true);
                });
                }
                @endphp

                @if(in_array(Auth::user()->role, ['ciso', 'manager', 'team_leader']) && !in_array($task->status, ['completed', 'closed', 'archived']))
                <div class="mt-4 pt-3 border-top">
                    <form action="{{ route('tasks.add_member', $task->id) }}" method="POST" class="row g-2 align-items-center">
                        @csrf
                        <div class="col-md-5">
                            <select name="user_id" class="form-select form-select-sm" required>
                                <option value="">+ إضافة عضو للفريق...</option>

                                @foreach((Auth::user()->role === 'ciso' ? $employeesForAdd : $availableEmployees) as $emp)
                                @if($emp->role === 'auditor') @continue @endif
                                @if(!$task->team->contains($emp->id))
                                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endif
                                @endforeach
                            </select>

                            @if(Auth::user()->role === 'ciso' && $executorDept)

                            @endif
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm rounded-pill px-3 text-white" style="background-color:#007B69;">إضافة</button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </div>

        @php
        $isAssigned = (Auth::id() == $task->assigned_to || $task->team->contains(Auth::id()));
        $isEmployee = Auth::user()->role == 'employee';
        $isActive = in_array($task->status, ['pending', 'returned', 'in_progress']);
        @endphp

        {{-- منطقة عمل الموظف --}}
        @if($isAssigned && $isEmployee && $isActive)
        <div class="card shadow-sm mb-4 rounded-4 border-0" style="border-top: 4px solid #007B69 !important;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0" style="color:#007B69;">
                    <i class="bi bi-pencil-square me-2"></i>منطقة العمل
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label class="small fw-bold text-muted mb-2">تحديث نسبة الإنجاز</label>
                        <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST" class="d-flex align-items-center gap-2">
                            @csrf
                            <input type="hidden" name="status" value="in_progress">
                            <div class="input-group">
                                <input type="number" name="completion_percentage" class="form-control"
                                    min="0" max="100" value="{{ $task->completion_percentage }}" placeholder="%">
                                <button class="btn text-white" style="background-color:#007B69;">حفظ</button>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-5 text-md-end border-start">
                        <label class="small fw-bold text-muted mb-2 d-block">هل انتهيت من العمل؟</label>
                        <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="submitted">
                            <button class="btn fw-bold w-100 rounded-pill shadow-sm text-white" style="background-color:#007B69;">
                                <i class="bi bi-send-check me-2"></i> رفع للاعتماد
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- رسالة انتظار للموظف --}}
        @if($isEmployee && !in_array($task->status, ['pending', 'returned', 'in_progress', 'completed', 'closed', 'archived']))
        <div class="alert shadow-sm d-flex align-items-center" style="background-color: #E3A778; color: #392015; border:none;">
            <i class="bi bi-hourglass-split fs-4 me-3"></i>
            <div>
                <strong>المهمة قيد المراجعة الإدارية</strong>
                <div class="small">بانتظار اعتماد قائد الفريق أو المدير. لا يمكنك التعديل حالياً.</div>
            </div>
        </div>
        @endif

        {{-- قائد الفريق --}}
        @if(Auth::user()->role == 'team_leader')
        @if($task->status == 'submitted')
        <div class="card shadow-sm mb-4 rounded-4 border-0" style="border-top: 4px solid #E3A778 !important; background-color: #fffbf0;">
            <div class="card-body text-center p-4">
                <h5 class="fw-bold mb-3" style="color:#392015;">إجراءات القائد</h5>
                <div class="d-flex justify-content-center gap-3">
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="approve">
                        <button class="btn fw-bold px-4 rounded-pill shadow-sm text-white" style="background-color:#007B69;">
                            اعتماد ورفع للمدير
                        </button>
                    </form>
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="returned">
                        <button class="btn fw-bold px-4 rounded-pill bg-white shadow-sm" style="color:#973D4B; border:1px solid #973D4B;">
                            إعادة للموظف
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @elseif($task->status == 'returned')
        <div class="alert alert-danger shadow-sm text-center border-0" style="background-color:#973D4B; color:#fff;">
            <i class="bi bi-arrow-return-left me-2"></i>
            المهمة معادة للموظف للتعديل. بانتظار إعادة الرفع.
        </div>
        @endif
        @endif

        {{-- اختيار المخرجات النهائية (مدير الإدارة المنفذة) --}}
        @php
        $taskAssigneeDept = $task->assignee?->department;
        $isExecutorManager = (Auth::user()->role === 'manager'
        && $isCrossDepartment
        && $taskAssigneeDept
        && Auth::user()->department === $taskAssigneeDept);
        $canSelectOutputs = $isExecutorManager && in_array($task->status, ['reviewed','submitted'], true);
        $hasSelection = is_array($selectedIds) ? count($selectedIds) > 0 : false;
        @endphp

        @if($canSelectOutputs)
        <div class="card shadow-sm mb-4 border-0 rounded-4" style="border-top: 4px solid #198754 !important;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-success">
                    تحديد المخرجات النهائية قبل إرسالها للجهة الطالبة
                </h6>
            </div>

            <div class="card-body p-3">
                <form action="{{ route('tasks.final_attachments.set', $task->id) }}" method="POST">
                    @csrf
                    <div class="d-flex flex-column gap-2">
                        @forelse(($allAttachments ?? collect()) as $c)
                        <label class="d-flex align-items-center justify-content-between bg-light p-3 rounded border">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="final_comment_ids[]" value="{{ $c->id }}"
                                    {{ in_array($c->id, $selectedIds ?? []) ? 'checked' : '' }}
                                    class="form-check-input" style="border-color:#198754;">
                                <span class="small">
                                    مرفق — <span class="text-muted">{{ $c->created_at->format('Y-m-d H:i') }}</span>
                                </span>
                            </div>
                            <a href="{{ asset('storage/' . $c->attachment) }}" target="_blank"
                                class="btn btn-sm btn-outline-secondary rounded-pill">
                                معاينة
                            </a>
                        </label>
                        @empty
                        <div class="text-muted small">لا توجد مرفقات في سجل المراسلات.</div>
                        @endforelse
                    </div>
                    <div class="mt-3 text-center">
                        <button type="submit" class="btn fw-bold rounded-pill px-4 text-white" style="background-color:#198754;">
                            حفظ الاختيار
                        </button>
                    </div>
                </form>

                <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="send_to_requester">
                        <button type="submit" class="btn fw-bold rounded-pill px-4 text-white shadow-sm"
                            style="background-color:#007B69;" {{ $hasSelection ? '' : 'disabled' }}>
                            إرسال للجهة الطالبة
                        </button>
                    </form>
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="returned">
                        <button type="submit" class="btn fw-bold rounded-pill px-4 shadow-sm" style="background-color:#fff; color:#973D4B; border:1px solid #973D4B;">
                            إعادة للتعديل
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{--  مدير الإدارة (داخل الإدارة) --}}
        @if(Auth::user()->role == 'manager')
        @php
        $hideManagerApprovalCard = ($isExecutorManager && $isCrossDepartment && in_array($task->status, ['reviewed','submitted'], true));
        @endphp

        @if(!$hideManagerApprovalCard)
        @if(in_array($task->status, ['reviewed', 'submitted'], true))
        <div class="card shadow-sm mb-4 rounded-4 border-0" style="border-top: 4px solid #007B69 !important; background-color: #f0fff4;">
            <div class="card-body text-center p-4">
                <h5 class="fw-bold mb-3" style="color:#007B69;">إجراءات المدير</h5>
                <div class="d-flex justify-content-center gap-3">
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="approve">
                        <button class="btn fw-bold px-4 rounded-pill shadow-sm text-white" style="background-color:#007B69;">
                            @if($task->creator && $task->creator->department !== Auth::user()->department)
                            <i class="bi bi-send-check-fill me-2"></i> اعتماد وإرسال للجهة الطالبة
                            @else
                            <i class="bi bi-shield-check me-2"></i> رفع للاعتماد النهائي (CISO)
                            @endif
                        </button>
                    </form>
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="returned">
                        <button class="btn fw-bold px-4 rounded-pill bg-white shadow-sm" style="color:#973D4B; border:1px solid #973D4B;">
                            إعادة للتعديل
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @elseif($task->status == 'endorsed')
        <div class="alert shadow-sm text-center border-0" style="background-color:#D4AF91; color:#392015;">
            <i class="bi bi-shield-check me-2"></i> تم رفع المهمة للمدير العام (CISO). بانتظار الاعتماد النهائي.
        </div>
        @elseif($task->status == 'returned')
        <div class="alert shadow-sm text-center border-0" style="background-color:#973D4B; color:#fff;">
            <i class="bi bi-arrow-return-left me-2"></i> تمت إعادة المهمة للموظف. بانتظار التصحيح وإعادة الرفع.
        </div>
        @endif
        @endif
        @endif

        {{--  CISO --}}
        @if(Auth::user()->role == 'ciso')
        <div class="card shadow-sm mb-4 rounded-4 border-0" style="border-top: 4px solid #392015 !important;">
            <div class="card-header text-white py-3" style="background-color:#392015;">
                <h6 class="fw-bold mb-0">تحكم المدير العام (CISO)</h6>
            </div>
            <div class="card-body p-4 bg-light text-center">
                @if($task->status == 'returned')
                <div class="alert alert-danger d-inline-block px-4 mb-3 border-0" style="background-color:#973D4B; color:#fff;">
                    <i class="bi bi-exclamation-octagon me-2"></i>
                    المهمة معادة للموظف للتعديل. بانتظار إعادة الرفع.
                </div>
                @endif

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @if($task->status == 'endorsed')
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="completed">
                        <button class="btn fw-bold rounded-pill px-4 shadow-sm text-white" style="background-color:#007B69;">
                            <i class="bi bi-patch-check-fill me-2"></i> اعتماد نهائي
                        </button>
                    </form>
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="returned">
                        <button class="btn fw-bold rounded-pill px-4 shadow-sm text-white" style="background-color:#973D4B;">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> رفض وإعادة
                        </button>
                    </form>
                    <div class="vr mx-2"></div>
                    @endif

                    @if($task->status != 'closed')
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="closed">
                        <button class="btn btn-sm rounded-pill px-3 h-100 text-white shadow-sm"
                            style="background-color:#D4AF91;"
                            onclick="return confirm('هل أنت متأكد من إغلاق/إلغاء المهمة')">
                            <i class="bi bi-lock-fill me-1"></i> إغلاق / إلغاء
                        </button>
                    </form>
                    @endif

                    @if($task->status != 'archived')
                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST" class="d-inline ms-2">
                        @csrf
                        <input type="hidden" name="status" value="archived"><button class="btn btn-sm rounded-pill px-3 h-100 shadow-sm"
                            style="background-color:#F4A261; color:#fff;">
                            <i class="bi bi-archive-fill me-1"></i> أرشفة
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @endif
    </div>

    {{-- العمود الأيسر: التعليقات --}}
    <div class="col-lg-4">
        <div class="card shadow-sm h-100 border-0 rounded-4 d-flex flex-column" style="border-top: 4px solid #A37956 !important;">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0" style="color:#392015;">
                    <i class="bi bi-chat-dots me-2" style="color:#A37956;"></i>سجل المراسلات
                </h6>
            </div>

            <div class="card-body bg-light p-3 d-flex flex-column gap-3" style="height: 500px; overflow-y: auto;">
                @forelse($filteredComments as $comment)
                @php
                $isMe = $comment->user_id == Auth::id();
                $isSystem = str_contains($comment->comment, '[نظام]');
                @endphp

                @if($isSystem)
                <div class="system-comment py-1 small fw-bold shadow-sm text-center rounded mx-auto px-3"
                    style="background-color:#e9ecef; color:#6c757d; max-width: 90%;">
                    {{ str_replace('[نظام]', '', $comment->comment) }}
                    <div style="font-size: 0.65rem; opacity: 0.6; margin-top:2px;">
                        {{ $comment->created_at->format('H:i') }} - {{ $comment->created_at->format('d/m') }}
                    </div>
                </div>
                @else
                <div class="d-flex {{ $isMe ? 'flex-row-reverse' : 'flex-row' }}">
                    {{-- الأفاتار --}}
                    <div class="rounded-circle flex-shrink-0 shadow-sm d-flex align-items-center justify-content-center {{ $isMe ? 'ms-2' : 'me-2' }}"
                        style="width:35px; height:35px;
                                        background-color: {{ $isMe ? '#007B69' : '#fff' }};
                                        border: {{ $isMe ? 'none' : '1px solid #dee2e6' }};">
                        {{ substr($comment->user->name ?? 'U', 0, 1) }}
                    </div>

                    <div class="chat-bubble shadow-sm p-2 px-3 rounded-3"
                        style="max-width: 80%;
                                        background-color: {{ $isMe ? '#007B69' : '#fff' }};
                                        color: {{ $isMe ? '#fff' : '#212529' }};
                                        {{ $isMe ? 'border-top-left-radius: 0 !important;' : 'border-top-right-radius: 0 !important;' }}">

                        @if(!$isMe)
                        <small class="fw-bold d-block mb-1" style="font-size:0.75rem; color:#A37956;">
                            {{ $comment->user->name }}
                        </small>
                        @endif

                        <p class="mb-0 small" style="line-height: 1.4;">
                            {!! nl2br(e($comment->comment)) !!}
                        </p>

                        @if($comment->attachment)
                        <a href="{{ asset('storage/' . $comment->attachment) }}" target="_blank"
                            class="btn btn-sm mt-2 w-100 text-start d-flex align-items-center gap-2 rounded"
                            style="background-color: {{ $isMe ? 'rgba(255,255,255,0.2)' : '#f8f9fa' }};
                                              color: {{ $isMe ? '#fff' : '#212529' }}; border:none;">
                            <i class="bi bi-paperclip"></i>
                            <span class="text-truncate">مرفق</span>
                        </a>
                        @endif

                        <div class="text-end mt-1" style="font-size: 0.65rem; opacity: 0.7;">
                            {{ $comment->created_at->format('H:i') }}
                        </div>
                    </div>
                </div>
                @endif
                @empty
                <div class="text-center text-muted mt-5 py-5">
                    <i class="bi bi-chat-square-text fs-1 opacity-25"></i>
                    <p class="mt-2 small">لا توجد تعليقات حتى الآن</p>
                </div>
                @endforelse
            </div>

            <div class="card-footer bg-white p-3 border-top">
                @if(Auth::user()->role !== 'auditor')
                @php
                $canCommentExecutive = (!$isExecutive) || in_array(Auth::user()->role, ['ciso','manager'], true);
                @endphp

                @if($canCommentExecutive)
                <form action="{{ route('tasks.comments.store', $task->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="input-group bg-light rounded-pill border p-1 mb-2 shadow-sm">
                        <textarea name="comment" class="form-control border-0 bg-transparent shadow-none"
                            rows="1" placeholder="اكتب ردك هنا..." required style="resize: none;"></textarea>
                        <button class="btn rounded-pill px-4 m-1 shadow-sm text-white" style="background-color:#FFFFFF;">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="file" name="attachment" class="form-control form-control-sm" id="fileInput">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="document.getElementById('fileInput').value = ''" title="إلغاء الملف">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </form>
                @else
                <div class="text-center text-muted small py-2 bg-light rounded">
                    <i class="bi bi-shield-lock-fill me-1"></i> لا يمكنك التعليق على معاملة Executive
                </div>
                @endif
                @else
                <div class="text-center text-muted small py-2 bg-light rounded">
                    <i class="bi bi-eye-fill me-1"></i> وضع القراءة فقط (للمدقق)
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection