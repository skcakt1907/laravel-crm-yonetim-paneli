@extends('admin._layout')

@section('title', 'Bayilik Satışları')

@push('head')
<style>
    .bayi-cell { display: flex; align-items: center; gap: 10px; }
    .bayi-avatar-sm {
        width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #000; font-weight: 700; font-size: 13px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bayi-cell .name { font-weight: 600; font-size: 13.5px; line-height: 1.3; }
    .bayi-cell .sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

    .money-pos { color: var(--success); font-weight: 700; }
    .money-brand { color: var(--brand-dark); font-weight: 700; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Bayilik Satışları</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="bar-chart-3"></i>
            Bayilik Satışları
            <span class="badge badge-brand">{{ $satislar->total() }}</span>
        </h1>
        <div class="page-subtitle">Bayilerinizin yaptığı tüm satışları ve komisyonları görüntüleyin</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.bayiler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="store"></i>
            <span>Bayilere Git</span>
        </a>
    </div>
</div>

{{-- 4 STAT KARTI --}}
@php
    $totalCount = $satislar->total();

    // Sayfadaki/tüm satışlardan hesaplanabilir (DB'den tek seferde alalım)
    $stats = ['toplam_tutar' => 0, 'toplam_komisyon' => 0, 'bu_ay_count' => 0, 'bu_ay_tutar' => 0];
    try {
        if (\Schema::hasTable('satilanlar')) {
            $toplamSatir = \DB::table('satilanlar')
                ->selectRaw('COALESCE(SUM(satis_tutari),0) AS t_tutar, COALESCE(SUM(komisyon_tutari),0) AS t_kom')
                ->first();
            if ($toplamSatir) {
                $stats['toplam_tutar']    = (float)($toplamSatir->t_tutar ?? 0);
                $stats['toplam_komisyon'] = (float)($toplamSatir->t_kom ?? 0);
            }

            // Bu ay (önce tarih, sonra created_at fallback)
            $cols = \Schema::getColumnListing('satilanlar');
            $dateCol = in_array('created_at', $cols) ? 'created_at' : (in_array('tarih', $cols) ? 'tarih' : null);
            if ($dateCol) {
                $baslangic = now()->startOfMonth()->toDateTimeString();
                if ($dateCol === 'tarih') {
                    $buAySatir = \DB::table('satilanlar')
                        ->whereRaw("STR_TO_DATE(tarih, '%Y-%m-%d %H:%i:%s') >= ?", [$baslangic])
                        ->selectRaw('COUNT(*) AS adet, COALESCE(SUM(satis_tutari),0) AS tutar')
                        ->first();
                } else {
                    $buAySatir = \DB::table('satilanlar')
                        ->where($dateCol, '>=', $baslangic)
                        ->selectRaw('COUNT(*) AS adet, COALESCE(SUM(satis_tutari),0) AS tutar')
                        ->first();
                }
                if ($buAySatir) {
                    $stats['bu_ay_count'] = (int)($buAySatir->adet ?? 0);
                    $stats['bu_ay_tutar'] = (float)($buAySatir->tutar ?? 0);
                }
            }
        }
    } catch (\Throwable $e) {}
@endphp

<div class="stat-grid" style="margin-bottom:20px">
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
                ₺{{ number_format($stats['toplam_tutar'], 0, ',', '.') }}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Tüm zamanlar</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="hand-coins"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Komisyon</div>
            <div class="stat-card-value" style="color:var(--brand-dark);font-size:22px">
                ₺{{ number_format($stats['toplam_komisyon'], 0, ',', '.') }}
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Bayilere ödenmiş/ödenecek</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay</div>
            <div class="stat-card-value">{{ $stats['bu_ay_count'] }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                ₺{{ number_format($stats['bu_ay_tutar'], 0, ',', '.') }} ciro
            </div>
        </div>
    </div>
</div>

{{-- FİLTRE BARI --}}
<form method="GET" class="section" style="padding:14px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end">
        <div>
            <label class="form-label">Ara</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   class="form-input" placeholder="Bayi adı, e-posta veya bayi kodu...">
        </div>
        <div>
            <label class="form-label">Başlangıç</label>
            <input type="date" name="bas" value="{{ request('bas') }}" class="form-input">
        </div>
        <div>
            <label class="form-label">Bitiş</label>
            <input type="date" name="bit" value="{{ request('bit') }}" class="form-input">
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="search"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['q','bas','bit']))
                <a href="{{ route('admin.bayilik.satislar') }}" class="btn btn-ghost btn-sm" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- TABLO --}}
@if($satislar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="bar-chart-3" class="empty-state-icon"></i>
            <h4>Henüz satış yok</h4>
            <p>Bayileriniz satış yapmaya başladığında burada listelenecek.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:80px">#</th>
                        <th>Bayi</th>
                        <th>Satış Tutarı</th>
                        <th>Komisyon</th>
                        <th>Komisyon %</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satislar as $s)
                        @php
                            $bayiAdi = $s->bayi_adi ?? '—';
                            $ad = trim(($s->ad ?? '') . ' ' . ($s->soyad ?? ''));
                            $harf = $ad
                                ? mb_strtoupper(mb_substr($ad, 0, 1, 'UTF-8'), 'UTF-8')
                                : '?';

                            $satisT = (float)($s->satis_tutari ?? 0);
                            $komisyonT = (float)($s->komisyon_tutari ?? 0);
                            $komisyonY = $satisT > 0 ? round(($komisyonT / $satisT) * 100, 2) : 0;

                            $tarihFmt = null;
                            $rawT = $s->tarih ?? $s->created_at ?? null;
                            if (!empty($rawT)) {
                                try {
                                    $tarihFmt = \Carbon\Carbon::parse($rawT)->format('d.m.Y H:i');
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr>
                            <td>
                                <span style="font-family:monospace;font-size:12px;color:var(--text-muted)">
                                    #{{ $s->id }}
                                </span>
                            </td>
                            <td>
                                <div class="bayi-cell">
                                    <div class="bayi-avatar-sm">{{ $harf }}</div>
                                    <div>
                                        <div class="name">{{ $bayiAdi }}</div>
                                        @if(!empty($s->email))
                                            <div class="sub">{{ $s->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="money-pos">₺{{ number_format($satisT, 2, ',', '.') }}</span>
                            </td>
                            <td>
                                <span class="money-brand">₺{{ number_format($komisyonT, 2, ',', '.') }}</span>
                            </td>
                            <td>
                                @if($komisyonY > 0)
                                    <span class="badge badge-brand">%{{ number_format($komisyonY, 2) }}</span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                {{ $tarihFmt ?? '—' }}
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