@extends('admin._layout')
@section('title', 'Sanal Tur Hosting Düzenle')
@section('content')
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.sanaltur-hosting.index') }}">Sanal Tur Hosting Takip</a>
    <span class="sep">/</span>
    <span class="current">{{ $row->domain ?? 'Düzenle' }}</span>
</div>
<div class="page-header">
    <div>
        <h1 class="page-title">🎦 {{ $row->domain ?? '—' }}</h1>
        <div class="page-subtitle">
            Sanal tur hosting kaydını düzenle
            @if(!empty($row->uye_email))
                · <a href="{{ url('/admin/uyeler?search='.urlencode($row->uye_email)) }}" style="color:var(--brand);text-decoration:none">
                    {{ $row->uye_firma ?: trim(($row->uye_ad ?? '').' '.($row->uye_soyad ?? '')) ?: $row->uye_email }}
                </a>
            @endif
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.sanaltur-hosting.show', ['id' => $row->id]) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="eye"></i>
            <span>Detay</span>
        </a>
        <a href="{{ route('admin.crm.sanaltur-hosting.index') }}" class="btn btn-secondary btn-sm">
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
@include('admin.crm.sanaltur-hosting._form')
@endsection
