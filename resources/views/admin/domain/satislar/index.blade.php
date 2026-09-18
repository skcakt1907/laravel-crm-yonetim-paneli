@extends('admin._layout')

@section('title', 'Domain Satışları (Klasik)')

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
        <div class="page-subtitle">Müşterilerinizin satın aldığı domain kayıtları</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.domain.fiyatlar.index'))
            <a href="{{ route('admin.domain.fiyatlar.index') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="dollar-sign"></i>
                <span>Fiyatlar</span>
            </a>
        @endif
        @if(Route::has('admin.satislar.domain'))
            <a href="{{ route('admin.satislar.domain') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="bar-chart-3"></i>
                <span>Tüm Satışlar</span>
            </a>
        @endif
    </div>
</div>

{{-- TABLO YOK UYARISI --}}
@if(!empty($tableMissing))
    <div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px">
        <i data-lucide="alert-triangle" style="width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
        <div>
            <strong>`domain_satislar` tablosu bulunamadı.</strong>
            <div style="margin-top:6px;font-size:13px">
                Domain satış kayıtları henüz oluşturulmamış. Genel satış kayıtları için
                @if(Route::has('admin.satislar.domain'))
                    <a href="{{ route('admin.satislar.domain') }}" style="color:var(--brand-dark);font-weight:600;text-decoration:underline">
                        Alan Adı Satışları
                    </a>
                @else
                    "Alan Adı Satışları"
                @endif
                sayfasını ziyaret edebilirsiniz.
            </div>
        </div>
    </div>
@endif

{{-- TABLO --}}
@if($satislar->isEmpty())
    @if(empty($tableMissing))
        <div class="section">
            <div class="empty-state">
                <i data-lucide="globe" class="empty-state-icon"></i>
                <h4>Henüz domain satışı yok</h4>
                <p>Müşterileriniz domain satın aldığında burada listelenecek.</p>
            </div>
        </div>
    @endif
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:80px">#</th>
                        <th>Domain</th>
                        <th>Tutar</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($satislar as $s)
                        @php
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
                                <span style="font-family:'SF Mono','Monaco',monospace;font-weight:600;color:var(--brand-dark)">
                                    {{ $s->domain ?? '—' }}
                                </span>
                            </td>
                            <td style="font-weight:700;color:var(--success)">
                                ₺{{ number_format((float)($s->tutar ?? 0), 2, ',', '.') }}
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                {{ $tFmt ?? '—' }}
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