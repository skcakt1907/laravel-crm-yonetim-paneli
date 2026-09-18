@extends('admin._layout')

@section('title', 'Bekleyen Faturalar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.faturalar.index') }}">Faturalar</a>
    <span class="sep">/</span>
    <span class="current">Bekleyen</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">⏳ Bekleyen Faturalar</h1>
        <div class="page-subtitle">Ödeme bekleyen faturalar listesi</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.faturalar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="list"></i>
            <span>Tüm Faturalar</span>
        </a>
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.onaylanan'))
        <a href="{{ route('admin.faturalar.onaylanan') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="check-circle"></i>
            <span>Onaylananlar</span>
        </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.export'))
        <a href="{{ route('admin.faturalar.export', 'bekleyen') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="download"></i>
            <span>Excel</span>
        </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.ekle'))
        <a href="{{ route('admin.faturalar.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Fatura</span>
        </a>
        @endif
    </div>
</div>

{{-- ═══ 3 Periyot Kart (Bu Ay / Seçilen / Hepsi) ═══ --}}
<div class="mini-stat-grid" style="grid-template-columns:repeat(3,1fr)">

    @php $aylar = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; @endphp

    {{-- Bu ay --}}
    <a href="?period=this_month" class="mini-stat warning" style="text-decoration:none;display:block;border-left-width:{{ $filter === 'this_month' ? '6px' : '3px' }}">
        <div class="lbl">🗓️ Bu Ay Bekleyen</div>
        <div class="val" style="color:var(--warning)">₺{{ number_format($stats['this_month']['total'] ?? 0, 2, ',', '.') }}</div>
        <div class="sub">{{ $stats['this_month']['count'] ?? 0 }} fatura</div>
    </a>

    {{-- Seçilen ay/yıl --}}
    <div class="mini-stat info" style="border-left-width:{{ $filter === 'picked' ? '6px' : '3px' }}">
        <div class="lbl">📅 Seçilen Dönem Bekleyen</div>
        <div class="val" style="color:var(--info)">₺{{ number_format($stats['picked']['total'] ?? 0, 2, ',', '.') }}</div>
        <div class="sub">{{ $aylar[$pickMonth] ?? '' }} {{ $pickYear }} · {{ $stats['picked']['count'] ?? 0 }} fatura</div>

        <form method="GET" action="" style="display:flex;gap:4px;margin-top:8px">
            <input type="hidden" name="period" value="picked">
            <select name="month" class="form-select" style="font-size:11px;padding:4px 6px">
                @foreach($aylar as $i => $ad)
                    @if($i > 0)
                        <option value="{{ $i }}" {{ $pickMonth == $i ? 'selected' : '' }}>{{ $ad }}</option>
                    @endif
                @endforeach
            </select>
            <select name="year" class="form-select" style="font-size:11px;padding:4px 6px">
                @foreach($mevcutYillar ?? [] as $y)
                    <option value="{{ $y }}" {{ $pickYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm" style="padding:4px 8px;font-size:11px">Göster</button>
        </form>
    </div>

    {{-- Tüm zamanlar --}}
    <a href="?period=all" class="mini-stat" style="text-decoration:none;display:block;border-left-width:{{ $filter === 'all' ? '6px' : '3px' }}">
        <div class="lbl">📊 Toplam Bekleyen</div>
        <div class="val">₺{{ number_format($stats['all']['total'] ?? 0, 2, ',', '.') }}</div>
        <div class="sub">{{ $stats['all']['count'] ?? 0 }} fatura</div>
    </a>
</div>

{{-- ═══ Arama ═══ --}}
<form action="" method="GET" class="section" style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
    @foreach(request()->except(['ara','page']) as $k => $v)
        @if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <input type="text" name="ara" value="{{ request('ara') }}" class="form-input" style="flex:1"
           placeholder="Fatura no, başlık, müşteri veya e-posta ara...">
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="search"></i> <span>Ara</span></button>
    @if(request('ara'))
        <a href="{{ route('admin.faturalar.bekleyen', request()->except(['ara','page'])) }}" class="btn btn-secondary btn-sm">Temizle</a>
    @endif
</form>

{{-- ═══ Tablo ═══ --}}
<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table" style="min-width:900px">
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Müşteri</th>
                    <th>Başlık</th>
                    <th>Tutar</th>
                    <th>Kesim Tarihi</th>
                    <th>Son Ödeme Tarihi</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faturalar ?? [] as $f)
                    @php
                        $detayUrl = \Illuminate\Support\Facades\Route::has('admin.faturalar.detay')
                            ? route('admin.faturalar.detay', $f->id) : '#';
                        $vadeBitis = $f->bitis_tarih ?? null;
                        $gecikme = $vadeBitis && strtotime($vadeBitis) < time();
                    @endphp
                    <tr class="clickable fatura-row {{ $gecikme ? 'taksit-row gecikme' : '' }}" data-url="{{ $detayUrl }}">
                        <td style="font-family:monospace;font-size:12px">
                            <strong>#{{ $f->fatura_no ?? 'FAT-' . str_pad($f->id, 6, '0', STR_PAD_LEFT) }}</strong>
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $f->ad ?? '' }} {{ $f->soyad ?? '' }}</div>
                            @if(!empty($f->email))<div style="font-size:11px;color:var(--text-muted)">{{ $f->email }}</div>@endif
                        </td>
                        <td style="color:var(--text-secondary)">{{ \Illuminate\Support\Str::limit($f->baslik ?? '—', 40) }}</td>
                        <td>
                            @if(!empty($f->para_birimi ?? null) && ($f->para_birimi ?? 'TL') !== 'TL' && !empty($f->doviz_tutar ?? null))
                                <strong>{{ number_format((float) $f->doviz_tutar, 2, ',', '.') }} {{ $f->para_birimi }}</strong>
                                <div style="font-size:11px;color:var(--text-muted)">≈ ₺{{ number_format((float) ($f->tutar ?? 0), 2, ',', '.') }}</div>
                            @else
                                <strong>₺{{ number_format((float) ($f->tutar ?? 0), 2, ',', '.') }}</strong>
                            @endif
                        </td>
                        {{-- KESİM TARİHİ = faturanın kesildiği gün (faturalar.tarih) --}}
                        <td style="font-size:12px;color:var(--text-muted)">
                            @if(!empty($f->tarih)){{ \Carbon\Carbon::parse($f->tarih)->format('d.m.Y') }}@else — @endif
                        </td>
                        {{-- SON ÖDEME TARİHİ = ödeme vadesi (faturalar.bitis_tarih) --}}
                        <td style="font-size:12px">
                            @if(!empty($vadeBitis))
                                {{ \Carbon\Carbon::parse($vadeBitis)->format('d.m.Y') }}
                                @if($gecikme)<span class="badge badge-danger" style="font-size:10px">⚠️ Geçti</span>@endif
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td class="text-right no-row-click" onclick="event.stopPropagation()">
                            <div class="table-actions">
                                {{-- HIZLI ÖDEME BİLDİRİMİ — tek tıkla müşteriye hatırlatma --}}
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.hatirlat'))
                                <form action="{{ route('admin.faturalar.hatirlat', $f->id) }}" method="POST" style="display:inline"
                                      onsubmit="event.stopPropagation();return confirm('{{ trim(($f->ad ?? '').' '.($f->soyad ?? '')) ?: 'Müşteriye' }}\nÖdeme bildirimi gönderilsin mi?');">
                                    @csrf
                                    <button type="submit" class="table-action" style="color:var(--brand-dark)" title="Ödeme bildirimi gönder">
                                        <i data-lucide="bell-ring"></i>
                                    </button>
                                </form>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.goster'))
                                <a href="{{ route('admin.faturalar.goster', $f->id) }}" target="_blank" class="table-action" title="Hızlı görüntüle">
                                    <i data-lucide="eye"></i>
                                </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.duzenle'))
                                <a href="{{ route('admin.faturalar.duzenle', $f->id) }}" class="table-action" title="Düzenle">
                                    <i data-lucide="edit-2"></i>
                                </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.durum'))
                                <form action="{{ route('admin.faturalar.durum', ['id' => $f->id, 'durum' => 1]) }}" method="POST" style="display:inline">
                                    @csrf
                                    <button type="submit" class="table-action" style="color:var(--success)" title="Onayla">
                                        <i data-lucide="check"></i>
                                    </button>
                                </form>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.sil'))
                                <form action="{{ route('admin.faturalar.sil', $f->id) }}" method="POST" style="display:inline" onsubmit="event.stopPropagation();return confirm('Fatura silinsin mi?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i data-lucide="check-circle-2" class="empty-state-icon"></i>
                                <h4>Bekleyen fatura yok</h4>
                                <p>Tüm faturalar ödenmiş, tebrikler! 🎉</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($faturalar ?? null, 'hasPages') && $faturalar->hasPages())
        <div class="pagination">
            {{ $faturalar->withQueryString()->links() }}
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.fatura-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.no-row-click')) return;
                const url = row.dataset.url;
                if (url && url !== '#') window.location = url;
            });
        });
    });
</script>

@endsection