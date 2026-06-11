@extends('admin.layouts.master')

@section('title', 'لوحة التحكم')

@section('content')
<div class="page-header">
    <h4>لوحة التحكم</h4>
    <p>مؤسسة الرواد للتعاون والتنمية</p>
</div>

{{-- النظام الأساسي --}}
<div class="d-flex align-items-center gap-2 mb-3">
    <div class="bg-primary" style="width: 4px; height: 24px; border-radius: 2px;"></div>
    <h5 class="mb-0 fw-bold">النظام الأساسي</h5>
</div>

<div class="row g-4 mb-5">
    @if ($centersCount !== null)
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="fs-1 me-3">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">{{ $centersCount }}</h2>
                        <small>المراكز</small>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.centers.index') }}" class="card-footer text-white text-decoration-none d-block small bg-dark bg-opacity-10">
                عرض التفاصيل <i class="bi bi-arrow-left me-1"></i>
            </a>
        </div>
    </div>
    @endif

    @if ($projectsCount !== null)
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="fs-1 me-3">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">{{ $projectsCount }}</h2>
                        <small>المشاريع</small>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="card-footer text-white text-decoration-none d-block small bg-dark bg-opacity-10">
                عرض التفاصيل <i class="bi bi-arrow-left me-1"></i>
            </a>
        </div>
    </div>
    @endif

    @if ($usersCount !== null)
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="fs-1 me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">{{ $usersCount }}</h2>
                        <small>المستخدمين</small>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="card-footer text-white text-decoration-none d-block small bg-dark bg-opacity-10">
                عرض التفاصيل <i class="bi bi-arrow-left me-1"></i>
            </a>
        </div>
    </div>
    @endif

    @if ($groupsCount !== null)
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="fs-1 me-3">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold">{{ $groupsCount }}</h2>
                        <small>المجموعات</small>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.groups.index') }}" class="card-footer text-white text-decoration-none d-block small bg-dark bg-opacity-10">
                عرض التفاصيل <i class="bi bi-arrow-left me-1"></i>
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
