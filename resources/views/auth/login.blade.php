@extends('layouts.layout')

@section('content')


@vite([
    'resources/css/login.css',
    'resources/js/app.js'
])


<div class="login-page">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">

            <div class="glass-card">

                <div class="brand-section">
                    <img
                        src="{{ asset('images/Aseerlogo-white.png') }}"
                        alt="شعار الأمانة"
                        class="brand-logo"
                        width="400"
                        height="160"
                        decoding="async">

                    <p class="brand-subtitle">نظام إدارة المهام للإدارة العامة للأمن السيبراني</p>
                </div>

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-envelope me-2"></i> اسم المستخدم
                        </label>
                        <input
                            type="email"
                            name="email"
                            class="input-glass"
                            dir="ltr"
                            required
                            autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-key me-2"></i> رمز الدخول
                        </label>
                        <input
                            type="password"
                            name="password"
                            class="input-glass"
                            required
                            autocomplete="current-password">
                    </div>

                    @if ($errors->any())
                    <div class="alert-glass">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                    @endif

                    <button type="submit" class="btn-glass">
                        تسجيل الدخول <i class="bi bi-arrow-left-short"></i>
                    </button>
                </form>

                <div class="footer-links">
                    <p class="mb-2 text-white-50 small">هل تواجه مشكلة؟</p>
                    <a href="mailto:mbnq1@outlook.com">  دعم المطور </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection