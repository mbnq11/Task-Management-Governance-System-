<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام المهام السيبراني</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    @vite(['resources/css/style.css', 'resources/js/app.js'])

</head>

<body>
    @auth
    <nav class="navbar navbar-expand-lg navbar-dark amanah-navbar sticky-top">
        <div class="container">

            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <div class="amanah-logo-wrap">
                    <img src="{{ asset('images/Aseerlogo-white.png') }}"
                         alt="شعار أمانة عسير"
                         class="amanah-logo">
                </div>
            </a>

            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="d-flex align-items-center text-white">
                    <div class="nav-profile-badge rounded-circle ms-2">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div style="line-height: 1.2;">
                        <div class="small fw-bold">{{ Auth::user()->name }}</div>
                        <div style="font-size: 0.75rem;" class="text-white-50">
                            {{ Auth::user()->role }}
                        </div>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>

        </div>
    </nav>
    @endauth

    <div class="container main-container">

        @auth
        @if(!request()->routeIs('dashboard'))
        <div class="mb-3">
            <a href="{{ url()->previous() == url()->current() ? route('dashboard') : url()->previous() }}"
               class="btn btn-back shadow-sm d-inline-flex align-items-center justify-content-center"
               title="رجوع"
               style="text-decoration: none;">
                <i class="bi bi-arrow-right fs-5"></i>
            </a>
        </div>
        @endif
        @endauth

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
