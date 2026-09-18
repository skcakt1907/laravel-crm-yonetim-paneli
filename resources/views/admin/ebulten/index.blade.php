@extends('admin._layout')

@section('title', 'E-Bülten Aboneleri')

@section('content')

@php
    $aktifSayi = $aboneler->where('durum', 1)->count();
    $pasifSayi = $aboneler->where('durum', 0)->count();
    $buAySayi = 0;
    try {
        $buAySayi = \DB::table('ebulten_aboneler')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    } catch (\Throwable $e) {
        try {
            $buAySayi = \DB::table('ebulten_aboneler')
                ->whereYear('tarih', now()->year)
                ->whereMonth('tarih', now()->month)
                ->count();
        } catch (\Throwable $e2) {}
    }
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">E-Bülten</span>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="mailbox"></i>
            E-Bülten Aboneleri
            <span class="badge badge-brand">{{ $aboneler->total() }}</span>
        </h1>
        <div class="page-subtitle">Bülten abonelerini yönet ve toplu mail gönder</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.ebulten.toplu-mail') }}" class="btn btn-primary">
            <i data-lucide="send"></i>
            <span>Toplu Mail Gönder</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,.12);color:var(--brand-dark)">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Abone</div>
            <div class="stat-card-value">{{ $aboneler->total() }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.12);color:#10b981">
            <i data-lucide="user-check"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value">{{ $aktifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,.12);color:#ef4444">
            <i data-lucide="user-x"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value">{{ $pasifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay</div>
            <div class="stat-card-value">{{ $buAySayi }}</div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if($aboneler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="mailbox" class="empty-state-icon"></i>
            <h4>Henüz abone yok</h4>
            <p>Bülten aboneleri ana sayfa abonelik formundan kayıt olur.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>E-posta</th>
                        <th>Durum</th>
                        <th>Kayıt Tarihi</th>
                        <th style="text-align:right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($aboneler as $a)
                    @php
                        $tarFmt = '—';
                        $tarFld = $a->created_at ?? $a->tarih ?? null;
                        if (!empty($tarFld)) {
                            try {
                                $tarFmt = is_numeric($tarFld)
                                    ? \Carbon\Carbon::createFromTimestamp((int)$tarFld)->format('d.m.Y H:i')
                                    : \Carbon\Carbon::parse($tarFld)->format('d.m.Y H:i');
                            } catch (\Throwable $e) {}
                        }
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <i data-lucide="mail" style="width:16px;height:16px;color:var(--brand-dark)"></i>
                                <span style="font-weight:500">{{ $a->email }}</span>
                            </div>
                        </td>
                        <td>
                            @if((int)($a->durum ?? 1) === 1)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-neutral">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:12px;color:var(--text-muted)">{{ $tarFmt }}</span>
                        </td>
                        <td>
                            <div class="table-actions" style="justify-content:flex-end">
                                <a href="mailto:{{ $a->email }}" class="table-action" title="Mail Gönder">
                                    <i data-lucide="send"></i>
                                </a>
                                <button type="button" class="table-action danger"
                                        onclick="document.getElementById('eb-sil-{{ $a->id }}').submit()"
                                        title="Sil">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                            <form id="eb-sil-{{ $a->id }}"
                                  action="{{ route('admin.ebulten.sil', $a->id) }}"
                                  method="POST" style="display:none"
                                  onsubmit="return confirm('Abone listeden silinsin mi?')">
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

    <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div style="font-size:13px;color:var(--text-muted)">
            <strong>{{ $aboneler->firstItem() }}</strong> - <strong>{{ $aboneler->lastItem() }}</strong> arası,
            toplam <strong>{{ $aboneler->total() }}</strong> abone
        </div>
        <div>{{ $aboneler->links() }}</div>
    </div>
@endif

@endsection