@extends('admin._layout')

@section('title', 'Yeni Müşteri')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.musteriler.index') }}">Müşteriler</a>
    <span class="sep">/</span>
    <span class="current">Yeni Müşteri</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Müşteri</h1>
        <div class="page-subtitle">Yeni müşteri kaydı oluştur</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.musteriler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

@include('admin.crm.customers._form', ['customer' => null, 'yoneticiler' => $yoneticiler ?? []])

@endsection