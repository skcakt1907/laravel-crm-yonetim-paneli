@extends('admin._layout')

@section('title', 'Tüm Faturalar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Faturalar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🧾 Tüm Faturalar</h1>
        <div class="page-subtitle">{{ $faturalar->total() ?? 0 }} fatura listeleniyor</div>
    </div>
    <div class="page-actions">
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.bekleyen'))
        <a href="{{ route('admin.faturalar.bekleyen') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="clock"></i>
            <span>Bekleyen</span>
        </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.onaylanan'))
        <a href="{{ route('admin.faturalar.onaylanan') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="check-circle"></i>
            <span>Onaylanan</span>
        </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.export'))
        <a href="{{ route('admin.faturalar.export', 'all') }}" class="btn btn-secondary btn-sm">
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

{{-- ═══ Stat Cards ═══ --}}
<div class="mini-stat-grid">
    <div class="mini-stat info">
        <div class="lbl">Toplam Fatura</div>
        <div class="val">{{ number_format(\Illuminate\Support\Facades\DB::table('faturalar')->count()) }}</div>
        <div class="sub">Tüm kayıtlar</div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Ödenen</div>
        <div class="val" style="color:var(--success)">₺{{ number_format($odenenTutar ?? 0, 0, ',', '.') }}</div>
        <div class="sub">{{ number_format(\Illuminate\Support\Facades\DB::table('faturalar')->where('durum', 1)->count()) }} adet</div>
    </div>
    <div class="mini-stat warning">
        <div class="lbl">Bekleyen</div>
        <div class="val" style="color:var(--warning)">₺{{ number_format($bekleyenTutar ?? 0, 0, ',', '.') }}</div>
        <div class="sub">{{ number_format(\Illuminate\Support\Facades\DB::table('faturalar')->where('durum', 0)->count()) }} adet</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Toplam Tutar</div>
        <div class="val">₺{{ number_format($toplamTutar ?? 0, 0, ',', '.') }}</div>
        <div class="sub">Filtreye göre</div>
    </div>
</div>

{{-- ═══ Filtre Barı ═══ --}}
<form action="" method="GET" class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">

    {{-- ARAMA ÇUBUĞU --}}
    <div class="form-group" style="flex:2;min-width:230px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Ara</label>
        <input type="text" name="ara" value="{{ request('ara') }}" class="form-input"
               placeholder="Fatura no, başlık, müşteri veya e-posta">
    </div>

    <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Yıl</label>
        <select name="yil" class="form-select" onchange="this.form.submit()">
            <option value="">Tüm Yıllar</option>
            @foreach($mevcutYillar ?? [] as $y)
                <option value="{{ $y }}" {{ request('yil') == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Ay</label>
        <select name="ay" class="form-select" onchange="this.form.submit()">
            <option value="">Tüm Aylar</option>
            @foreach(['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'] as $i => $ad)
                <option value="{{ $i+1 }}" {{ request('ay') == ($i+1) ? 'selected' : '' }}>{{ $ad }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Durum</label>
        <select name="durum" class="form-select" onchange="this.form.submit()">
            <option value="">Hepsi</option>
            <option value="0" {{ request('durum') === '0' ? 'selected' : '' }}>⏳ Bekleyen</option>
            <option value="1" {{ request('durum') === '1' ? 'selected' : '' }}>✓ Ödenen</option>
            <option value="2" {{ request('durum') === '2' ? 'selected' : '' }}>✗ İptal</option>
        </select>
    </div>

    @if(request('yil') || request('ay') || request('durum') !== null && request('durum') !== '')
    <a href="{{ url()->current() }}" class="btn btn-ghost btn-sm">
        <i data-lucide="x"></i>
        <span>Temizle</span>
    </a>
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
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faturalar ?? [] as $f)
                    @php
                        $detayUrl = \Illuminate\Support\Facades\Route::has('admin.faturalar.detay')
                            ? route('admin.faturalar.detay', $f->id) : '#';
                    @endphp
                    <tr class="clickable fatura-row" data-url="{{ $detayUrl }}">
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
                        <td>
                            @if(($f->durum ?? 0) == 1)
                                <span class="badge badge-success">✓ Ödendi</span>
                            @elseif(($f->durum ?? 0) == 2)
                                <span class="badge badge-danger">✗ İptal</span>
                            @else
                                <span class="badge badge-warning">⏳ Bekliyor</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:var(--text-muted)">
                            @if(!empty($f->tarih))
                                {{ \Carbon\Carbon::parse($f->tarih)->format('d.m.Y') }}
                            @else — @endif
                        </td>
                        <td class="text-right no-row-click" onclick="event.stopPropagation()">
                            <div class="table-actions">
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.goster'))
                                <a href="{{ route('admin.faturalar.goster', $f->id) }}" target="_blank" class="table-action" title="Yazdır">
                                    <i data-lucide="printer"></i>
                                </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.detay'))
                                <a href="{{ route('admin.faturalar.detay', $f->id) }}" class="table-action" title="Detay">
                                    <i data-lucide="eye"></i>
                                </a>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.duzenle'))
                                <a href="{{ route('admin.faturalar.duzenle', $f->id) }}" class="table-action" title="Düzenle">
                                    <i data-lucide="edit-2"></i>
                                </a>
                                @endif
                                @if(($f->durum ?? 0) == 0 && \Illuminate\Support\Facades\Route::has('admin.faturalar.durum'))
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
                                <i data-lucide="receipt" class="empty-state-icon"></i>
                                <h4>Henüz fatura yok</h4>
                                <p>Yeni fatura oluştur veya filtreleri değiştir.</p>
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