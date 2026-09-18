@extends('admin._layout')

@section('title', ($customer->adi ?? 'Müşteri') . ' — Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.musteriler.index') }}">Müşteriler</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.musteriler.show', $customer->id) }}">{{ $customer->adi ?? 'Müşteri' }}</a>
    <span class="sep">/</span>
    <span class="current">Düzenle</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ {{ $customer->adi ?? 'Müşteri' }} — Düzenle</h1>
        <div class="page-subtitle">ID #{{ $customer->id }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.musteriler.show', $customer->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Detaya Dön</span>
        </a>
    </div>
</div>

@include('admin.crm.customers._form', ['customer' => $customer, 'yoneticiler' => $yoneticiler ?? []])

@endsection