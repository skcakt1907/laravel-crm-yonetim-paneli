@extends('admin._layout')

@section('title', 'Domain Siparişleri')

@push('head')
<style>
    .domain-cell {
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-weight: 700;
        color: var(--brand-dark);
        font-size: 14px;
    }

    .reseller-id {
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Domain Siparişleri</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="package"></i>
            Domain Siparişleri
            <span class="badge badge-brand">{{ $orders->total() }}</span>
        </h1>
        <div class="page-subtitle">ResellerClub API üzerinden alınan domain kayıt siparişleri</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.domain.fiyatlar.index'))
            <a href="{{ route('admin.domain.fiyatlar.index') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="dollar-sign"></i>
                <span>Fiyatlar</span>
            </a>
        @endif
    </div>
</div>

{{-- 4 STATUS STAT --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Beklemede</div>
            <div class="stat-card-value" style="color:var(--warning)">{{ $stats['pending'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Ödeme bekliyor</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="credit-card"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Ödendi</div>
            <div class="stat-card-value" style="color:#3b82f6">{{ $stats['paid'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">İşlem hazır</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $stats['active'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Tescil edildi</div>
        </div>
    </div>

    <div class="stat-card" style="@if(($stats['failed'] ?? 0) > 0)border-color:rgba(239,68,68,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Başarısız</div>
            <div class="stat-card-value" style="@if(($stats['failed'] ?? 0) > 0)color:var(--danger)@endif">
                {{ $stats['failed'] ?? 0 }}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Manuel müdahale</div>
        </div>
    </div>
</div>

{{-- FİLTRE BARI --}}
<form method="GET" class="section" style="padding:14px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:10px;align-items:end">
        <div>
            <label class="form-label">Ara</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-input" placeholder="Domain adı veya ResellerClub ID...">
        </div>
        <div>
            <label class="form-label">Durum</label>
            <select name="status" class="form-select">
                <option value="">Tümü</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Beklemede</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Ödendi</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Başarısız</option>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="search"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('admin.domain-orders.index') }}" class="btn btn-ghost btn-sm" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- TABLO --}}
@if($orders->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="package-x" class="empty-state-icon"></i>
            <h4>Sipariş bulunamadı</h4>
            <p>
                @if(request()->hasAny(['search','status']))
                    Arama kriterlerine uygun sipariş yok. Filtreyi temizleyip tekrar deneyin.
                @else
                    Henüz domain siparişi yok. Müşteriler domain satın aldığında burada görünecek.
                @endif
            </p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Domain</th>
                        <th>Müşteri</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th style="text-align:right;width:60px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $o)
                        @php
                            $user = $o->user ?? null;
                            $musteriAd = $user
                                ? trim(($user->ad ?? '') . ' ' . ($user->soyad ?? ''))
                                : '—';
                            $musteriAd = $musteriAd ?: '—';
                            $musteriEmail = $user->email ?? null;

                            $tFmt = null;
                            try {
                                if ($o->created_at) $tFmt = \Carbon\Carbon::parse($o->created_at)->format('d.m.Y H:i');
                            } catch (\Throwable $e) {}

                            $st = $o->status ?? 'pending';
                            $statusCfg = match($st) {
                                'pending'  => ['cls' => 'badge-warning', 'ic' => 'clock',        'txt' => 'Beklemede'],
                                'paid'     => ['cls' => 'badge-brand',   'ic' => 'credit-card', 'txt' => 'Ödendi'],
                                'active'   => ['cls' => 'badge-success', 'ic' => 'check',       'txt' => 'Aktif'],
                                'failed'   => ['cls' => 'badge-danger',  'ic' => 'x',           'txt' => 'Başarısız'],
                                default    => ['cls' => 'badge-neutral', 'ic' => 'circle',      'txt' => $st],
                            };
                        @endphp
                        <tr>
                            <td>
                                <span style="font-family:monospace;font-size:12px;color:var(--text-muted)">
                                    #{{ $o->id }}
                                </span>
                            </td>
                            <td>
                                <div class="domain-cell">{{ $o->domain ?? '—' }}</div>
                                @if(!empty($o->reseller_order_id))
                                    <div class="reseller-id">
                                        <i data-lucide="link" style="width:10px;height:10px;display:inline;vertical-align:middle"></i>
                                        RC: {{ $o->reseller_order_id }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:13.5px">{{ $musteriAd }}</div>
                                @if($musteriEmail)
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ $musteriEmail }}</div>
                                @endif
                            </td>
                            <td>
                                @if(isset($o->price) || isset($o->tutar))
                                    <span style="font-weight:700;color:var(--success)">
                                        ₺{{ number_format((float)($o->price ?? $o->tutar ?? 0), 2, ',', '.') }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusCfg['cls'] }}">
                                    <i data-lucide="{{ $statusCfg['ic'] }}" style="width:11px;height:11px"></i>
                                    {{ $statusCfg['txt'] }}
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                {{ $tFmt ?? '—' }}
                            </td>
                            <td style="text-align:right">
                                <a href="{{ route('admin.domain-orders.show', $o->id) }}"
                                   class="table-action" title="Detay">
                                    <i data-lucide="eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:13px;color:var(--text-muted)">
                    {{ $orders->firstItem() ?? 0 }} – {{ $orders->lastItem() ?? 0 }} / Toplam
                    <strong style="color:var(--text)">{{ $orders->total() }}</strong>
                </div>
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endif

@endsection