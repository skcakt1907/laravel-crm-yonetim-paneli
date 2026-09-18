@extends('admin._layout')

@section('title', 'Fırsat Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.firsatlar.index') }}">Fırsatlar</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.firsatlar.show', $opportunity->id) }}">{{ Str::limit($opportunity->baslik, 30) }}</a>
    <span class="sep">/</span>
    <span class="current">Düzenle</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ {{ $opportunity->baslik }}</h1>
        <div class="page-subtitle">Fırsat bilgilerini güncelle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.firsatlar.show', $opportunity->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="eye"></i>
            <span>Detay</span>
        </a>
        <a href="{{ route('admin.crm.firsatlar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">
        <strong>Form Hataları:</strong>
        <ul style="margin:6px 0 0 18px;font-size:13px">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('admin.crm.opportunities._form')

@endsection