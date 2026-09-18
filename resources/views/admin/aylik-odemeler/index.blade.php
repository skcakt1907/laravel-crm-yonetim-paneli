@extends('admin._layout')

@section('title', 'Aylık Bildirimli Ödemeler')

@push('head')
<style>
    .gd-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .gd-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .gd-btn{display:inline-flex;align-items:center;gap:8px;background: var(--brand);color: var(--text);font-weight:700;
        padding:11px 20px;border-radius: 12px;text-decoration:none;border: none;cursor:pointer;font-size:14px;transition:.2s}
    .gd-btn:hover{background: var(--brand-hover);transform:translateY(-1px)}
    .gd-ozet{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:22px}
    .gd-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:18px 20px}
    .gd-kart-l{font-size:12.5px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:flex;align-items:center;gap:7px}
    .gd-kart-v{font-size:26px;font-weight:800;color: var(--text);margin-top:8px}
    .gd-kart.a .gd-kart-v{color: var(--info)}.gd-kart.b .gd-kart-v{color: var(--warning)}.gd-kart.g .gd-kart-v{color: var(--success)}
    .gd-filtre{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:16px 18px;margin-bottom:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
    .gd-f-grup{display:flex;flex-direction:column;gap:5px}
    .gd-f-grup label{font-size:11.5px;font-weight:600;color: var(--text-secondary)}.gd-f-grup input,.gd-f-grup select{border: 1.5px solid var(--border);border-radius: 9px;padding:8px 11px;font-size:13.5px;font-family:inherit;color: var(--text);min-width:150px;background: var(--surface);}
    .gd-f-grup input:focus,.gd-f-grup select:focus{outline: none;border-color: var(--brand)}
    .gd-f-btn{background: var(--brand);color: var(--text);border: none;border-radius: 9px;padding:9px 18px;font-weight:700;font-size:13.5px;cursor:pointer}
    .gd-f-clear{color: var(--text-secondary);text-decoration:none;font-size:13px;padding:9px 10px;font-weight:600}
    .gd-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;overflow:hidden}
    .gd-table{width:100%;border-collapse: collapse}
    .gd-table th{text-align:left;font-size:11.5px;font-weight:700;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:13px 16px;border-bottom: 1px solid var(--border);background: var(--surface)}
    .gd-table td{padding:14px 16px;border-bottom: 1px solid var(--border);font-size:13.5px;color: var(--text-secondary);vertical-align:middle}
    .gd-table tr:last-child td{border-bottom: none}
    .gd-table tr:hover td{background: var(--bg-subtle)}
    .gd-table tr.row-kirmizi td{background: rgba(239,68,68,.10)}
    .gd-table tr.row-kirmizi:hover td{background: rgba(239,68,68,.16)}
    .gd-table tr.row-sari td{background: rgba(245,158,11,.12)}
    .gd-table tr.row-sari:hover td{background: rgba(245,158,11,.18)}
    .gd-table tr.row-yesil td{background: rgba(16,185,129,.10)}
    .gd-table tr.row-yesil:hover td{background: rgba(16,185,129,.16)}
    .gd-table tr.row-notr td{background: transparent}
    .gd-table tr.row-kirmizi td:first-child{box-shadow:inset 4px 0 0 #ef4444}
    .gd-table tr.row-sari td:first-child{box-shadow:inset 4px 0 0 #f59e0b}
    .gd-table tr.row-yesil td:first-child{box-shadow:inset 4px 0 0 #10b981}
    .gd-baslik{font-weight:700;color: var(--text)}
    .gd-periyot{font-size:11px;color: var(--text-muted);display:block;margin-top:2px}
    .gd-kat{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:3px 10px;border-radius: 20px}
    .gd-tutar{font-weight:800;font-size:15px;color: var(--text);white-space:nowrap}
    .gd-jeton{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:3px 9px;border-radius: 20px;white-space:nowrap}
    .gd-jeton .dot{width:8px;height:8px;border-radius: 50%}
    .gd-jeton.kirmizi{background: rgba(239,68,68,.12);color: var(--danger)}.gd-jeton.kirmizi .dot{background: var(--danger)}
    .gd-jeton.sari{background: rgba(245,158,11,.14);color: var(--warning)}.gd-jeton.sari .dot{background: var(--warning)}
    .gd-jeton.yesil{background: rgba(16,185,129,.12);color: var(--success)}.gd-jeton.yesil .dot{background: var(--success)}
    .gd-jeton.notr{background: rgba(148,163,184,.15);color: var(--text-secondary)}.gd-jeton.notr .dot{background: #94a3b8}
    .gd-durum{font-size:12px;font-weight:700;padding:3px 10px;border-radius: 20px}
    .gd-durum.odendi{background: rgba(16,185,129,.14);color: var(--success)}
    .gd-durum.bekliyor{background: rgba(239,68,68,.12);color: var(--danger)}
    .gd-act{width:32px;height:32px;border-radius: 8px;border: 1px solid var(--border);background: var(--surface);color: var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;transition:.15s}
    .gd-act:hover{border-color: var(--brand);color: var(--brand-hover)}
    .gd-act.del:hover{border-color: var(--danger);color: var(--danger)}
    .gd-act.ok:hover{border-color: var(--success);color: var(--success)}
    .gd-act.undo:hover{border-color: var(--warning);color: var(--warning)}
    .gd-empty{padding:56px 20px;text-align:center;color: var(--text-muted)}
    @media(max-width:768px){.gd-table th:nth-child(2),.gd-table td:nth-child(2){display:none}}
</style>
@endpush

@section('content')
<div class="gd-head">
    <h1><i data-lucide="calendar-clock"></i> Aylık Bildirimli Ödemeler</h1>
    <a href="{{ route('admin.aylik-odemeler.olustur') }}" class="gd-btn"><i data-lucide="plus" style="width:18px;height:18px"></i> Yeni Ödeme</a>
</div>

@if(session('success'))<div style="background: rgba(16,185,129,.1);border: 1px solid rgba(16,185,129,.3);color: var(--success);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>@endif

@if(!empty($tabloYok))
<div style="background: rgba(245,158,11,.1);border: 1px solid rgba(245,158,11,.3);color: var(--warning);padding:16px;border-radius: 12px">
    Tablo henüz oluşturulmamış. Lütfen <strong>aylik_odemeler.sql</strong> dosyasını phpMyAdmin'de çalıştırın (ya da migration).
</div>
@else

<div class="gd-ozet">
    <div class="gd-kart"><div class="gd-kart-l"><i data-lucide="sigma" style="width:14px;height:14px"></i> Toplam</div><div class="gd-kart-v">₺{{ number_format($ozet['toplam'], 2, ',', '.') }}</div></div>
    <div class="gd-kart b"><div class="gd-kart-l"><i data-lucide="clock" style="width:14px;height:14px"></i> Bekleyen</div><div class="gd-kart-v">₺{{ number_format($ozet['bekleyen'], 2, ',', '.') }}</div></div>
    <div class="gd-kart g"><div class="gd-kart-l"><i data-lucide="check-circle" style="width:14px;height:14px"></i> Ödenen</div><div class="gd-kart-v">₺{{ number_format($ozet['odenen'], 2, ',', '.') }}</div></div>
    <div class="gd-kart a"><div class="gd-kart-l"><i data-lucide="bell-ring" style="width:14px;height:14px"></i> 7 Gün İçinde</div><div class="gd-kart-v">{{ $ozet['yaklasan'] }}</div></div>
</div>

<form method="GET" class="gd-filtre">
    <div class="gd-f-grup"><label>Ara</label><input type="text" name="q" value="{{ $filtre['arama'] ?? '' }}" placeholder="Başlık / açıklama"></div>
    <div class="gd-f-grup"><label>Durum</label>
        <select name="durum">
            <option value="">Tümü</option>
            <option value="bekliyor" {{ ($filtre['durum'] ?? '')==='bekliyor'?'selected':'' }}>Bekliyor</option>
            <option value="odendi" {{ ($filtre['durum'] ?? '')==='odendi'?'selected':'' }}>Ödendi</option>
        </select>
    </div>
    <button type="submit" class="gd-f-btn">Filtrele</button>
    <a href="{{ route('admin.aylik-odemeler.index') }}" class="gd-f-clear">Temizle</a>
</form>

@php
    $periyotAd = function($ay){
        return match((int)$ay){ 1=>'Aylık', 3=>'3 ayda bir', 6=>'6 ayda bir', 12=>'Yıllık', default=>$ay.' ayda bir' };
    };
@endphp

<div class="gd-card">
    <table class="gd-table">
        <thead>
            <tr>
                <th>Ödeme</th>
                <th>Kategori</th>
                <th>Son Ödeme</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th style="text-align:right">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($odemeler as $o)
            <tr class="row-{{ $o->aciliyet['renk'] }}">
                <td>
                    <span class="gd-baslik">{{ $o->baslik }}</span>
                    <span class="gd-periyot"><i data-lucide="repeat" style="width:11px;height:11px;vertical-align:-1px"></i> {{ $periyotAd($o->periyot_ay) }}</span>
                </td>
                <td>
                    @if($o->kategori_adi)
                    <span class="gd-kat" style="background: {{ $o->kategori_renk }}1f;color: {{ $o->kategori_renk }}">
                        <i data-lucide="{{ $o->kategori_ikon ?? 'tag' }}" style="width:13px;height:13px"></i>
                        @if(mb_strtolower($o->kategori_adi,'UTF-8')==='diğer' && !empty($o->kategori_diger)){{ $o->kategori_diger }}@else{{ $o->kategori_adi }}@endif
                    </span>
                    @else <span style="color: var(--text-muted)">—</span> @endif
                </td>
                <td>
                    <div>{{ \Carbon\Carbon::parse($o->son_odeme_tarihi)->format('d.m.Y') }}</div>
                    <span class="gd-jeton {{ $o->aciliyet['renk'] }}"><span class="dot"></span>{{ $o->aciliyet['etiket'] }}</span>
                </td>
                <td><span class="gd-tutar">₺{{ number_format($o->tutar, 2, ',', '.') }}</span></td>
                <td><span class="gd-durum {{ $o->durum }}">{{ $o->durum === 'odendi' ? 'Ödendi' : 'Ödenmedi' }}</span></td>
                <td style="text-align:right;white-space:nowrap">
                    @if($o->durum !== 'odendi')
                    <form action="{{ route('admin.aylik-odemeler.durum', $o->id) }}" method="POST" style="display:inline">
                        @csrf <input type="hidden" name="durum" value="odendi">
                        <button type="submit" class="gd-act ok" title="Ödendi işaretle"><i data-lucide="check" style="width:15px;height:15px"></i></button>
                    </form>
                    @else
                    <form action="{{ route('admin.aylik-odemeler.durum', $o->id) }}" method="POST" style="display:inline">
                        @csrf <input type="hidden" name="durum" value="bekliyor">
                        <button type="submit" class="gd-act undo" title="Ödenmedi yap"><i data-lucide="rotate-ccw" style="width:15px;height:15px"></i></button>
                    </form>
                    @endif
                    <a href="{{ route('admin.aylik-odemeler.duzenle', $o->id) }}" class="gd-act" title="Düzenle"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
                    <form action="{{ route('admin.aylik-odemeler.sil', $o->id) }}" method="POST" onsubmit="return confirm('Bu ödeme silinsin mi?')" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="gd-act del" title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6"><div class="gd-empty"><i data-lucide="calendar-clock" style="width:42px;height:42px;opacity:.4;margin-bottom:10px"></i><p>Kayıtlı aylık ödeme yok.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endif
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush
