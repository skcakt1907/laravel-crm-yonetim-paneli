@extends('admin._layout')

@section('title', 'Lokasyonlar')

@push('head')
<style>
    .hzm-swatch { width:18px; height:18px; border-radius:50%; display:inline-block; vertical-align:middle; border:1px solid rgba(0,0,0,.1); }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Lokasyonlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">📍 Lokasyonlar
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ count($hizmetler) }}</span>
        </h1>
        <div class="page-subtitle">Randevu lokasyonlarını yönet (ofisler, online kanallar)</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.randevu.hizmet-ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Lokasyon</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">
    <i data-lucide="check-circle"></i> {{ session('success') }}
</div>
@endif

@if(count($hizmetler) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="list" class="empty-state-icon"></i>
            <h4>Henüz lokasyon eklenmemiş</h4>
            <p>Randevularda kullanılacak lokasyonları ekle (ofis, Zoom, WhatsApp...).</p>
            <a href="{{ route('admin.randevu.hizmet-ekle') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i> <span>İlk Lokasyonu Ekle</span>
            </a>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Lokasyon Adı</th>
                        <th>Açıklama</th>
                        <th>Süre</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hizmetler as $h)
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $h->id }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <span class="hzm-swatch" style="background:{{ $h->renk ?? '#3b82f6' }}"></span>
                                    <span style="font-weight:600;color:var(--text)">{{ $h->ad }}</span>
                                </div>
                            </td>
                            <td style="font-size:13px;color:var(--text-secondary)">{{ $h->aciklama ?? '—' }}</td>
                            <td style="font-size:13px">{{ $h->sure_dk }} dk</td>
                            <td>
                                @if($h->durum == 1)
                                    <span class="badge badge-success">🟢 Aktif</span>
                                @else
                                    <span class="badge badge-danger">🔴 Pasif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.randevu.hizmet-duzenle', $h->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.randevu.hizmet-sil', $h->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($h->ad) }} silinsin mi?');" style="margin:0;display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection