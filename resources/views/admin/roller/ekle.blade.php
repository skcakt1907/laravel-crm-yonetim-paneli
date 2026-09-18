@extends('admin._layout')

@section('title', 'Yeni Rol')

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.yoneticiler.index') }}">Yöneticiler</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.roller.index') }}">Roller</a>
    <span class="sep">/</span>
    <span class="current">Yeni Rol</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="plus-circle"></i>
            Yeni Rol Oluştur
        </h1>
        <div class="page-subtitle">Sayfa bazlı yetki tanımı içeren yeni bir rol oluşturun</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.roller.index') }}" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <strong>Hata:</strong>
    <ul style="margin:6px 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

<form action="{{ route('admin.roller.eklePost') }}" method="POST">
    @csrf

    @include('admin.roller._form', [
        'rol' => null,
        'kategoriler' => $kategoriler,
        'mevcutYetkiler' => $mevcutYetkiler ?? []
    ])

    {{-- BUTONLAR --}}
    <div class="form-actions-sticky">
        <a href="{{ route('admin.roller.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Rolü Oluştur</span>
        </button>
    </div>
</form>

@endsection