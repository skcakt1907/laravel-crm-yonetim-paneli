@extends('admin._layout')

@section('title', 'Ayın İş Ortakları')

@push('head')
<style>
    .io-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px}
    .io-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .io-sub{color: var(--text-muted);font-size:13.5px;margin-bottom:20px}
    .io-tabs{display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap}
    .io-tab{padding:9px 18px;border-radius: 22px;font-size:13.5px;font-weight:700;text-decoration:none;border: 1.5px solid var(--border);color: var(--text-secondary);transition:.15s}
    .io-tab:hover{border-color: var(--brand);color: var(--brand-hover)}
    .io-tab.aktif{background: var(--brand);border-color: var(--brand);color: var(--text)}
    .io-ozet{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:22px}
    .io-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 14px;padding:18px 20px}
    .io-kart-l{font-size:12px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;display:flex;align-items:center;gap:7px}
    .io-kart-v{font-size:25px;font-weight:800;color: var(--text);margin-top:8px}
    .io-kart.c .io-kart-v{color: var(--brand)}
    .io-kart.t .io-kart-v{color: var(--success)}
    .io-kart.b .io-kart-v{color: var(--warning)}
    .io-kart.m .io-kart-v{color: var(--info)}
    /* Grafik */
    .io-grafik{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:22px;margin-bottom:22px}
    .io-grafik h3{font-size:15px;font-weight:700;margin:0 0 4px;display:flex;align-items:center;gap:8px}
    .io-grafik .alt{color: var(--text-muted);font-size:12.5px;margin-bottom:18px}
    .io-bar-row{display:flex;align-items:center;gap:12px;margin-bottom:14px}
    .io-bar-ad{width:160px;flex-shrink:0;font-size:13px;font-weight:600;color: var(--text-secondary);text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .io-bar-track{flex:1;height:26px;background: var(--bg-subtle);border-radius: 7px;overflow:hidden;display:flex}
    .io-bar-t{height:100%;background: var(--success);transition:width .5s}
    .io-bar-b{height:100%;background: var(--warning);transition:width .5s}
    .io-bar-val{width:130px;flex-shrink:0;font-size:13px;font-weight:700;color: var(--text);white-space:nowrap}
    .io-legend{display:flex;gap:18px;margin-top:8px;font-size:12.5px;color: var(--text-secondary)}
    .io-legend span{display:inline-flex;align-items:center;gap:6px}
    .io-dot{width:11px;height:11px;border-radius: 3px;display:inline-block}
    /* Liste */
    .io-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;overflow:hidden}
    .io-table{width:100%;border-collapse: collapse}
    .io-table th{text-align:left;font-size:11.5px;font-weight:700;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:13px 16px;border-bottom: 1px solid var(--border);background: var(--surface)}
    .io-table td{padding:14px 16px;border-bottom: 1px solid var(--border);font-size:13.5px;color: var(--text-secondary);vertical-align:middle}
    .io-table tr:last-child td{border-bottom: none}
    .io-table tr:hover td{background: var(--bg-subtle)}
    .io-rank{width:30px;height:30px;border-radius: 9px;background: var(--bg-subtle);color: var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:13px}
    .io-rank.r1{background: var(--warning-soft);color: var(--warning)}
    .io-rank.r2{background: #e5e7eb;color: var(--text-secondary)}
    .io-rank.r3{background: #fde9d8;color: #9a3412}
    .io-ad{font-weight:700;color: var(--text)}
    .io-tutar{font-weight:800;white-space:nowrap}
    .io-tutar.t{color: var(--success)}
    .io-tutar.b{color: var(--warning)}
    .io-tutar.top{color: var(--text)}
    .io-empty{padding:56px 20px;text-align:center;color: var(--text-muted)}
    @media(max-width:768px){.io-bar-ad{width:90px}.io-bar-val{width:90px;font-size:12px}.io-table th:nth-child(3),.io-table td:nth-child(3){display:none}}
</style>
@endpush

@section('content')
<div class="io-head">
    <h1><i data-lucide="trophy"></i> Ayın İş Ortakları</h1>
</div>
<div class="io-sub">Bize en çok kazandıran müşteriler — faturalar üzerinden ciro sıralaması.</div>

@if(!empty($tabloYok))
<div style="background: rgba(245,158,11,.1);border: 1px solid rgba(245,158,11,.3);color: var(--warning);padding:16px;border-radius: 12px">
    Faturalar tablosu bulunamadı.
</div>
@else

{{-- Dönem sekmeleri --}}
<div class="io-tabs">
    <a href="{{ route('admin.is-ortaklari.index', ['donem'=>'ay']) }}" class="io-tab {{ $donem==='ay'?'aktif':'' }}">Bu Ay</a>
    <a href="{{ route('admin.is-ortaklari.index', ['donem'=>'uc_ay']) }}" class="io-tab {{ $donem==='uc_ay'?'aktif':'' }}">Son 3 Ay</a>
    <a href="{{ route('admin.is-ortaklari.index', ['donem'=>'yil']) }}" class="io-tab {{ $donem==='yil'?'aktif':'' }}">Bu Yıl</a>
    <span style="align-self:center;color: var(--text-muted);font-size:13px;margin-left:6px">{{ $aralikMetni }}</span>
</div>

{{-- Özet kartları --}}
<div class="io-ozet">
    <div class="io-kart c"><div class="io-kart-l"><i data-lucide="circle-dollar-sign" style="width:14px;height:14px"></i> Toplam Ciro</div><div class="io-kart-v">₺{{ number_format($ozet['toplam_ciro'], 0, ',', '.') }}</div></div>
    <div class="io-kart t"><div class="io-kart-l"><i data-lucide="check-circle-2" style="width:14px;height:14px"></i> Tahsil Edilen</div><div class="io-kart-v">₺{{ number_format($ozet['tahsil_edilen'], 0, ',', '.') }}</div></div>
    <div class="io-kart b"><div class="io-kart-l"><i data-lucide="clock" style="width:14px;height:14px"></i> Bekleyen</div><div class="io-kart-v">₺{{ number_format($ozet['bekleyen'], 0, ',', '.') }}</div></div>
    <div class="io-kart m"><div class="io-kart-l"><i data-lucide="users" style="width:14px;height:14px"></i> Müşteri Sayısı</div><div class="io-kart-v">{{ $ozet['musteri_sayisi'] }}</div></div>
</div>

{{-- Grafik (en çok kazandıran 8) --}}
@if(count($grafik) > 0)
@php $maxTop = collect($grafik)->max('toplam') ?: 1; @endphp
<div class="io-grafik">
    <h3><i data-lucide="bar-chart-3" style="width:18px;height:18px"></i> En Çok Kazandıran Müşteriler</h3>
    <div class="alt">İlk {{ count($grafik) }} müşteri · yeşil tahsil edilen, turuncu bekleyen</div>
    @foreach($grafik as $g)
    <div class="io-bar-row">
        <div class="io-bar-ad" title="{{ $g['ad'] }}">{{ $g['ad'] }}</div>
        <div class="io-bar-track">
            <div class="io-bar-t" style="width:{{ $maxTop>0 ? ($g['tahsil']/$maxTop*100) : 0 }}%" title="Tahsil: ₺{{ number_format($g['tahsil'],0,',','.') }}"></div>
            <div class="io-bar-b" style="width:{{ $maxTop>0 ? ($g['bekleyen']/$maxTop*100) : 0 }}%" title="Bekleyen: ₺{{ number_format($g['bekleyen'],0,',','.') }}"></div>
        </div>
        <div class="io-bar-val">₺{{ number_format($g['toplam'], 0, ',', '.') }}</div>
    </div>
    @endforeach
    <div class="io-legend">
        <span><i class="io-dot" style="background: var(--success)"></i> Tahsil edilen</span>
        <span><i class="io-dot" style="background: var(--warning)"></i> Bekleyen</span>
    </div>
</div>
@endif

{{-- Liste --}}
<div class="io-card">
    <table class="io-table">
        <thead>
            <tr>
                <th style="width:50px">#</th>
                <th>Müşteri</th>
                <th>Fatura</th>
                <th>Tahsil Edilen</th>
                <th>Bekleyen</th>
                <th>Toplam</th>
            </tr>
        </thead>
        <tbody>
            @forelse($liste as $i => $m)
            <tr>
                <td><span class="io-rank {{ $i===0?'r1':($i===1?'r2':($i===2?'r3':'')) }}">{{ $i+1 }}</span></td>
                <td><span class="io-ad">{{ $m->gorunen_ad }}</span>@if(!empty($m->email))<div style="font-size:12px;color: var(--text-muted)">{{ $m->email }}</div>@endif</td>
                <td>{{ $m->fatura_adet }}</td>
                <td><span class="io-tutar t">₺{{ number_format($m->tahsil_edilen, 2, ',', '.') }}</span></td>
                <td><span class="io-tutar b">₺{{ number_format($m->bekleyen, 2, ',', '.') }}</span></td>
                <td><span class="io-tutar top">₺{{ number_format($m->toplam, 2, ',', '.') }}</span></td>
            </tr>
            @empty
            <tr><td colspan="6"><div class="io-empty"><i data-lucide="trophy" style="width:42px;height:42px;opacity:.4;margin-bottom:10px"></i><p>Bu dönemde fatura bulunamadı.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endif
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush