@extends('admin._layout')

@section('title', 'Harcamalar')

@push('head')
<style>
    .gd-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .gd-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .gd-btn{display:inline-flex;align-items:center;gap:8px;background: var(--brand);color: var(--text);font-weight:700;
        padding:11px 20px;border-radius: 12px;text-decoration:none;border: none;cursor:pointer;font-size:14px;transition:.2s}
    .gd-btn:hover{background: var(--brand-hover);transform:translateY(-1px)}
    /* Özet kartları */
    .gd-ozet{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:22px}
    .gd-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:18px 20px}
    .gd-kart-l{font-size:12.5px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:flex;align-items:center;gap:7px}
    .gd-kart-v{font-size:26px;font-weight:800;color: var(--text);margin-top:8px}
    .gd-kart.t .gd-kart-v{color: var(--text)}
    .gd-kart.a .gd-kart-v{color: var(--info)}
    .gd-kart.b .gd-kart-v{color: var(--warning)}
    .gd-kart.g .gd-kart-v{color: var(--danger)}
    /* Filtre */
    .gd-filtre{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:16px 18px;margin-bottom:18px;
        display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
    .gd-f-grup{display:flex;flex-direction:column;gap:5px}
    .gd-f-grup label{font-size:11.5px;font-weight:600;color: var(--text-secondary)}.gd-f-grup input,.gd-f-grup select{border: 1.5px solid var(--border);border-radius: 9px;padding:8px 11px;font-size:13.5px;font-family:inherit;color: var(--text);min-width:140px;background: var(--surface);}
    .gd-f-grup input:focus,.gd-f-grup select:focus{outline: none;border-color: var(--brand)}
    .gd-f-btn{background: var(--brand);color: var(--text);border: none;border-radius: 9px;padding:9px 18px;font-weight:700;font-size:13.5px;cursor:pointer}
    .gd-f-clear{color: var(--text-secondary);text-decoration:none;font-size:13px;padding:9px 10px;font-weight:600}
    /* Tablo */
    .gd-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;overflow:hidden}
    .gd-table{width:100%;border-collapse: collapse}
    .gd-table th{text-align:left;font-size:11.5px;font-weight:700;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:13px 16px;border-bottom: 1px solid var(--border);background: var(--surface)}
    .gd-table td{padding:14px 16px;border-bottom: 1px solid var(--border);font-size:13.5px;color: var(--text-secondary);vertical-align:middle}
    .gd-table tr:last-child td{border-bottom: none}
    .gd-table tr:hover td{background: var(--bg-subtle)}
    /* Satır arka planı — aciliyet rengine göre tüm satır boyanır */
    .gd-table tr.row-kirmizi td{background: rgba(239,68,68,.10)}
    .gd-table tr.row-kirmizi:hover td{background: rgba(239,68,68,.16)}
    .gd-table tr.row-sari td{background: rgba(245,158,11,.12)}
    .gd-table tr.row-sari:hover td{background: rgba(245,158,11,.18)}
    .gd-table tr.row-yesil td{background: rgba(16,185,129,.10)}
    .gd-table tr.row-yesil:hover td{background: rgba(16,185,129,.16)}
    .gd-table tr.row-notr td{background: transparent}
    /* Sol kenarda ince renkli vurgu (satır rengiyle uyumlu, opsiyonel şerit) */
    .gd-table tr.row-kirmizi td:first-child{box-shadow:inset 4px 0 0 #ef4444}
    .gd-table tr.row-sari td:first-child{box-shadow:inset 4px 0 0 #f59e0b}
    .gd-table tr.row-yesil td:first-child{box-shadow:inset 4px 0 0 #10b981}
    .gd-baslik{font-weight:700;color: var(--text)}
    .gd-kat{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:3px 10px;border-radius: 20px}
    .gd-tutar{font-weight:800;font-size:15px;color: var(--text);white-space:nowrap}
    .gd-aciliyet{font-size:12px;font-weight:600;padding:3px 9px;border-radius: 20px;white-space:nowrap}
    .gd-aciliyet.kirmizi{background: rgba(239,68,68,.12);color: var(--danger)}
    .gd-aciliyet.sari{background: rgba(245,158,11,.14);color: var(--warning)}
    .gd-aciliyet.yesil{background: rgba(16,185,129,.12);color: var(--success)}
    .gd-aciliyet.notr{background: rgba(148,163,184,.15);color: var(--text-secondary)}
    .gd-durum{font-size:12px;font-weight:600;padding:3px 10px;border-radius: 20px}
    .gd-durum.odendi{background: rgba(16,185,129,.12);color: var(--success)}
    .gd-durum.bekliyor{background: rgba(245,158,11,.14);color: var(--warning)}
    .gd-durum.gecikti{background: rgba(239,68,68,.12);color: var(--danger)}
    .gd-act{width:32px;height:32px;border-radius: 8px;border: 1px solid var(--border);background: var(--surface);color: var(--text-secondary);
        display:inline-flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;transition:.15s}
    .gd-act:hover{border-color: var(--brand);color: var(--brand-hover)}
    .gd-act.del:hover{border-color: var(--danger);color: var(--danger)}
    .gd-act.ok:hover{border-color: var(--success);color: var(--success)}
    .gd-empty{padding:56px 20px;text-align:center;color: var(--text-muted)}
    /* Kategori (envanter) */
    .gd-env{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:20px;margin-top:20px}
    .gd-env h3{font-size:15px;font-weight:700;margin:0 0 14px;display:flex;align-items:center;gap:8px}
    .gd-env-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom: 1px solid #f5f6f7}
    .gd-env-row:last-child{border-bottom: none}
    .gd-env-dot{width:12px;height:12px;border-radius: 50%;flex-shrink:0}
    .gd-env-ad{font-size:13.5px;font-weight:600;color: var(--text-secondary);flex:1}
    .gd-env-bar{flex:2;height:8px;background: var(--bg-subtle);border-radius: 6px;overflow:hidden}
    .gd-env-bar span{display:block;height:100%;border-radius: 6px}
    .gd-env-tutar{font-size:13.5px;font-weight:700;color: var(--text);white-space:nowrap;min-width:110px;text-align:right}
    @media(max-width:768px){.gd-table th:nth-child(4),.gd-table td:nth-child(4){display:none}}
    /* Tab navigasyonu */
    .hc-tabs{display:flex;gap:6px;margin-bottom:22px;border-bottom: 2px solid var(--border)}
    .hc-tab{display:inline-flex;align-items:center;gap:7px;padding:11px 20px;font-size:14px;font-weight:700;color: var(--text-muted);text-decoration:none;border-bottom: 3px solid transparent;margin-bottom:-2px;transition:.15s}
    .hc-tab:hover{color: var(--brand-hover)}
    .hc-tab.aktif{color: var(--text);border-bottom-color: var(--brand)}
</style>
@endpush

@section('content')
<div class="gd-head">
    <h1><i data-lucide="wallet"></i> Harcamalar</h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('admin.giderler.excel', request()->only('q','kategori','durum','bas','bit')) }}" class="gd-btn" style="background: #1f6f43;color: var(--text-inverse)"><i data-lucide="file-spreadsheet" style="width:18px;height:18px"></i> Excel'e Aktar</a>
        @if(Route::has('admin.giderler.tablolara-aktar'))
        <a href="{{ route('admin.giderler.tablolara-aktar', request()->only('q','kategori','durum','bas','bit')) }}" class="gd-btn" style="background: #7c5cbf;color: var(--text-inverse)" onclick="return confirm('Mevcut giderler yeni bir tabloya aktarılsın mı?')"><i data-lucide="table-2" style="width:18px;height:18px"></i> Tablolara Aktar</a>
        @endif
        <a href="{{ route('admin.giderler.olustur') }}" class="gd-btn"><i data-lucide="plus" style="width:18px;height:18px"></i> Yeni Gider</a>
    </div>
</div>

<div class="hc-tabs">
    <a href="{{ route('admin.giderler.index') }}" class="hc-tab aktif"><i data-lucide="wallet" style="width:16px;height:16px"></i> Giderler</a>
    <a href="{{ route('admin.giderler.envanter') }}" class="hc-tab"><i data-lucide="package" style="width:16px;height:16px"></i> Envanter</a>
</div>

@if(session('success'))<div style="background: rgba(16,185,129,.1);border: 1px solid rgba(16,185,129,.3);color: var(--success);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>@endif

@if(!empty($tabloYok))
<div style="background: rgba(245,158,11,.1);border: 1px solid rgba(245,158,11,.3);color: var(--warning);padding:16px;border-radius: 12px">
    Gider tabloları henüz oluşturulmamış. Lütfen <strong>07_giderler_tablolari.sql</strong> dosyasını phpMyAdmin'de çalıştırın.
</div>
@else

{{-- Özet kartları --}}
<div class="gd-ozet">
    <div class="gd-kart t"><div class="gd-kart-l"><i data-lucide="sigma" style="width:14px;height:14px"></i> Toplam Harcama</div><div class="gd-kart-v">₺{{ number_format($ozet['toplam'], 2, ',', '.') }}</div></div>
    <div class="gd-kart a"><div class="gd-kart-l"><i data-lucide="calendar" style="width:14px;height:14px"></i> Bu Ay</div><div class="gd-kart-v">₺{{ number_format($ozet['buAy'], 2, ',', '.') }}</div></div>
    <div class="gd-kart b"><div class="gd-kart-l"><i data-lucide="clock" style="width:14px;height:14px"></i> Bekleyen</div><div class="gd-kart-v">₺{{ number_format($ozet['bekleyen'], 2, ',', '.') }}</div></div>
    <div class="gd-kart g"><div class="gd-kart-l"><i data-lucide="alert-triangle" style="width:14px;height:14px"></i> Gecikmiş</div><div class="gd-kart-v">₺{{ number_format($ozet['gecikmis'], 2, ',', '.') }}</div></div>
</div>

{{-- Filtre --}}
<form method="GET" class="gd-filtre">
    <div class="gd-f-grup">
        <label>Ara</label>
        <input type="text" name="q" value="{{ $filtre['arama'] ?? '' }}" placeholder="Başlık / açıklama">
    </div>
    <div class="gd-f-grup">
        <label>Kategori</label>
        <select name="kategori">
            <option value="">Tümü</option>
            @foreach($kategoriler as $k)
            <option value="{{ $k->id }}" {{ (string)($filtre['katId'] ?? '') === (string)$k->id ? 'selected' : '' }}>{{ $k->ad }}</option>
            @endforeach
        </select>
    </div>
    <div class="gd-f-grup">
        <label>Durum</label>
        <select name="durum">
            <option value="">Tümü</option>
            <option value="bekliyor" {{ ($filtre['durum'] ?? '')==='bekliyor'?'selected':'' }}>Bekliyor</option>
            <option value="odendi" {{ ($filtre['durum'] ?? '')==='odendi'?'selected':'' }}>Ödendi</option>
            <option value="gecikti" {{ ($filtre['durum'] ?? '')==='gecikti'?'selected':'' }}>Gecikti</option>
        </select>
    </div>
    <div class="gd-f-grup">
        <label>Başlangıç</label>
        <input type="date" name="bas" value="{{ $filtre['basTar'] ?? '' }}">
    </div>
    <div class="gd-f-grup">
        <label>Bitiş</label>
        <input type="date" name="bit" value="{{ $filtre['bitTar'] ?? '' }}">
    </div>
    <button type="submit" class="gd-f-btn">Filtrele</button>
    <a href="{{ route('admin.giderler.index') }}" class="gd-f-clear">Temizle</a>
</form>

{{-- Tablo --}}
<div class="gd-card">
    <table class="gd-table">
        <thead>
            <tr>
                <th>Gider</th>
                <th>Kategori</th>
                <th>Son Ödeme</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th style="text-align:right">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($giderler as $g)
            <tr class="row-{{ $g->aciliyet['renk'] }}">
                <td>
                    <span class="gd-baslik">{{ $g->baslik }}</span>
                    @if($g->tekrar !== 'tek')<span style="font-size:11px;color: var(--text-muted);display:block;margin-left:8px">{{ $g->tekrar === 'aylik' ? 'Aylık tekrar' : 'Yıllık tekrar' }}</span>@endif
                </td>
                <td>
                    @if($g->kategori_adi)
                    <span class="gd-kat" style="background: {{ $g->kategori_renk }}1f;color: {{ $g->kategori_renk }}">
                        <i data-lucide="{{ $g->kategori_ikon ?? 'tag' }}" style="width:13px;height:13px"></i> {{ $g->kategori_adi }}
                    </span>
                    @else <span style="color: var(--text-muted)">—</span> @endif
                </td>
                <td>
                    @if($g->son_odeme_tarihi)
                        <div>{{ \Carbon\Carbon::parse($g->son_odeme_tarihi)->format('d.m.Y') }}</div>
                        <span class="gd-aciliyet {{ $g->aciliyet['renk'] }}">{{ $g->aciliyet['etiket'] }}</span>
                    @else <span style="color: var(--text-muted)">—</span> @endif
                </td>
                <td><span class="gd-tutar">₺{{ number_format($g->tutar, 2, ',', '.') }}</span></td>
                <td><span class="gd-durum {{ $g->durum }}">{{ ['odendi'=>'Ödendi','bekliyor'=>'Bekliyor','gecikti'=>'Gecikti'][$g->durum] ?? $g->durum }}</span></td>
                <td style="text-align:right;white-space:nowrap">
                    @if($g->durum !== 'odendi')
                    <form action="{{ route('admin.giderler.durum', $g->id) }}" method="POST" style="display:inline">
                        @csrf <input type="hidden" name="durum" value="odendi">
                        <button type="submit" class="gd-act ok" title="Ödendi işaretle"><i data-lucide="check" style="width:15px;height:15px"></i></button>
                    </form>
                    @endif
                    <a href="{{ route('admin.giderler.duzenle', $g->id) }}" class="gd-act" title="Düzenle"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
                    <form action="{{ route('admin.giderler.sil', $g->id) }}" method="POST" onsubmit="return confirm('Bu gider silinsin mi?')" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="gd-act del" title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6"><div class="gd-empty"><i data-lucide="wallet" style="width:42px;height:42px;opacity:.4;margin-bottom:10px"></i><p>Kayıtlı gider yok.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($giderler instanceof \Illuminate\Pagination\LengthAwarePaginator && $giderler->hasPages())
<div style="margin-top:18px">{{ $giderler->appends($filtre)->links() }}</div>
@endif

{{-- Kategori bazlı toplam (envanter: neye ne kadar harcadın) --}}
@if($kategoriToplam->count() > 0)
@php $maxKat = $kategoriToplam->max('toplam') ?: 1; @endphp
<div class="gd-env">
    <h3><i data-lucide="pie-chart" style="width:18px;height:18px"></i> Kategoriye Göre Harcama</h3>
    @foreach($kategoriToplam as $kt)
    <div class="gd-env-row">
        <span class="gd-env-dot" style="background: {{ $kt->renk ?? '#64748b' }}"></span>
        <span class="gd-env-ad">{{ $kt->ad ?? 'Kategorisiz' }} <span style="color: var(--text-muted);font-weight:500">({{ $kt->adet }})</span></span>
        <span class="gd-env-bar"><span style="width:{{ $maxKat > 0 ? round(($kt->toplam / $maxKat) * 100) : 0 }}%;background: {{ $kt->renk ?? '#64748b' }}"></span></span>
        <span class="gd-env-tutar">₺{{ number_format($kt->toplam, 2, ',', '.') }}</span>
    </div>
    @endforeach
</div>
@endif

@endif
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush