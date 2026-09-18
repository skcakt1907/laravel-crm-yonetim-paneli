@extends('admin._layout')

@section('title', 'Mail Şablonları')

@push('head')
<style>
    .subj-cell {
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')

@php
    $aktifSayi = collect($templates)->where('aktif', 1)->count();
    $pasifSayi = collect($templates)->where('aktif', 0)->count();
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Mail Şablonları</span>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="mail"></i>
            Mail Şablonları
            <span class="badge badge-brand">{{ count($templates) }}</span>
        </h1>
        <div class="page-subtitle">Sistem mail içeriklerini yönet (sipariş, kayıt, bildirim vs.)</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.mail-templates.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Şablon</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,.12);color:var(--brand-dark)">
            <i data-lucide="mail"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Şablon</div>
            <div class="stat-card-value">{{ count($templates) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.12);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value">{{ $aktifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(107,114,128,.12);color:#6b7280">
            <i data-lucide="pause-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value">{{ $pasifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">
            <i data-lucide="settings"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Mail Ayarları</div>
            <div class="stat-card-value" style="font-size:14px;font-weight:600">
                <a href="{{ route('admin.ayarlar.mail') }}" style="color:#3b82f6">SMTP</a>
            </div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if(count($templates) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="mail-x" class="empty-state-icon"></i>
            <h4>Henüz şablon yok</h4>
            <p>Sipariş, kayıt veya bildirim mail içeriklerini hazırlamak için ilk şablonu oluşturun.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Şablon Adı</th>
                        <th>Slug</th>
                        <th>Konu</th>
                        <th>Durum</th>
                        <th>Güncelleme</th>
                        <th style="text-align:right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($templates as $t)
                    @php
                        $guncelFmt = '—';
                        if (!empty($t->updated_at)) {
                            try {
                                $guncelFmt = \Carbon\Carbon::parse($t->updated_at)->format('d.m.Y H:i');
                            } catch (\Throwable $e) {}
                        }
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:14px">
                                <i data-lucide="file-text" style="width:14px;height:14px;display:inline-block;vertical-align:-2px;color:var(--brand-dark);margin-right:4px"></i>
                                {{ $t->name ?? '—' }}
                            </div>
                        </td>
                        <td>
                            <code style="font-family:'JetBrains Mono',monospace;font-size:11px;background:var(--brand-soft);color:var(--brand-dark);padding:2px 8px;border-radius:6px">
                                {{ $t->slug ?? '—' }}
                            </code>
                        </td>
                        <td class="subj-cell" title="{{ $t->subject ?? '' }}">
                            {{ $t->subject ?? '—' }}
                        </td>
                        <td>
                            @if((int)($t->aktif ?? 0) === 1)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-neutral">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:12px;color:var(--text-muted)">{{ $guncelFmt }}</span>
                        </td>
                        <td>
                            <div class="table-actions" style="justify-content:flex-end">
                                <a href="{{ route('admin.mail-templates.edit', $t->id) }}"
                                   class="table-action" title="Düzenle">
                                    <i data-lucide="edit-3"></i>
                                </a>
                                <button type="button" class="table-action danger"
                                        onclick="document.getElementById('mt-sil-{{ $t->id }}').submit()"
                                        title="Sil">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                            <form id="mt-sil-{{ $t->id }}"
                                  action="{{ route('admin.mail-templates.destroy', $t->id) }}"
                                  method="POST" style="display:none"
                                  onsubmit="return confirm('Şablon silinsin mi?')">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection