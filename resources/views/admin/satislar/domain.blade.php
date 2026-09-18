@extends('admin._layout')

@section('title', 'Domain Satışları')

@push('head')
<style>
    .musteri-cell { display: flex; align-items: center; gap: 10px; }
    .musteri-avatar {
        width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #000; font-weight: 700; font-size: 13px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .musteri-cell .name { font-weight: 600; font-size: 13.5px; line-height: 1.3; }
    .musteri-cell .sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .urun-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        border-radius: 6px;
        font-weight: 600;
        font-size: 12.5px;
        font-family: 'SF Mono','Monaco','Consolas',monospace;
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Domain Satışları</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="globe"></i>
            Domain Satışları
            <span class="badge badge-brand">{{ $satislar->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşterilerinizin satın aldığı alan adı satışları</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.domains.index'))
            <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="globe"></i>
                <span>Domain Yönetimi</span>
            </a>
        @endif
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $totalCount = $satislar->total();
    $totalCiro = 0;
    $ortTutar = 0;

    try {
        if (\Schema::hasTable('satilanlar')) {
            // Domain satışı: hosting=0 (veya null) VE paket=0 (veya null)
            $totalRow = \DB::table('satilanlar')
                ->where(function($q){ $q->whereNull('hosting')->orWhere('hosting', 0); })
                ->where(function($q){ $q->whereNull('paket')->orWhere('paket', 0); })
                ->selectRaw('COALESCE(SUM(tutar),0) AS toplam, COALESCE(AVG(tutar),0) AS ort')
                ->first();
            $totalCiro = (float)($totalRow->toplam ?? 0);
            $ortTutar  = (float)($totalRow->ort ?? 0);
        }
    } catch (\Throwable $e) {}
@endphp

<div class="stat-grid" style="margin-bottom:20px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="shopping-cart"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Satış</div>
            <div class="stat-card-value">{{ $totalCount }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Adet</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="wallet"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Ciro</div>
            <div class="stat-card-value" style="color:var(--success);font-size:22px">
                ₺{{ number_format($totalCiro, 0, ',', '.') }}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Tüm zamanlar</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Ortalama Tutar</div>
            <div class="stat-card-value" style="color:var(--brand-dark);font-size:22px">
                ₺{{ number_format($ortTutar, 0, ',', '.') }}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Satış başına</div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if($satislar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="globe" class="empty-state-icon"></i>
            <h4>Henüz domain satışı yok</h4>
            <p>Müşterileriniz alan adı satın aldığında burada listelenecek.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:80px">#</th>
                        <th>Müşteri</th>
                        <th>Alan Adı</th>
                        <th>Tutar</th>
                        <th>Başlangıç</th>
                        <th>Bitiş</th>
                        <th style="width:120px">Kalan</th>
                        <th style="text-align:right;width:60px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satislar as $s)
                        @php
                            $ad = $s->ad ?? $s->musteri ?? '—';
                            $email = $s->musteri_email ?? $s->email ?? null;
                            $harf = mb_strtoupper(mb_substr($ad ?: '?', 0, 1, 'UTF-8'), 'UTF-8');

                            $urun = $s->urun ?? $s->domain ?? '—';
                            $tutarVal = (float)($s->tutar ?? 0);

                            // Başlangıç tarihi - önce DB'deki olası field'lara bak
                            $tFmt = null;
                            $baslangicCarbon = null;
                            $baslangicRaw = $s->baslangic_tarihi ?? $s->tarih ?? $s->created_at ?? null;
                            if (!empty($baslangicRaw)) {
                                try {
                                    $baslangicCarbon = \Carbon\Carbon::parse($baslangicRaw);
                                    $tFmt = $baslangicCarbon->format('d.m.Y');
                                } catch (\Throwable $e) {}
                            }

                            // Bitiş tarihi - önce DB'deki olası field'lara bak
                            $bitisCarbon = null;
                            $bitisFmt = null;
                            $bitisOtomatik = false;
                            foreach (['bitis_tarihi','bitis','sona_erme','sona_erme_tarihi','expires_at'] as $bf) {
                                if (!empty($s->$bf ?? null)) {
                                    try {
                                        $bitisCarbon = \Carbon\Carbon::parse($s->$bf);
                                        $bitisFmt = $bitisCarbon->format('d.m.Y');
                                    } catch (\Throwable $e) {}
                                    break;
                                }
                            }
                            // Bulunamadıysa: başlangıç + 1 yıl
                            if (!$bitisCarbon && $baslangicCarbon) {
                                try {
                                    $bitisCarbon = $baslangicCarbon->copy()->addYear();
                                    $bitisFmt = $bitisCarbon->format('d.m.Y');
                                    $bitisOtomatik = true;
                                } catch (\Throwable $e) {}
                            }

                            // Kalan gün
                            $kalanGun = null;
                            $durum = null;
                            if ($bitisCarbon) {
                                $kalanGun = (int) floor(now()->diffInDays($bitisCarbon, false));
                                if ($kalanGun < 0)        $durum = 'bitti';
                                elseif ($kalanGun <= 30)  $durum = 'sona-eriyor';
                                else                       $durum = 'aktif';
                            }
                        @endphp
                        <tr>
                            <td>
                                <span style="font-family:monospace;font-size:12px;color:var(--text-muted)">
                                    #{{ $s->id }}
                                </span>
                            </td>
                            <td>
                                <div class="musteri-cell">
                                    <div class="musteri-avatar">{{ $harf }}</div>
                                    <div>
                                        <div class="name">{{ $ad ?: '—' }}</div>
                                        @if($email)
                                            <div class="sub">{{ $email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="urun-chip">
                                    <i data-lucide="globe" style="width:12px;height:12px"></i>
                                    {{ $urun }}
                                </span>
                            </td>
                            <td style="font-weight:700;color:var(--success)">
                                ₺{{ number_format($tutarVal, 2, ',', '.') }}
                            </td>
                            <td style="font-size:12.5px">
                                {{ $tFmt ?? '—' }}
                            </td>
                            <td style="font-size:12.5px">
                                @if($bitisFmt)
                                    <span @if($bitisOtomatik) title="Tahmini (+1 yıl)" style="border-bottom:1px dashed var(--text-muted);cursor:help" @endif>
                                        {{ $bitisFmt }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                @if($durum === 'aktif')
                                    <span class="badge badge-success">
                                        <i data-lucide="check-circle" style="width:11px;height:11px"></i>
                                        {{ $kalanGun }} gün
                                    </span>
                                @elseif($durum === 'sona-eriyor')
                                    <span class="badge badge-warning">
                                        <i data-lucide="alert-triangle" style="width:11px;height:11px"></i>
                                        {{ $kalanGun }} gün
                                    </span>
                                @elseif($durum === 'bitti')
                                    <span class="badge badge-danger">
                                        <i data-lucide="x-circle" style="width:11px;height:11px"></i>
                                        Doldu
                                    </span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="text-align:right">
                                @if(Route::has('admin.satislar.detay'))
                                    <a href="{{ route('admin.satislar.detay', $s->id) }}"
                                       class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($satislar->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:13px;color:var(--text-muted)">
                    {{ $satislar->firstItem() ?? 0 }} – {{ $satislar->lastItem() ?? 0 }} / Toplam
                    <strong style="color:var(--text)">{{ $satislar->total() }}</strong>
                </div>
                {{ $satislar->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endif

@endsection