@extends('admin._layout')

@section('title', 'Rolü Düzenle')

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.yoneticiler.index') }}">Yöneticiler</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.roller.index') }}">Roller</a>
    <span class="sep">/</span>
    <span class="current">{{ $rol->ad }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            {{ $rol->ikon ?? '🎭' }} {{ $rol->ad }}
            @if($rol->korumali)
                <span class="badge badge-danger" style="margin-left:8px">
                    <i data-lucide="shield" style="width:11px;height:11px"></i>
                    Korumalı
                </span>
            @endif
        </h1>
        <div class="page-subtitle">
            <span style="font-family:'JetBrains Mono',monospace">{{ $rol->slug }}</span>
            @if($rol->aciklama) — {{ $rol->aciklama }}@endif
        </div>
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
    <ul style="margin:0 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif

<form action="{{ route('admin.roller.duzenlePost', $rol->id) }}" method="POST">
    @csrf

    @include('admin.roller._form', [
        'rol' => $rol,
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
            <span>Değişiklikleri Kaydet</span>
        </button>
    </div>
</form>

@endsection