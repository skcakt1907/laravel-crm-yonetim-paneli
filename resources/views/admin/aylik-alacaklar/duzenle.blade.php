@extends('admin._layout')

@section('title', 'Alacak Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.aylik-alacaklar.index') }}">Aylık Bildirimli Alacaklar</a>
    <span class="sep">/</span>
    <span class="current">Düzenle</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $alacak->baslik }}</h1>
        <div class="page-subtitle">Alacak kaydını düzenle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.aylik-alacaklar.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> <span>Listeye Dön</span>
        </a>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

<form method="POST" action="{{ route('admin.aylik-alacaklar.guncelle', $alacak->id) }}">
    @csrf
    @method('PUT')

    <div class="section">
        <div class="section-title">
            <i data-lucide="pencil"></i>
            <span>Alacak Bilgileri</span>
        </div>
        @include('admin.aylik-alacaklar._form', ['alacak' => $alacak])
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="{{ route('admin.aylik-alacaklar.index') }}" class="btn btn-secondary"><i data-lucide="x"></i> <span>İptal</span></a>
        <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <span>Güncelle</span></button>
    </div>
</form>

@endsection
