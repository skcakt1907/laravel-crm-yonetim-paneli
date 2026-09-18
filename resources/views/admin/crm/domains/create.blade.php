@extends('admin._layout')

@section('title', 'Yeni Domain Ekle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.domains.index') }}">Domain &amp; Hosting Takip</a>
    <span class="sep">/</span>
    <span class="current">Yeni Domain</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Domain Ekle</h1>
        <div class="page-subtitle">Manuel olarak bir domain kaydını sisteme ekle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.domains.index') }}" class="btn btn-secondary btn-sm">
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

@include('admin.crm.domains._form')

@endsection