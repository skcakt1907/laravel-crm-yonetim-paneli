@extends('layouts.bayi')

@section('title', __('messages.reseller_dashboard'))

@section('panel_content')
@php
    $chartMonths = $aylik_satis->pluck('ay')->toArray();
    $chartSales = $aylik_satis->pluck('adet')->toArray();
    $chartEarnings = $aylik_satis->pluck('kazanc')->toArray();
    if (empty($chartMonths)) {
        $now = \Carbon\Carbon::now();
        $chartMonths = [];
        for ($i = 5; $i >= 0; $i--) $chartMonths[] = $now->copy()->subMonths($i)->format('Y-m');
        $chartSales = array_fill(0, 6, 0);
        $chartEarnings = array_fill(0, 6, 0);
    }
    $currency = session('currency', 'TRY');
    $fmt = fn($v) => \App\Helpers\CurrencyHelper::format((float)($v ?? 0), $currency);
    $komisyon = $bayi->komisyon_oran ?? $bayi->komisyon_orani ?? 10;
    $kredi = $bayi->kredi ?? $bayi->bakiye ?? null;
@endphp

<style>
    /* ===== BAYİ DASHBOARD — modern light tasarım ===== */
    .bd-wrap{--brand:#b8b62e;--brand2:#8a8a1f;--ink:#1f2430;--mut:#6b7280;--line:#eceae0;font-family:'Poppins','Inter',sans-serif}
    .bd-hero{position:relative;overflow:hidden;border-radius: 22px;padding:28px 30px;margin-bottom:22px;
        background: linear-gradient(120deg,#1c1d12 0%,#2c2e16 45%,#3a3c1c 100%);color: var(--text-inverse);
        box-shadow:0 18px 40px -18px rgba(138,138,31,.55)}
    .bd-hero:before{content:"";position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius: 50%;
        background: radial-gradient(circle,rgba(212,208,102,.35),transparent 70%)}
    .bd-hero h1{font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;color: var(--text-inverse) !important}
    .bd-hero .sub{color: rgba(255,255,255,.72);margin-top:6px;font-size:14px}
    .bd-hero .chips{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    .bd-chip{background: rgba(255,255,255,.1);border: 1px solid rgba(212,208,102,.3);backdrop-filter:blur(6px);
        padding:8px 14px;border-radius: 12px;font-size:13px;display:flex;align-items:center;gap:7px}
    .bd-chip b{color: #e7e36a}
    .bd-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
    .kpi{background: var(--surface);border: 1px solid var(--line);border-radius: 18px;padding:20px;position:relative;
        box-shadow:0 4px 18px rgba(0,0,0,.04);transition:transform .25s,box-shadow .25s;overflow:hidden;min-width:0}
    .kpi:hover{transform:translateY(-4px);box-shadow:0 16px 32px -12px rgba(138,138,31,.3)}
    .kpi:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background: var(--accent,var(--brand))}
    .kpi .ic{width:46px;height:46px;border-radius: 13px;display:flex;align-items:center;justify-content:center;font-size:22px;
        background: var(--soft,#f6f5e8)}
    .kpi .lbl{font-size:11px;text-transform:uppercase;letter-spacing:1px;color: var(--mut);font-weight:700;margin-top:14px}
    .kpi .val{font-size:26px;font-weight:800;color: var(--ink);line-height:1.15;margin-top:4px;word-break:break-word}
    .kpi .trend{font-size:12px;margin-top:8px;font-weight:600}
    .bd-cols{display:grid;grid-template-columns:1.6fr 1fr;gap:18px;margin-bottom:22px;align-items:start}
    @media(max-width:1024px){.bd-grid{grid-template-columns:repeat(2,1fr)}.bd-cols{grid-template-columns:1fr}}
    .panel{background: var(--surface);border: 1px solid var(--line);border-radius: 18px;box-shadow:0 4px 18px rgba(0,0,0,.04);min-width:0}
    .panel-h{padding:18px 22px;border-bottom: 1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
    .panel-h h2{font-size:16px;font-weight:700;color: var(--ink);margin:0;display:flex;align-items:center;gap:8px}
    .panel-h a{font-size:13px;color: var(--brand2);font-weight:600;text-decoration:none}
    .panel-b{padding:20px 22px}
    .qa{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
    .qa a{display:flex;align-items:center;gap:12px;padding:14px;border-radius: 14px;text-decoration:none;
        border: 1px solid var(--line);background: #fbfbf6;color: var(--ink);font-weight:600;font-size:14px;transition:.2s}
    .qa a:hover{border-color: var(--brand);background: #fefce8;transform:translateX(2px)}
    .qa .qi{width:38px;height:38px;border-radius: 11px;background: linear-gradient(135deg,var(--brand),var(--brand2));
        color: var(--text-inverse);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
    .mini{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom: 1px dashed var(--line)}
    .mini:last-child{border-bottom: 0}
    .mini .ml{font-size:13px;color: var(--mut);display:flex;align-items:center;gap:8px}
    .mini .mv{font-weight:800;color: var(--ink)}
    .bd-table{width:100%;border-collapse: collapse}
    .bd-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.6px;color: var(--mut);
        font-weight:700;padding:12px 22px;background: #faf9f0;border-bottom: 1px solid var(--line)}
    .bd-table td{padding:14px 22px;border-bottom: 1px solid #f3f2ea;font-size:14px;color: var(--ink)}
    .bd-table tr:hover td{background: #fefce8}
    .bd-table .r{text-align:right}
    .badge-y{background: var(--warning-soft);color: var(--warning);padding:3px 10px;border-radius: 8px;font-size:12px;font-weight:700}
    .empty{text-align:center;padding:48px 20px;color: var(--mut)}
</style>

<div class="bd-wrap">

    <!-- HERO -->
    <div class="bd-hero">
        <h1>👋 Hoş geldin, {{ $yonetici->adi ?? $yonetici->kullaniciadi ?? 'Bayi' }}</h1>
        <div class="sub">Bayi panelinize hoş geldiniz — bu ay <b style="color: #e7e36a">{{ $stats['bu_ay_satis'] ?? 0 }}</b> satış gerçekleştirdiniz.</div>
        <div class="chips">
            <span class="bd-chip">🆔 Bayi Kodu: <b>{{ $bayi->bayi_kodu ?? '—' }}</b></span>
            <span class="bd-chip">💹 Komisyon: <b>%{{ $komisyon }}</b></span>
            @if($kredi !== null)<span class="bd-chip">💳 Bakiye: <b>{{ $fmt($kredi) }}</b></span>@endif
            <span class="bd-chip">👥 Müşteri: <b>{{ $stats['toplam_musteri'] ?? 0 }}</b></span>
        </div>
    </div>

    <!-- KPI -->
    <div class="bd-grid">
        <div class="kpi" style="--accent:#b8b62e;--soft:#f6f5e8">
            <div class="ic">🛒</div>
            <div class="lbl">Toplam Satış</div>
            <div class="val">{{ $stats['toplam_satis'] ?? 0 }}</div>
            <div class="trend" style="color: var(--brand-hover)">Bugün +{{ $stats['bugun_satis'] ?? 0 }} · Bu ay {{ $stats['bu_ay_satis'] ?? 0 }}</div>
        </div>
        <div class="kpi" style="--accent:#10b981;--soft:#e7f7f0">
            <div class="ic">💰</div>
            <div class="lbl">Toplam Kazanç</div>
            <div class="val">{{ $fmt($stats['toplam_kazanc'] ?? 0) }}</div>
            <div class="trend" style="color: var(--success)">Bu ay {{ $fmt($stats['bu_ay_kazanc'] ?? 0) }}</div>
        </div>
        <div class="kpi" style="--accent:#f59e0b;--soft:#fef3c7">
            <div class="ic">⏳</div>
            <div class="lbl">Bekleyen Kazanç</div>
            <div class="val">{{ $fmt($stats['bekleyen_kazanc'] ?? 0) }}</div>
            <div class="trend" style="color: #d97706">{{ $stats['bekleyen_odeme'] ?? 0 }} ödeme talebi</div>
        </div>
        <div class="kpi" style="--accent:#6366f1;--soft:#eef0fe">
            <div class="ic">✅</div>
            <div class="lbl">Ödenen Kazanç</div>
            <div class="val">{{ $fmt($stats['odenen_kazanc'] ?? 0) }}</div>
            <div class="trend" style="color: #4f46e5">{{ $stats['onaylanan_odeme'] ?? 0 }} onaylı ödeme</div>
        </div>
    </div>

    <!-- CHART + QUICK ACTIONS -->
    <div class="bd-cols">
        <div class="panel">
            <div class="panel-h"><h2>📈 Kazanç Analizi</h2><span style="font-size:12px;color: var(--mut)">{{ date('Y') }}</span></div>
            <div class="panel-b"><canvas id="resellerEarningsChart" height="120"></canvas></div>
        </div>
        <div class="panel">
            <div class="panel-h"><h2>⚡ Hızlı Eylemler</h2></div>
            <div class="panel-b">
                <div class="qa">
                    <a href="{{ route('admin.bayi.musteri.ekle') }}"><span class="qi">➕</span> Yeni Müşteri</a>
                    <a href="{{ route('admin.bayi.odeme.talep.olustur') }}"><span class="qi">💳</span> Ödeme Talebi</a>
                    <a href="{{ route('admin.bayi.satislar') }}"><span class="qi">🛒</span> Satışlarım</a>
                    <a href="{{ route('admin.bayi.referans.link') }}"><span class="qi">🔗</span> Referans Linki</a>
                </div>
                <div style="margin-top:18px">
                    <div class="mini"><span class="ml">💵 Bu Ay Kazanç</span><span class="mv">{{ $fmt($stats['bu_ay_kazanc'] ?? 0) }}</span></div>
                    <div class="mini"><span class="ml">⏳ Bekleyen Ödeme Talebi</span><span class="mv">{{ $stats['bekleyen_odeme'] ?? 0 }}</span></div>
                    <div class="mini"><span class="ml">✅ Onaylanan Ödeme</span><span class="mv">{{ $stats['onaylanan_odeme'] ?? 0 }}</span></div>
                    <div class="mini"><span class="ml">👥 Toplam Müşteri</span><span class="mv">{{ $stats['toplam_musteri'] ?? 0 }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- SON SATIŞLAR -->
    <div class="panel">
        <div class="panel-h">
            <h2>🛒 Son Satışlar</h2>
            <a href="{{ route('admin.bayi.satislar') }}">Tümü →</a>
        </div>
        <div style="overflow-x:auto">
            <table class="bd-table">
                <thead>
                    <tr>
                        <th>#</th><th>Müşteri</th><th>Paket</th>
                        <th class="r">Tutar</th><th class="r">Komisyon</th><th class="r">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($son_satislar as $satis)
                    <tr>
                        <td style="color: var(--mut)">#{{ $satis->id }}</td>
                        <td style="font-weight:600">{{ $satis->musteri_adi ?? '-' }}</td>
                        <td>{{ $satis->paket_adi ?? '-' }}</td>
                        <td class="r" style="font-weight:700">{{ $fmt($satis->satis_tutari ?? 0) }}</td>
                        <td class="r"><span class="badge-y">{{ $fmt($satis->komisyon_tutari ?? 0) }}</span></td>
                        <td class="r" style="font-size:12px;color: var(--mut)">{{ $satis->created_at ? \Carbon\Carbon::parse($satis->created_at)->format('d.m.Y H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><div class="empty"><div style="font-size:46px;margin-bottom:8px">🛒</div>Henüz satış yok</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const labels = @json(collect($chartMonths)->map(fn($ay) => \Carbon\Carbon::parse($ay.'-01')->format('m.Y')));
    const sales = @json($chartSales);
    const earnings = @json($chartEarnings);
    const ctx = document.getElementById('resellerEarningsChart');
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [
            { label:'Satış', data:sales, borderColor:'#b8b62e', backgroundColor:'rgba(184,182,46,.15)', tension:.35, fill:true, yAxisID:'y', borderWidth: 2, pointRadius:3 },
            { label:'Kazanç', data:earnings, borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,.12)', tension:.35, fill:true, yAxisID:'y1', borderWidth: 2, pointRadius:3 }
        ]},
        options: {
            responsive:true, maintainAspectRatio:true,
            interaction:{ mode:'index', intersect:false },
            plugins:{ legend:{ labels:{ usePointStyle:true, color:'#4b5563', font:{family:'Poppins'} } } },
            scales:{
                x:{ grid:{ color: 'rgba(0,0,0,.05)' }, ticks:{ color: '#6b7280' } },
                y:{ position:'left', grid:{ color: 'rgba(0,0,0,.05)' }, ticks:{ color: '#6b7280' }, beginAtZero:true },
                y1:{ position:'right', grid:{ display:false }, ticks:{ color: '#6b7280' }, beginAtZero:true }
            }
        }
    });
})();
</script>
@endpush
