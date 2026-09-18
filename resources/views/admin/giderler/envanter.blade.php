@extends('admin._layout')

@section('title', 'Envanter')

@push('head')
<style>
    .gd-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .gd-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .gd-btn{display:inline-flex;align-items:center;gap:8px;background: var(--brand);color: var(--text);font-weight:700;padding:11px 20px;border-radius: 12px;text-decoration:none;border: none;cursor:pointer;font-size:14px;transition:.2s}
    .gd-btn:hover{background: var(--brand-hover);transform:translateY(-1px)}
    .hc-tabs{display:flex;gap:6px;margin-bottom:22px;border-bottom: 2px solid var(--border)}
    .hc-tab{display:inline-flex;align-items:center;gap:7px;padding:11px 20px;font-size:14px;font-weight:700;color: var(--text-muted);text-decoration:none;border-bottom: 3px solid transparent;margin-bottom:-2px;transition:.15s}
    .hc-tab:hover{color: var(--brand-hover)}
    .hc-tab.aktif{color: var(--text);border-bottom-color: var(--brand)}
    .gd-ozet{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:22px}
    .gd-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:18px 20px}
    .gd-kart-l{font-size:12.5px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:flex;align-items:center;gap:7px}
    .gd-kart-v{font-size:26px;font-weight:800;color: var(--text);margin-top:8px}
    .gd-kart.a .gd-kart-v{color: var(--info)}
    .gd-kart.d .gd-kart-v{color: var(--success)}
    .gd-filtre{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:16px 18px;margin-bottom:18px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
    .gd-f-grup{display:flex;flex-direction:column;gap:5px}
    .gd-f-grup label{font-size:11.5px;font-weight:600;color: var(--text-secondary)}.gd-f-grup input,.gd-f-grup select{border: 1.5px solid var(--border);border-radius: 9px;padding:8px 11px;font-size:13.5px;font-family:inherit;min-width:150px;background: var(--surface);color: var(--text);}
    .gd-f-grup input:focus,.gd-f-grup select:focus{outline: none;border-color: var(--brand)}
    .gd-f-btn{background: var(--brand);color: var(--text);border: none;border-radius: 9px;padding:9px 18px;font-weight:700;font-size:13.5px;cursor:pointer}
    .gd-f-clear{color: var(--text-secondary);text-decoration:none;font-size:13px;padding:9px 10px;font-weight:600}
    .gd-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;overflow:hidden}
    .gd-table{width:100%;border-collapse: collapse}
    .gd-table th{text-align:left;font-size:11.5px;font-weight:700;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:13px 16px;border-bottom: 1px solid var(--border);background: var(--surface)}
    .gd-table td{padding:14px 16px;border-bottom: 1px solid var(--border);font-size:13.5px;color: var(--text-secondary);vertical-align:middle}
    .gd-table tr:last-child td{border-bottom: none}
    .gd-table tr:hover td{background: var(--bg-subtle)}
    .gd-baslik{font-weight:700;color: var(--text)}
    .gd-durum{font-size:12px;font-weight:600;padding:3px 10px;border-radius: 20px;white-space:nowrap}
    .gd-durum.kullanimda{background: rgba(16,185,129,.12);color: var(--success)}
    .gd-durum.depoda{background: rgba(59,130,246,.12);color: #2563eb}
    .gd-durum.arizali{background: rgba(245,158,11,.14);color: var(--warning)}
    .gd-durum.elden_cikti{background: rgba(148,163,184,.18);color: var(--text-secondary)}
    .gd-act{width:32px;height:32px;border-radius: 8px;border: 1px solid var(--border);background: var(--surface);color: var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;transition:.15s}
    .gd-act:hover{border-color: var(--brand);color: var(--brand-hover)}
    .gd-act.del:hover{border-color: var(--danger);color: var(--danger)}
    .gd-empty{padding:56px 20px;text-align:center;color: var(--text-muted)}
    .gd-tutar{font-weight:800;color: var(--text);white-space:nowrap}
</style>
@endpush

@section('content')
<div class="gd-head">
    <h1><i data-lucide="package"></i> Harcamalar</h1>
    <a href="{{ route('admin.giderler.envanter.olustur') }}" class="gd-btn"><i data-lucide="plus" style="width:18px;height:18px"></i> Yeni Kalem</a>
</div>

<div class="hc-tabs">
    <a href="{{ route('admin.giderler.index') }}" class="hc-tab"><i data-lucide="wallet" style="width:16px;height:16px"></i> Giderler</a>
    <a href="{{ route('admin.giderler.envanter') }}" class="hc-tab aktif"><i data-lucide="package" style="width:16px;height:16px"></i> Envanter</a>
</div>

@if(session('success'))<div style="background: rgba(16,185,129,.1);border: 1px solid rgba(16,185,129,.3);color: var(--success);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>@endif

@if(!empty($tabloYok))
<div style="background: rgba(245,158,11,.1);border: 1px solid rgba(245,158,11,.3);color: var(--warning);padding:16px;border-radius: 12px">
    Envanter tablosu henüz oluşturulmamış. Lütfen <strong>10_envanter_tablosu.sql</strong> dosyasını phpMyAdmin'de çalıştırın.
</div>
@else

<div class="gd-ozet">
    <div class="gd-kart"><div class="gd-kart-l"><i data-lucide="layers" style="width:14px;height:14px"></i> Kalem Sayısı</div><div class="gd-kart-v">{{ $ozet['kalem'] }}</div></div>
    <div class="gd-kart a"><div class="gd-kart-l"><i data-lucide="boxes" style="width:14px;height:14px"></i> Toplam Adet</div><div class="gd-kart-v">{{ $ozet['toplam_adet'] }}</div></div>
    <div class="gd-kart d"><div class="gd-kart-l"><i data-lucide="banknote" style="width:14px;height:14px"></i> Toplam Değer</div><div class="gd-kart-v">₺{{ number_format($ozet['toplam_deger'], 2, ',', '.') }}</div></div>
</div>

<form method="GET" class="gd-filtre">
    <div class="gd-f-grup">
        <label>Ara</label>
        <input type="text" name="q" value="{{ $filtre['arama'] ?? '' }}" placeholder="Ürün / kategori / açıklama">
    </div>
    <div class="gd-f-grup">
        <label>Durum</label>
        <select name="durum">
            <option value="">Tümü</option>
            <option value="kullanimda" {{ ($filtre['durum'] ?? '')==='kullanimda'?'selected':'' }}>Kullanımda</option>
            <option value="depoda" {{ ($filtre['durum'] ?? '')==='depoda'?'selected':'' }}>Depoda</option>
            <option value="arizali" {{ ($filtre['durum'] ?? '')==='arizali'?'selected':'' }}>Arızalı</option>
            <option value="elden_cikti" {{ ($filtre['durum'] ?? '')==='elden_cikti'?'selected':'' }}>Elden Çıktı</option>
        </select>
    </div>
    <button type="submit" class="gd-f-btn">Filtrele</button>
    <a href="{{ route('admin.giderler.envanter') }}" class="gd-f-clear">Temizle</a>
</form>

<div class="gd-card">
    <table class="gd-table">
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Kategori</th>
                <th>Adet</th>
                <th>Birim Fiyat</th>
                <th>Toplam</th>
                <th>Durum</th>
                <th style="text-align:right">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($envanterler as $e)
            <tr>
                <td><span class="gd-baslik">{{ $e->ad }}</span></td>
                <td>{{ $e->kategori ?? '—' }}</td>
                <td>{{ $e->adet }}</td>
                <td>₺{{ number_format($e->birim_fiyat, 2, ',', '.') }}</td>
                <td><span class="gd-tutar">₺{{ number_format($e->adet * $e->birim_fiyat, 2, ',', '.') }}</span></td>
                <td><span class="gd-durum {{ $e->durum }}">{{ ['kullanimda'=>'Kullanımda','depoda'=>'Depoda','arizali'=>'Arızalı','elden_cikti'=>'Elden Çıktı'][$e->durum] ?? $e->durum }}</span></td>
                <td style="text-align:right;white-space:nowrap">
                    <a href="{{ route('admin.giderler.envanter.duzenle', $e->id) }}" class="gd-act" title="Düzenle"><i data-lucide="pencil" style="width:15px;height:15px"></i></a>
                    <form action="{{ route('admin.giderler.envanter.sil', $e->id) }}" method="POST" onsubmit="return confirm('Bu kalem silinsin mi?')" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="gd-act del" title="Sil"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="gd-empty"><i data-lucide="package" style="width:42px;height:42px;opacity:.4;margin-bottom:10px"></i><p>Kayıtlı envanter kalemi yok.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($envanterler instanceof \Illuminate\Pagination\LengthAwarePaginator && $envanterler->hasPages())
<div style="margin-top:18px">{{ $envanterler->appends($filtre)->links() }}</div>
@endif

@endif
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush