@extends('layouts.layout')

@section('content')
@vite('resources/css/style.css')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card task-form-card">
                
                <div class="task-form-header d-flex justify-content-between align-items-center"
                     style="background:linear-gradient(135deg,#392015,#5E452E); color:#fff;">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-plus-circle me-2" style="color:#D4AF91;"></i>
                        إنشاء مهمة جديدة
                    </h5>
                </div>

                <div class="card-body p-3 p-md-4">
                    
                    @if(in_array(Auth::user()->role, ['auditor', 'team_leader', 'employee']))
                        <div class="access-denied-box text-center p-4"
                             style="border:1px solid #973D4B; background:#fdf6f7; border-radius:12px;">
                            <i class="bi bi-shield-lock-fill access-denied-icon"
                               style="color:#973D4B; font-size:3.5rem;"></i>
                            <h4 class="fw-bold mt-2">غير مصرح بالوصول</h4>
                            <p class="mb-3">ليس لديك الصلاحية لإنشاء مهام جديدة.</p>
                            <a href="{{ route('dashboard') }}"
                               class="btn px-4 rounded-pill"
                               style="border:1px solid #973D4B; color:#973D4B;">
                                الرئيسية
                            </a>
                        </div>
                    @else
                        @if ($errors->any())
                            <div class="alert shadow-sm border-0"
                                 style="background:#973D4B; color:#fff;">
                                <ul class="mb-0 small fw-bold">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('tasks.store') }}" method="POST">
                            @csrf

                            {{-- الصف الأول: نحط فيه العنوان والتصنيف --}}
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-lg-8">
                                    <label class="form-label fw-bold" style="color:#392015;">
                                        عنوان المهمة <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="title" class="form-control" required
                                           value="{{ old('title') }}"
                                           style="border-color:#D4AF91;">
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label class="form-label fw-bold" style="color:#392015;">
                                        التصنيف الفرعي <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="sub_category" class="form-control" required
                                           value="{{ old('sub_category') }}"
                                           style="border-color:#D4AF91;">
                                </div>
                            </div>

                            {{-- الصف الثاني: نختار الموظف وتاريخ الاستحقاق --}}
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-lg-6">
                                    <label class="form-label fw-bold" style="color:#392015;">
                                        إسناد إلى <span class="text-danger">*</span>
                                    </label>
                                    {{-- القائمة المنسدلة عشان تختار مين بيستلم المهمة --}}
                                    <select name="assigned_to" class="form-select" required
                                            style="border-color:#D4AF91;">
                                        <option value="">اختر المسؤول</option>

                                        @if(isset($cisoUser) && $cisoUser)
                                            <optgroup label="المدير العام">
                                                <option value="{{ $cisoUser->id }}">
                                                    {{ $cisoUser->name }} (CISO)
                                                </option>
                                            </optgroup>
                                        @endif

                                        @if($subordinates->count() > 0)
                                            <optgroup label="الموظفين التابعين">
                                                @foreach($subordinates as $sub)
                                                    <option value="{{ $sub->id }}">
                                                        {{ $sub->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif

                                        @if(isset($otherManagers) && $otherManagers->count() > 0)
                                            <optgroup label="مدراء الإدارات الأخرى">
                                                @foreach($otherManagers as $manager)
                                                    <option value="{{ $manager->id }}">
                                                        {{ $manager->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif

                                        @if($subordinates->isEmpty() && !$cisoUser)
                                            <option disabled>لا يوجد مستخدمين متاحين</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label fw-bold" style="color:#392015;">
                                        تاريخ الاستحقاق <span class="text-danger">*</span>
                                    </label>
                                    {{-- هنا التعديل: حطينا min بتاريخ اليوم عشان ما يختار تاريخ قديم --}}
                                    <input type="date" name="due_date" class="form-control" required
                                           value="{{ old('due_date') }}"
                                           min="{{ date('Y-m-d') }}"
                                           style="border-color:#D4AF91;">
                                </div>
                            </div>

                            {{-- الصف الثالث: خيارات الأولوية ومستوى التعقيد --}}
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-lg-6">
                                    <label class="form-label fw-bold" style="color:#392015;">الأولوية</label>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <label class="badge" style="background:#007B69; color:#fff;">
                                            <input type="radio" name="priority" value="low" checked> منخفضة
                                        </label>
                                        <label class="badge" style="background:#E3A778; color:#392015;">
                                            <input type="radio" name="priority" value="medium"> متوسطة
                                        </label>
                                        <label class="badge" style="background:#973D4B; color:#fff;">
                                            <input type="radio" name="priority" value="high"> عالية
                                        </label>
                                        <label class="badge" style="background:#392015; color:#fff;">
                                            <input type="radio" name="priority" value="critical"> حرج
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label fw-bold" style="color:#392015;">التعقيد</label>
                                    <select name="complexity" class="form-select" style="border-color:#D4AF91;">
                                        <option value="S">بسيط</option>
                                        <option value="M" selected>متوسط</option>
                                        <option value="L">معقد</option>
                                    </select>
                                </div>
                            </div>

                            {{-- مكان كتابة تفاصيل المهمة --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold" style="color:#392015;">تفاصيل المهمة</label>
                                <textarea name="description" class="form-control" rows="5"
                                          style="border-color:#D4AF91;">{{ old('description') }}</textarea>
                            </div>

                            {{-- زر الحفظ النهائي --}}
                            <div class="d-grid">
                                <button type="submit"
                                        class="btn fw-bold fs-5 shadow-sm"
                                        style="background:#007B69; border-color:#007B69; color:#fff;">
                                    <i class="bi bi-save me-2" style="color:#fff;"></i>
                                    حفظ المهمة
                                </button>
                            </div>

                        </form>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection