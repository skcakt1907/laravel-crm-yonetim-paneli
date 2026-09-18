@extends('admin._layout')

@section('title', 'Gelir-Gider')

@push('head')
<style>
    .gg-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px}
    .gg-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .gg-sub{color: var(--text-muted);font-size:13.5px;margin-bottom:20px}
    .gg-tabs{display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap;align-items:center}
    .gg-tab{padding:9px 18px;border-radius: 22px;font-size:13.5px;font-weight:700;text-decoration:none;border: 1.5px solid var(--border);color: var(--text-secondary);transition:.15s}
    .gg-tab:hover{border-color: var(--brand);color: var(--brand-hover)}
    .gg-tab.aktif{background: var(--brand);border-color: var(--brand);color: var(--text)}
    /* Büyük net kâr kartı */
    .gg-net{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;padding:26px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .gg-net-l{font-size:13px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
    .gg-net-v{font-size:40px;font-weight:800;margin-top:6px;line-height:1}
    .gg-net-v.art{color: var(--success)}
    .gg-net-v.eksi{color: var(--danger)}
    .gg-net-aciklama{font-size:13px;color: var(--text-secondary);margin-top:8px}
    .gg-net-ikon{width:72px;height:72px;border-radius: 20px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .gg-net-ikon.art{background: rgba(16,185,129,.12);color: var(--success)}
    .gg-net-ikon.eksi{background: rgba(239,68,68,.12);color: var(--danger)}
    /* İkili kart: gelir + gider */
    .gg-ikili{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
    .gg-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:22px}
    .gg-kart-head{display:flex;align-items:center;gap:10px;margin-bottom:16px}
    .gg-kart-ic{width:42px;height:42px;border-radius: 12px;display:flex;align-items:center;justify-content:center}
    .gg-kart.gelir .gg-kart-ic{background: rgba(16,185,129,.12);color: var(--success)}
    .gg-kart.gider .gg-kart-ic{background: rgba(239,68,68,.12);color: var(--danger)}
    .gg-kart-baslik{font-size:16px;font-weight:800;color: var(--text)}
    .gg-kart-toplam{font-size:30px;font-weight:800;margin:4px 0 16px}
    .gg-kart.gelir .gg-kart-toplam{color: var(--success)}
    .gg-kart.gider .gg-kart-toplam{color: var(--danger)}
    .gg-satir{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-top: 1px solid var(--border);font-size:13.5px}
    .gg-satir .lbl{color: var(--text-secondary)}
    .gg-satir .val{font-weight:700;color: var(--text)}
    /* Grafik */
    .gg-grafik{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:22px;margin-bottom:18px}
    .gg-grafik h3{font-size:15px;font-weight:700;margin:0 0 18px;display:flex;align-items:center;gap:8px}
    .gg-bar-row{display:flex;align-items:center;gap:12px;margin-bottom:13px}
    .gg-bar-ay{width:90px;flex-shrink:0;font-size:12.5px;font-weight:600;color: var(--text-secondary);text-align:right}
    .gg-bar-wrap{flex:1;display:flex;flex-direction:column;gap:3px}
    .gg-bar{height:13px;border-radius: 4px;min-width:2px;transition:width .5s}
    .gg-bar.gelir{background: var(--success)}
    .gg-bar.gider{background: var(--danger)}
    .gg-bar-net{width:120px;flex-shrink:0;font-size:13px;font-weight:700;text-align:right}
    .gg-bar-net.art{color: var(--success)}
    .gg-bar-net.eksi{color: var(--danger)}
    .gg-legend{display:flex;gap:18px;margin-top:10px;font-size:12.5px;color: var(--text-secondary)}
    .gg-legend span{display:inline-flex;align-items:center;gap:6px}
    .gg-dot{width:11px;height:11px;border-radius: 3px;display:inline-block}
    .gg-uyari{background: rgba(245,158,11,.1);border: 1px solid rgba(245,158,11,.3);color: var(--warning);padding:14px 16px;border-radius: 12px;margin-bottom:18px;font-size:13.5px}
    /* Açılır-kapanır detay dropdown'ları */
    .gg-acc{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;margin-bottom:14px;overflow:hidden}
    .gg-acc-head{display:flex;align-items:center;gap:12px;width:100%;padding:16px 20px;background: none;border: none;cursor:pointer;text-align:left;font-family:inherit}
    .gg-acc-head:hover{background: var(--bg-subtle)}
    .gg-acc-ic{width:38px;height:38px;border-radius: 10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .gg-acc.gelir .gg-acc-ic{background: rgba(16,185,129,.12);color: var(--success)}
    .gg-acc.gider .gg-acc-ic{background: rgba(239,68,68,.12);color: var(--danger)}
    .gg-acc.bekleyen .gg-acc-ic{background: rgba(245,158,11,.14);color: var(--warning)}
    .gg-acc.bekleyen .gg-acc-toplam{color: var(--warning)}
    .gg-acc.birlesik .gg-acc-ic{background: rgba(99,102,241,.12);color: #6366f1}
    .gg-acc-t{flex:1}
    .gg-acc-baslik{font-size:15px;font-weight:800;color: var(--text)}
    .gg-acc-alt{font-size:12.5px;color: var(--text-muted);margin-top:2px}
    .gg-acc-toplam{font-size:16px;font-weight:800;white-space:nowrap}
    .gg-acc.gelir .gg-acc-toplam{color: var(--success)}
    .gg-acc.gider .gg-acc-toplam{color: var(--danger)}
    .gg-acc-chev{transition:transform .2s;color: var(--text-muted);flex-shrink:0}
    .gg-acc.acik .gg-acc-chev{transform:rotate(180deg)}
    .gg-acc-body{display:none;border-top: 1px solid var(--border)}
    .gg-acc.acik .gg-acc-body{display:block}
    .gg-tbl{width:100%;border-collapse: collapse}
    .gg-tbl th{text-align:left;font-size:11px;font-weight:700;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:11px 20px;background: var(--surface);border-bottom: 1px solid var(--border)}
    .gg-tbl td{padding:12px 20px;border-bottom: 1px solid var(--border);font-size:13px;color: var(--text-secondary)}
    .gg-tbl tr:last-child td{border-bottom: none}
    .gg-tbl tr:hover td{background: var(--bg-subtle)}
    .gg-tbl .num{text-align:right;font-weight:800;white-space:nowrap}
    .gg-tbl .num.art{color: var(--success)}
    .gg-tbl .num.eksi{color: var(--danger)}
    .gg-pill{font-size:11.5px;font-weight:700;padding:2px 9px;border-radius: 20px;white-space:nowrap}
    .gg-pill.gelir{background: rgba(16,185,129,.12);color: var(--success)}
    .gg-pill.gider{background: rgba(239,68,68,.12);color: var(--danger)}
    .gg-pill.odendi{background: rgba(16,185,129,.12);color: var(--success)}
    .gg-pill.bekliyor{background: rgba(245,158,11,.14);color: var(--warning)}
    .gg-pill.gecikti{background: rgba(239,68,68,.12);color: var(--danger)}
    .gg-bos{padding:30px 20px;text-align:center;color: var(--text-muted);font-size:13.5px}
    /* Standart aylık kalemler (manuel) */
    .gg-std-baslik{margin:26px 0 12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .gg-std-baslik h3{font-size:16px;font-weight:800;margin:0;color: var(--text);display:flex;align-items:center;gap:8px}
    .gg-std-not{font-size:12.5px;color: var(--text-muted)}
    .gg-sil{background: none;border: none;cursor:pointer;color: var(--text-muted);padding:4px 6px;border-radius: 6px;line-height:0}
    .gg-sil:hover{color: var(--danger);background: rgba(239,68,68,.08)}
    .gg-ekle-form{display:flex;gap:8px;padding:14px 20px;border-top: 1px solid var(--border);background: var(--bg-subtle);flex-wrap:wrap;align-items:center}
    .gg-inp{flex:1;min-width:130px;height:38px;padding:0 12px;border: 1.5px solid var(--border);border-radius: 9px;font-size:13.5px;font-family:inherit;color: var(--text)}
    .gg-inp:focus{outline: none;border-color: var(--brand)}
    .gg-inp-num{flex:0 0 130px;text-align:right}
    .gg-ekle-btn{height:38px;padding:0 16px;border: none;border-radius: 9px;font-weight:700;font-size:13px;cursor:pointer;color: var(--text-inverse);display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
    .gg-ekle-btn.gelir{background: var(--success)}
    .gg-ekle-btn.gider{background: var(--danger)}
    .gg-ekle-btn:hover{filter:brightness(.95)}
    @media(max-width:768px){.gg-ikili{grid-template-columns:1fr}.gg-bar-ay{width:64px}.gg-bar-net{width:90px;font-size:12px}.gg-inp-num{flex:1 1 100%}}
</style>
@endpush

@section('content')
<div class="gg-head">
    <h1><i data-lucide="scale"></i> Gelir-Gider</h1>
</div>
<div class="gg-sub">Şirketin gelir (faturalar) ve gider (harcamalar) karşılaştırması — net kâr/zarar.</div>

@if(!$faturaVar && !$giderVar)
<div class="gg-uyari">Faturalar ve giderler tabloları bulunamadı.</div>
@else

@if(!$faturaVar)<div class="gg-uyari">Faturalar tablosu yok — gelir 0 gösteriliyor.</div>@endif
@if(!$giderVar)<div class="gg-uyari">Giderler tablosu yok — gider 0 gösteriliyor.</div>@endif

{{-- Dönem sekmeleri --}}
<div class="gg-tabs">
    <a href="{{ route('admin.gelir-gider.index', ['donem'=>'ay']) }}" class="gg-tab {{ $donem==='ay'?'aktif':'' }}">Bu Ay</a>
    <a href="{{ route('admin.gelir-gider.index', ['donem'=>'uc_ay']) }}" class="gg-tab {{ $donem==='uc_ay'?'aktif':'' }}">Son 3 Ay</a>
    <a href="{{ route('admin.gelir-gider.index', ['donem'=>'yil']) }}" class="gg-tab {{ $donem==='yil'?'aktif':'' }}">Bu Yıl</a>
    <span style="color: var(--text-muted);font-size:13px;margin-left:6px">{{ $aralikMetni }}</span>
</div>

{{-- Net kâr/zarar (büyük kart) --}}
@php $artida = $netNakit >= 0; @endphp
<div class="gg-net">
    <div>
        <div class="gg-net-l">Net Nakit Durumu (Tahsil Edilen Gelir − Ödenen Gider)</div>
        <div class="gg-net-v {{ $artida ? 'art' : 'eksi' }}">{{ $artida ? '' : '−' }}₺{{ number_format(abs($netNakit), 2, ',', '.') }}</div>
        <div class="gg-net-aciklama">
            Tahakkuk (tüm gelir − tüm gider):
            <strong style="color: {{ $netTahakkuk>=0 ? '#059669' : '#dc2626' }}">{{ $netTahakkuk>=0?'':'−' }}₺{{ number_format(abs($netTahakkuk), 2, ',', '.') }}</strong>
        </div>
    </div>
    <div class="gg-net-ikon {{ $artida ? 'art' : 'eksi' }}">
        <i data-lucide="{{ $artida ? 'trending-up' : 'trending-down' }}" style="width:36px;height:36px"></i>
    </div>
</div>

{{-- Gelir + Gider kartları --}}
<div class="gg-ikili">
    <div class="gg-kart gelir">
        <div class="gg-kart-head">
            <div class="gg-kart-ic"><i data-lucide="arrow-down-circle" style="width:22px;height:22px"></i></div>
            <div class="gg-kart-baslik">Gelir (Faturalar)</div>
        </div>
        <div class="gg-kart-toplam">₺{{ number_format($gelir['toplam'], 2, ',', '.') }}</div>
        <div class="gg-satir"><span class="lbl">Tahsil edilen</span><span class="val" style="color: var(--success)">₺{{ number_format($gelir['tahsil'], 2, ',', '.') }}</span></div>
        <div class="gg-satir"><span class="lbl">Bekleyen</span><span class="val" style="color: var(--warning)">₺{{ number_format($gelir['bekleyen'], 2, ',', '.') }}</span></div>
        <div class="gg-satir"><span class="lbl">Fatura sayısı</span><span class="val">{{ $gelir['adet'] }}</span></div>
    </div>

    <div class="gg-kart gider">
        <div class="gg-kart-head">
            <div class="gg-kart-ic"><i data-lucide="arrow-up-circle" style="width:22px;height:22px"></i></div>
            <div class="gg-kart-baslik">Gider (Harcamalar)</div>
        </div>
        <div class="gg-kart-toplam">₺{{ number_format($gider['toplam'], 2, ',', '.') }}</div>
        <div class="gg-satir"><span class="lbl">Ödenen</span><span class="val" style="color: var(--danger)">₺{{ number_format($gider['odenen'], 2, ',', '.') }}</span></div>
        <div class="gg-satir"><span class="lbl">Bekleyen/Gecikmiş</span><span class="val" style="color: var(--warning)">₺{{ number_format($gider['bekleyen'], 2, ',', '.') }}</span></div>
        <div class="gg-satir"><span class="lbl">Gider kalemi</span><span class="val">{{ $gider['adet'] }}</span></div>
    </div>
</div>

{{-- ═══════════════ STANDART AYLIK KALEMLER (manuel — bağımsız) ═══════════════ --}}
<div class="gg-std-baslik">
    <h3><i data-lucide="table-2" style="width:18px;height:18px"></i> Standart Aylık Kalemler</h3>
    <span class="gg-std-not">Manuel giriş — yukarıdaki hesaplara/toplamlara <b>dahil değildir</b>, sadece kayıt & görüntüleme.</span>
</div>

@if(!$ggStdVar)
<div class="gg-uyari">Standart kalemler tablosu (gg_standart_kalemler) bulunamadı — migration/SQL çalıştırılmalı.</div>
@else

{{-- Standart Aylık Gelir --}}
@php $stdGelirToplam = $standartGelir->sum('tutar'); @endphp
<div class="gg-acc gelir acik" id="acc-std-gelir">
    <button type="button" class="gg-acc-head" onclick="ggToggle('acc-std-gelir')">
        <span class="gg-acc-ic"><i data-lucide="wallet" style="width:20px;height:20px"></i></span>
        <span class="gg-acc-t">
            <span class="gg-acc-baslik">Standart Aylık Gelir</span>
            <span class="gg-acc-alt">{{ $standartGelir->count() }} kalem — manuel (bağımsız)</span>
        </span>
        <span class="gg-acc-toplam">₺{{ number_format($stdGelirToplam, 2, ',', '.') }}</span>
        <i data-lucide="chevron-down" class="gg-acc-chev" style="width:20px;height:20px"></i>
    </button>
    <div class="gg-acc-body">
        <table class="gg-tbl">
            <thead><tr><th>Başlık</th><th>Açıklama</th><th style="text-align:right">Tutar</th><th style="width:50px"></th></tr></thead>
            <tbody>
                @forelse($standartGelir as $k)
                <tr>
                    <td style="font-weight:600;color: var(--text)">{{ $k->baslik }}</td>
                    <td style="color: var(--text-secondary)">{{ $k->aciklama ?: '—' }}</td>
                    <td class="num art">₺{{ number_format($k->tutar, 2, ',', '.') }}</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('admin.gelir-gider.standart.sil', $k->id) }}" onsubmit="return confirm('Bu kalem silinsin mi?')" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="gg-sil" title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="gg-bos">Henüz kalem yok — aşağıdan ekleyin.</td></tr>
                @endforelse
            </tbody>
            @if($standartGelir->count() > 0)
            <tfoot><tr style="background: var(--surface)">
                <td colspan="2" style="font-weight:700;color: var(--text-secondary)">Toplam</td>
                <td class="num art">₺{{ number_format($stdGelirToplam, 2, ',', '.') }}</td><td></td>
            </tr></tfoot>
            @endif
        </table>
        <form method="POST" action="{{ route('admin.gelir-gider.standart.ekle') }}" class="gg-ekle-form">
            @csrf
            <input type="hidden" name="tip" value="gelir">
            <input type="text" name="baslik" placeholder="Başlık (ör. Kira geliri)" required maxlength="190" class="gg-inp">
            <input type="text" name="aciklama" placeholder="Açıklama (opsiyonel)" maxlength="255" class="gg-inp">
            <input type="number" name="tutar" placeholder="Tutar ₺" step="0.01" min="0" required class="gg-inp gg-inp-num">
            <button type="submit" class="gg-ekle-btn gelir"><i data-lucide="plus" style="width:15px;height:15px"></i> Ekle</button>
        </form>
    </div>
</div>

{{-- Standart Aylık Gider --}}
@php $stdGiderToplam = $standartGider->sum('tutar'); @endphp
<div class="gg-acc gider acik" id="acc-std-gider">
    <button type="button" class="gg-acc-head" onclick="ggToggle('acc-std-gider')">
        <span class="gg-acc-ic"><i data-lucide="receipt-text" style="width:20px;height:20px"></i></span>
        <span class="gg-acc-t">
            <span class="gg-acc-baslik">Standart Aylık Gider</span>
            <span class="gg-acc-alt">{{ $standartGider->count() }} kalem — manuel (bağımsız)</span>
        </span>
        <span class="gg-acc-toplam">₺{{ number_format($stdGiderToplam, 2, ',', '.') }}</span>
        <i data-lucide="chevron-down" class="gg-acc-chev" style="width:20px;height:20px"></i>
    </button>
    <div class="gg-acc-body">
        <table class="gg-tbl">
            <thead><tr><th>Başlık</th><th>Açıklama</th><th style="text-align:right">Tutar</th><th style="width:50px"></th></tr></thead>
            <tbody>
                @forelse($standartGider as $k)
                <tr>
                    <td style="font-weight:600;color: var(--text)">{{ $k->baslik }}</td>
                    <td style="color: var(--text-secondary)">{{ $k->aciklama ?: '—' }}</td>
                    <td class="num eksi">₺{{ number_format($k->tutar, 2, ',', '.') }}</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('admin.gelir-gider.standart.sil', $k->id) }}" onsubmit="return confirm('Bu kalem silinsin mi?')" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="gg-sil" title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="gg-bos">Henüz kalem yok — aşağıdan ekleyin.</td></tr>
                @endforelse
            </tbody>
            @if($standartGider->count() > 0)
            <tfoot><tr style="background: var(--surface)">
                <td colspan="2" style="font-weight:700;color: var(--text-secondary)">Toplam</td>
                <td class="num eksi">₺{{ number_format($stdGiderToplam, 2, ',', '.') }}</td><td></td>
            </tr></tfoot>
            @endif
        </table>
        <form method="POST" action="{{ route('admin.gelir-gider.standart.ekle') }}" class="gg-ekle-form">
            @csrf
            <input type="hidden" name="tip" value="gider">
            <input type="text" name="baslik" placeholder="Başlık (ör. Kira gideri)" required maxlength="190" class="gg-inp">
            <input type="text" name="aciklama" placeholder="Açıklama (opsiyonel)" maxlength="255" class="gg-inp">
            <input type="number" name="tutar" placeholder="Tutar ₺" step="0.01" min="0" required class="gg-inp gg-inp-num">
            <button type="submit" class="gg-ekle-btn gider"><i data-lucide="plus" style="width:15px;height:15px"></i> Ekle</button>
        </form>
    </div>
</div>

@endif

{{-- Aylık kırılım grafiği --}}
@if(count($aylikKirilim) > 0)
@php $maxDeger = collect($aylikKirilim)->flatMap(fn($a)=>[$a['gelir'],$a['gider']])->max() ?: 1; @endphp
<div class="gg-grafik">
    <h3><i data-lucide="bar-chart-3" style="width:18px;height:18px"></i> Aylık Gelir-Gider Karşılaştırması</h3>
    @foreach($aylikKirilim as $a)
    <div class="gg-bar-row">
        <div class="gg-bar-ay">{{ $a['etiket'] }}</div>
        <div class="gg-bar-wrap">
            <div class="gg-bar gelir" style="width:{{ $maxDeger>0 ? max(0.5,$a['gelir']/$maxDeger*100) : 0 }}%" title="Gelir: ₺{{ number_format($a['gelir'],0,',','.') }}"></div>
            <div class="gg-bar gider" style="width:{{ $maxDeger>0 ? max(0.5,$a['gider']/$maxDeger*100) : 0 }}%" title="Gider: ₺{{ number_format($a['gider'],0,',','.') }}"></div>
        </div>
        <div class="gg-bar-net {{ $a['net']>=0 ? 'art' : 'eksi' }}">{{ $a['net']>=0?'':'−' }}₺{{ number_format(abs($a['net']),0,',','.') }}</div>
    </div>
    @endforeach
    <div class="gg-legend">
        <span><i class="gg-dot" style="background: var(--success)"></i> Gelir (tahsil)</span>
        <span><i class="gg-dot" style="background: var(--danger)"></i> Gider (ödenen)</span>
        <span style="margin-left:auto">Sağdaki değer: net (gelir − gider)</span>
    </div>
</div>
@endif

{{-- ═══════════════ DETAY DROPDOWN'LARI ═══════════════ --}}

{{-- 1) Tahsil Edilen Faturalar (Gelir) --}}
<div class="gg-acc gelir" id="acc-gelir">
    <button type="button" class="gg-acc-head" onclick="ggToggle('acc-gelir')">
        <span class="gg-acc-ic"><i data-lucide="file-check-2" style="width:20px;height:20px"></i></span>
        <span class="gg-acc-t">
            <span class="gg-acc-baslik">Tahsil Edilen Faturalar</span>
            <span class="gg-acc-alt">{{ $onaylananFaturalar->count() }} fatura — tahsil edilen gelir ({{ $aralikMetni }})</span>
        </span>
        <span class="gg-acc-toplam">₺{{ number_format($onaylananFaturalar->sum('tutar'), 2, ',', '.') }}</span>
        <i data-lucide="chevron-down" class="gg-acc-chev" style="width:20px;height:20px"></i>
    </button>
    <div class="gg-acc-body">
        @if($onaylananFaturalar->count() > 0)
        <table class="gg-tbl">
            <thead><tr><th>Tarih</th><th>Fatura No</th><th>Müşteri</th><th>Açıklama</th><th style="text-align:right">Tutar</th></tr></thead>
            <tbody>
                @foreach($onaylananFaturalar as $f)
                @php $musteri = $f->firmaadi ?: trim(($f->ad ?? '').' '.($f->soyad ?? '')); @endphp
                <tr>
                    <td style="white-space:nowrap">{{ $f->tarih ? \Carbon\Carbon::parse($f->tarih)->format('d.m.Y') : '—' }}</td>
                    <td style="color: var(--text-secondary)">{{ $f->fatura_no ?: '#'.$f->id }}</td>
                    <td style="font-weight:600;color: var(--text)">{{ $musteri !== '' ? $musteri : '—' }}</td>
                    <td style="color: var(--text-secondary)">{{ $f->baslik ?: ($f->hizmet ?: '—') }}</td>
                    <td class="num art">₺{{ number_format($f->tutar, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="gg-bos">Bu dönemde tahsil edilen fatura yok.</div>
        @endif
    </div>
</div>

{{-- 1b) Bekleyen Faturalar (henüz tahsil edilmemiş gelir) --}}
<div class="gg-acc bekleyen" id="acc-bekleyen">
    <button type="button" class="gg-acc-head" onclick="ggToggle('acc-bekleyen')">
        <span class="gg-acc-ic"><i data-lucide="clock" style="width:20px;height:20px"></i></span>
        <span class="gg-acc-t">
            <span class="gg-acc-baslik">Bekleyen Faturalar</span>
            <span class="gg-acc-alt">{{ $bekleyenFaturalar->count() }} fatura — tahsil edilmemiş gelir ({{ $aralikMetni }})</span>
        </span>
        <span class="gg-acc-toplam">₺{{ number_format($bekleyenFaturalar->sum('tutar'), 2, ',', '.') }}</span>
        <i data-lucide="chevron-down" class="gg-acc-chev" style="width:20px;height:20px"></i>
    </button>
    <div class="gg-acc-body">
        @if($bekleyenFaturalar->count() > 0)
        <table class="gg-tbl">
            <thead><tr><th>Tarih</th><th>Fatura No</th><th>Müşteri</th><th>Açıklama</th><th style="text-align:right">Tutar</th></tr></thead>
            <tbody>
                @foreach($bekleyenFaturalar as $f)
                @php $musteri = $f->firmaadi ?: trim(($f->ad ?? '').' '.($f->soyad ?? '')); @endphp
                <tr>
                    <td style="white-space:nowrap">{{ $f->tarih ? \Carbon\Carbon::parse($f->tarih)->format('d.m.Y') : '—' }}</td>
                    <td style="color: var(--text-secondary)">{{ $f->fatura_no ?: '#'.$f->id }}</td>
                    <td style="font-weight:600;color: var(--text)">{{ $musteri !== '' ? $musteri : '—' }}</td>
                    <td style="color: var(--text-secondary)">{{ $f->baslik ?: ($f->hizmet ?: '—') }}</td>
                    <td class="num" style="color: var(--warning)">₺{{ number_format($f->tutar, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="gg-bos">Bu dönemde bekleyen fatura yok.</div>
        @endif
    </div>
</div>

{{-- 2) Harcamalar (Gider) --}}
<div class="gg-acc gider" id="acc-gider">
    <button type="button" class="gg-acc-head" onclick="ggToggle('acc-gider')">
        <span class="gg-acc-ic"><i data-lucide="receipt" style="width:20px;height:20px"></i></span>
        <span class="gg-acc-t">
            <span class="gg-acc-baslik">Harcamalar</span>
            <span class="gg-acc-alt">{{ $harcamalar->count() }} gider kalemi ({{ $aralikMetni }})</span>
        </span>
        <span class="gg-acc-toplam">₺{{ number_format($harcamalar->sum('tutar'), 2, ',', '.') }}</span>
        <i data-lucide="chevron-down" class="gg-acc-chev" style="width:20px;height:20px"></i>
    </button>
    <div class="gg-acc-body">
        @if($harcamalar->count() > 0)
        <table class="gg-tbl">
            <thead><tr><th>Tarih</th><th>Harcama</th><th>Kategori</th><th>Durum</th><th style="text-align:right">Tutar</th></tr></thead>
            <tbody>
                @foreach($harcamalar as $h)
                <tr>
                    <td style="white-space:nowrap">{{ $h->gider_tarihi ? \Carbon\Carbon::parse($h->gider_tarihi)->format('d.m.Y') : '—' }}</td>
                    <td style="font-weight:600;color: var(--text)">{{ $h->baslik }}</td>
                    <td style="color: var(--text-secondary)">{{ $h->kategori_adi ?? '—' }}</td>
                    <td><span class="gg-pill {{ $h->durum }}">{{ ['odendi'=>'Ödendi','bekliyor'=>'Bekliyor','gecikti'=>'Gecikti'][$h->durum] ?? $h->durum }}</span></td>
                    <td class="num eksi">₺{{ number_format($h->tutar, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="gg-bos">Bu dönemde harcama kaydı yok.</div>
        @endif
    </div>
</div>

{{-- 3) Birleşik Hareketler (gelir + gider birlikte) --}}
@php
    $brToplamGelir = $hareketler->where('tip','gelir')->sum('tutar');
    $brToplamGider = $hareketler->where('tip','gider')->sum('tutar');
    $brNet = $brToplamGelir - $brToplamGider;
@endphp
<div class="gg-acc birlesik" id="acc-birlesik">
    <button type="button" class="gg-acc-head" onclick="ggToggle('acc-birlesik')">
        <span class="gg-acc-ic"><i data-lucide="arrow-left-right" style="width:20px;height:20px"></i></span>
        <span class="gg-acc-t">
            <span class="gg-acc-baslik">Tüm Hareketler (Gelir + Gider)</span>
            <span class="gg-acc-alt">{{ $hareketler->count() }} hareket — gelir ve giderler tek listede ({{ $aralikMetni }})</span>
        </span>
        <span class="gg-acc-toplam" style="color: {{ $brNet>=0 ? '#059669' : '#dc2626' }}">{{ $brNet>=0?'':'−' }}₺{{ number_format(abs($brNet), 2, ',', '.') }}</span>
        <i data-lucide="chevron-down" class="gg-acc-chev" style="width:20px;height:20px"></i>
    </button>
    <div class="gg-acc-body">
        @if($hareketler->count() > 0)
        <table class="gg-tbl">
            <thead><tr><th>Tarih</th><th>Tip</th><th>Açıklama</th><th>Detay</th><th style="text-align:right">Tutar</th></tr></thead>
            <tbody>
                @foreach($hareketler as $hr)
                <tr>
                    <td style="white-space:nowrap">{{ $hr->tarih ? \Carbon\Carbon::parse($hr->tarih)->format('d.m.Y') : '—' }}</td>
                    <td><span class="gg-pill {{ $hr->tip }}">{{ $hr->tip === 'gelir' ? 'Gelir' : 'Gider' }}</span></td>
                    <td style="font-weight:600;color: var(--text)">{{ $hr->baslik }}</td>
                    <td style="color: var(--text-secondary)">{{ $hr->detay }}</td>
                    <td class="num {{ $hr->tip === 'gelir' ? 'art' : 'eksi' }}">{{ $hr->tip === 'gelir' ? '+' : '−' }}₺{{ number_format($hr->tutar, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: var(--surface)">
                    <td colspan="4" style="font-weight:700;color: var(--text-secondary)">Net (Gelir − Gider)</td>
                    <td class="num {{ $brNet>=0 ? 'art' : 'eksi' }}">{{ $brNet>=0?'':'−' }}₺{{ number_format(abs($brNet), 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="gg-bos">Bu dönemde hareket yok.</div>
        @endif
    </div>
</div>


@endif
@endsection

@push('scripts')
<script>
if(window.lucide)lucide.createIcons();
function ggToggle(id){
    var el = document.getElementById(id);
    if(el) el.classList.toggle('acik');
}
</script>
@endpush