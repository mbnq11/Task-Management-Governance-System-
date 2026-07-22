@extends('layouts.layout')

@section('content')

@php
$selectedIds = $selectedIds ?? [];
$finalAttachments = $finalAttachments ?? collect();
$message = $message ?? '';
$isFinished = $isFinished ?? false;

$isRequesterManager = (auth()->check()
&& auth()->user()->role === 'manager'
&& auth()->id() == ($task->created_by ?? null));

$canAct = $isRequesterManager && ($task->status === 'waiting_requester');
@endphp
@vite('resources/css/style.css')
<div class="container py-4">

    @if ($errors->any())
    <div class="alert alert-danger shadow-sm border-0 mb-4">
        <div class="fw-bold mb-2"><i class="bi bi-exclamation-octagon me-2"></i>حدثت أخطاء، يرجى التصحيح:</div>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- تفاصيل المهمة --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

                {{-- معلومات المهمة --}}
                <div class="task-status-info flex-grow-1">
                    <div class="mb-2">
                        <span class="badge bg-dark">#{{ $task->id }}</span>
                        <span class="badge bg-secondary">{{ $task->sub_category ?? 'عام' }}</span>
                    </div>
                    <h4 class="fw-bold mb-1">{{ $task->title }}</h4>
                    <div class="text-muted small mt-2">
                        <i class="bi bi-person me-1"></i> المنشئ: {{ $task->creator->name ?? 'غير محدد' }}
                        <span class="mx-2 text-muted">|</span>
                        <i class="bi bi-person-gear me-1"></i> المنفّذ: {{ $task->assignee->name ?? 'غير محدد' }}
                    </div>
                </div>

                {{-- حالة المهمة --}}
                <div class="text-end">
                    @if($task->status === 'waiting_requester')
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6 shadow-sm">
                        <i class="bi bi-hourglass-split me-1"></i> بانتظار اعتماد الجهة الطالبة
                    </span>
                    @elseif($isFinished)
                    <span class="badge bg-success px-3 py-2 fs-6 shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> منتهية
                    </span>
                    @else
                    <span class="badge bg-secondary px-3 py-2 fs-6 shadow-sm">{{ $task->status }}</span>
                    @endif
                </div>
            </div>

            {{-- رسالة توضيحية --}}
            @if($message)
            <div class="alert alert-light mt-4 mb-0 border-1 border-secondary bg-light text-dark shadow-sm">
                <i class="bi bi-info-circle-fill me-2"></i> {{ $message }}
            </div>
            @endif
        </div>
    </div>

    {{-- المخرجات النهائية --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="fw-bold mb-0 text-success">
                <i class="bi bi-check2-circle me-2"></i>المخرجات النهائية المختارة
            </h6>
        </div>

        <div class="card-body p-3 bg-light rounded-bottom">
            @if($finalAttachments->count())
            <div class="d-flex flex-column gap-3">
                @foreach($finalAttachments as $c)
                <div class="final-attachment-item d-flex align-items-center justify-content-between bg-white p-3 rounded border shadow-sm">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-2 rounded border text-danger">
                            <i class="bi bi-file-earmark-pdf fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">مرفق نهائي</div>
                            <div class="small text-muted">{{ $c->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>

                    <a href="{{ asset('storage/' . $c->attachment) }}" target="_blank" class="btn btn-outline-dark btn-preview">
                        <i class="bi bi-eye me-1"></i> معاينة
                    </a>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-muted small text-center py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                لا توجد مخرجات نهائية محددة حتى الآن.
            </div>
            @endif
        </div>
    </div>

    {{-- إجراءات المدير الطالب (اعتماد أو إرجاع) --}}
    @if($canAct)
    <div class="action-box p-4 bg-white shadow-sm rounded border">
        <h6 class="fw-bold mb-4 border-bottom pb-2">إجراءات الجهة الطالبة</h6>

        <div class="row g-4">

            {{-- خيار الاعتماد --}}
            <div class="col-md-5">
                <div class="p-3 border rounded bg-success bg-opacity-10 h-100">
                    <h6 class="fw-bold text-success mb-3">اعتماد وإنهاء المهمة</h6>
                    <p class="small text-muted mb-3">سيتم إغلاق المهمة نهائياً واعتماد المخرجات المرفقة.</p>

                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="approve">
                        <button type="submit" class="btn btn-success w-100 fw-bold rounded-pill" {{ $finalAttachments->count() ? '' : 'disabled' }}>
                            <i class="bi bi-check-lg me-1"></i> اعتماد وإغلاق
                        </button>
                    </form>

                    @if(!$finalAttachments->count())
                    <div class="small text-danger mt-2 text-center bg-white p-1 rounded border border-danger">
                        <i class="bi bi-exclamation-circle me-1"></i> لا يمكن الاعتماد قبل تحديد المخرجات.
                    </div>
                    @endif
                </div>
            </div>

            {{-- خيار الإرجاع --}}
            <div class="col-md-7">
                <div class="p-3 border rounded bg-danger bg-opacity-10 h-100">
                    <h6 class="fw-bold text-danger mb-3">إعادة للإدارة المنفذة</h6>
                    <p class="small text-muted mb-2">في حال وجود ملاحظات أو نقص، يمكنك إعادة المهمة للتعديل.</p>

                    <form action="{{ route('tasks.updateStatus', $task->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="returned">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">سبب الإرجاع (إلزامي)</label>
                            <textarea name="return_note" class="form-control border-secondary" rows="2" required placeholder="اكتب الملاحظات هنا..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-outline-danger w-100 fw-bold rounded-pill">
                            <i class="bi bi-arrow-return-right me-1"></i> إعادة للتعديل
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
    @endif

</div>

@endsection