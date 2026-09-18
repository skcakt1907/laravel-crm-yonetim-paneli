@extends('admin._layout')

@section('title', 'Roller')

@push('head')
<style>
    .rol-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 18px;
        transition: all .15s ease;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .rol-card:hover {
        border-color: var(--brand);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,.06);
    }
    .rol-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 14px;
    }
    .rol-head {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .rol-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        flex-shrink: 0;
    }
    .rol-name {
        font-weight: 700;
        font-size: 16px;
        line-height: 1.2;
    }
    .rol-slug {
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .rol-desc {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
        min-height: 40px;
    }
    .rol-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px dashed var(--border);
        gap: 8px;
    }
    .rol-actions {
        display: flex;
        gap: 6px;
    }
</style>
@endpush

@section('content')

@php
    $rolRenkMap = [
        'primary'   => ['bg' => 'rgba(184,182,46,.15)',  'color' => '#8a8a1f'],
        'success'   => ['bg' => 'rgba(16,185,129,.15)',  'color' => '#10b981'],
        'danger'    => ['bg' => 'rgba(239,68,68,.15)',   'color' => '#ef4444'],
        'warning'   => ['bg' => 'rgba(245,158,11,.15)',  'color' => '#f59e0b'],
        'info'      => ['bg' => 'rgba(59,130,246,.15)',  'color' => '#3b82f6'],
        'dark'      => ['bg' => 'rgba(31,41,55,.15)',    'color' => '#1f2937'],
        'secondary' => ['bg' => 'rgba(107,114,128,.15)', 'color' => '#6b7280'],
    ];
    $aktifSayi = collect($roller)->where('durum', 1)->count();
    $korumaliSayi = collect($roller)->where('korumali', 1)->count();
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.yoneticiler.index') }}">Yöneticiler</a>
    <span class="sep">/</span>
    <span class="current">Roller</span>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="key-round"></i>
            Roller
            <span class="badge badge-brand">{{ count($roller) }}</span>
        </h1>
        <div class="page-subtitle">Yönetici rolleri ve sayfa bazlı yetki tanımları</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.yoneticiler.index') }}" class="btn btn-secondary">
            <i data-lucide="users"></i>
            <span>Yöneticiler</span>
        </a>
        <a href="{{ route('admin.roller.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Rol</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,.12);color:var(--brand-dark)">
            <i data-lucide="key-round"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Rol</div>
            <div class="stat-card-value">{{ count($roller) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.12);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif Rol</div>
            <div class="stat-card-value">{{ $aktifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,.12);color:#ef4444">
            <i data-lucide="shield"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Korumalı</div>
            <div class="stat-card-value">{{ $korumaliSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">
            <i data-lucide="user-cog"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Yönetici Sayısı</div>
            <div class="stat-card-value">{{ \DB::table('yoneticiler')->count() }}</div>
        </div>
    </div>
</div>

{{-- ROL KARTLARI --}}
@if(count($roller) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="key-round" class="empty-state-icon"></i>
            <h4>Henüz rol yok</h4>
            <p>Yönetici yetkilendirmesi için ilk rolü oluşturun.</p>
        </div>
    </div>
@else
    <div class="rol-grid">
        @foreach($roller as $r)
            @php
                $renk = $rolRenkMap[$r->renk ?? 'primary'] ?? $rolRenkMap['primary'];
                $kullananSayisi = \DB::table('yoneticiler')->where('rol', $r->id)->count();
            @endphp
            <div class="rol-card">
                <div class="rol-head">
                    <div class="rol-icon" style="background:{{ $renk['bg'] }};color:{{ $renk['color'] }}">
                        {{ $r->ikon ?? '🎭' }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div class="rol-name">
                            {{ $r->ad }}
                            @if($r->korumali)
                                <i data-lucide="shield-check" style="width:14px;height:14px;color:#ef4444;display:inline-block;vertical-align:-2px;margin-left:4px" title="Korumalı"></i>
                            @endif
                        </div>
                        <div class="rol-slug">{{ $r->slug }}</div>
                    </div>
                </div>

                <div class="rol-desc">
                    {{ $r->aciklama ?: 'Açıklama girilmemiş.' }}
                </div>

                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    @if($r->korumali)
                        <span class="badge badge-danger">
                            <i data-lucide="shield" style="width:11px;height:11px"></i>
                            Korumalı
                        </span>
                        <span class="badge badge-brand">
                            <i data-lucide="infinity" style="width:11px;height:11px"></i>
                            Tüm Yetkiler
                        </span>
                    @else
                        <span class="badge badge-brand">
                            <i data-lucide="check-square" style="width:11px;height:11px"></i>
                            {{ (int)($r->yetki_sayisi ?? 0) }} yetki
                        </span>
                    @endif
                    @if($r->durum)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-neutral">Pasif</span>
                    @endif
                    @if($kullananSayisi > 0)
                        <span class="badge badge-neutral">{{ $kullananSayisi }} yönetici</span>
                    @endif
                </div>

                <div class="rol-foot">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-muted)">
                        #{{ $r->id }}
                    </span>
                    <div class="rol-actions">
                        <a href="{{ route('admin.roller.duzenle', $r->id) }}"
                           class="table-action" title="Düzenle">
                            <i data-lucide="edit-3"></i>
                        </a>
                        @if(!$r->korumali)
                            <button type="button" class="table-action danger"
                                    onclick="document.getElementById('rol-sil-{{ $r->id }}').submit()"
                                    title="Sil">
                                <i data-lucide="trash-2"></i>
                            </button>
                            <form id="rol-sil-{{ $r->id }}"
                                  action="{{ route('admin.roller.sil', $r->id) }}"
                                  method="POST" style="display:none"
                                  onsubmit="return confirm('Rol silinsin mi? Bu role atanmış yönetici varsa silme başarısız olacaktır.')">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection