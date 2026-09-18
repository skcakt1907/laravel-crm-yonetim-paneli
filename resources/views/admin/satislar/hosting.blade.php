@extends('admin._layout')

@section('title', 'Hosting Satışları')

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
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Hosting Satışları</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="server"></i>
            Hosting Satışları
            <span class="badge badge-brand">{{ $satislar->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşterilerinizin satın aldığı hosting paketleri</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.hosting.paketler.index'))
            <a href="{{ route('admin.hosting.paketler.index') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="package"></i>
                <span>Hosting Paketleri</span>
            </a>
        @endif
    </div>
</div>

{{-- TABLO YOK UYARISI --}}
@if(!empty($tableMissing))
    <div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px">
        <i data-lucide="alert-triangle" style="width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
        <div>
            <strong>Hosting satış tablosu bulunamadı.</strong>
            <div style="margin-top:6px;font-size:13px">
                <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">satilanlar</code>
                veya
                <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">hosting_satislar</code>
                tablosu gerekli.
            </div>
        </div>
    </div>
@endif

{{-- STAT KARTLARI --}}
@php
    $totalCount = $satislar->total();
    $totalCiro = 0;
    $ortTutar = 0;

    try {
        if (\Schema::hasTable('satilanlar')) {
            $totalRow = \DB::table('satilanlar')->where('hosting', '>', 0)
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
            <i data-lucide="server" class="empty-state-icon"></i>
            <h4>Henüz hosting satışı yok</h4>
            <p>Müşterileriniz hosting paketi satın aldığında burada listelenecek.</p>
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
                        <th>Ürün / Paket</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                        <th style="text-align:right;width:60px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satislar as $s)
                        @php
                            $ad = $s->ad ?? $s->musteri ?? '—';
                            $email = $s->musteri_email ?? $s->email ?? null;
                            $harf = mb_strtoupper(mb_substr($ad ?: '?', 0, 1, 'UTF-8'), 'UTF-8');

                            $urun = $s->urun ?? $s->paket_adi ?? $s->hosting_baslik ?? '—';
                            $tutarVal = (float)($s->tutar ?? 0);

                            $tFmt = null;
                            $rawT = $s->tarih ?? $s->created_at ?? null;
                            if (!empty($rawT)) {
                                try { $tFmt = \Carbon\Carbon::parse($rawT)->format('d.m.Y H:i'); }
                                catch (\Throwable $e) {}
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
                                    <i data-lucide="server" style="width:12px;height:12px"></i>
                                    {{ $urun }}
                                </span>
                            </td>
                            <td style="font-weight:700;color:var(--success)">
                                ₺{{ number_format($tutarVal, 2, ',', '.') }}
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                {{ $tFmt ?? '—' }}
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