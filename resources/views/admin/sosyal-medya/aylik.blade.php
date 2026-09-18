@extends('admin._layout')

@section('title', 'Sosyal Medya Aylık Rapor')

@php
    $oncekiAy = $ay->copy()->subMonth();
    $sonrakiAy = $ay->copy()->addMonth();
    $ileriVar = $sonrakiAy->lte(\Illuminate\Support\Carbon::today()->startOfMonth());

    /* Gün şeridi için ayın tüm günleri — hesap satırlarında aynı ızgara
       kullanılıyor ki sütunlar hizalı kalsın. */
    $tumGunler = [];
    for ($t = $ay->copy(); $t->lte($ay->copy()->endOfMonth()); $t->addDay()) {
        $tumGunler[] = $t->copy();
    }

    $renk = [
        'tamam'     => ['#1E6B2F', '#E2EFDA', 'tamamlandı'],
        'eksik'     => ['#9C2B2B', '#F8D7DA', 'eksik'],
        'ertelendi' => ['#8A6200', '#FFF2CC', 'ertelendi'],
        'iptal'     => ['#5b6168', '#ECECEC', 'iptal'],
        'plansiz'   => ['#c8ccd0', '#F7F7F5', 'plan yok'],
    ];
@endphp

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.sosyal-medya-takip.index') }}">Sosyal Medya Takibi</a>
    <span class="sep">/</span>
    <span class="current">Aylık Rapor</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Aylık Rapor</h1>
        <div class="page-subtitle">
            {{ $ay->translatedFormat('F Y') }}
            @if($bitis->lt($ay->copy()->endOfMonth()))
                · {{ $bitis->format('d.m') }} tarihine kadar
            @endif
            · {{ count($satirlar) }} platform
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.sosyal-medya-takip.aylik', ['ay' => $oncekiAy->format('Y-m'), 'sorumlu' => $sorumluId]) }}"
           class="btn btn-secondary btn-sm"><i data-lucide="chevron-left"></i> <span>{{ $oncekiAy->translatedFormat('F') }}</span></a>

        @if($ileriVar)
            <a href="{{ route('admin.crm.sosyal-medya-takip.aylik', ['ay' => $sonrakiAy->format('Y-m'), 'sorumlu' => $sorumluId]) }}"
               class="btn btn-secondary btn-sm"><span>{{ $sonrakiAy->translatedFormat('F') }}</span> <i data-lucide="chevron-right"></i></a>
        @endif

        <a href="{{ route('admin.crm.sosyal-medya-takip.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="calendar-check"></i> <span>Günlük takip</span>
        </a>
    </div>
</div>

{{-- Uzman filtresi --}}
@if($uzmanlar->isNotEmpty())
<form method="GET" class="sm-ay-filtre">
    <input type="hidden" name="ay" value="{{ $ay->format('Y-m') }}">
    <label>Sorumlu uzman</label>
    <select name="sorumlu" class="form-select" onchange="this.form.submit()">
        <option value="">Tümü</option>
        @foreach($uzmanlar as $u)
            <option value="{{ $u->id }}" @selected($sorumluId == $u->id)>{{ $u->adi ?: $u->kullaniciadi }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn btn-secondary btn-sm">Uygula</button></noscript>
</form>
@endif

@if(empty($satirlar))
    <div class="card">
        <div class="card-body sm-bos">
            <i data-lucide="calendar-x"></i>
            <p>{{ $ay->translatedFormat('F Y') }} ayında kayıtlı paylaşım planı yok.</p>
            <p class="sm-bos-alt">Plan tanımlamak için <a href="{{ route('admin.crm.sosyal-medya-takip.plan') }}">Haftalık Plan</a> ekranını kullanın.</p>
        </div>
    </div>
@else

{{-- ── AY ÖZETİ ── --}}
<div class="sm-ozet">
    <div class="sm-ozet-kutu {{ $toplam['oran'] < 90 ? 'uyari' : 'tamam' }}">
        <span class="sm-ozet-sayi">%{{ $toplam['oran'] }}</span>
        <span class="sm-ozet-etiket">gerçekleşme</span>
    </div>
    <div class="sm-ozet-kutu">
        <span class="sm-ozet-sayi">{{ $toplam['yapilan'] }} / {{ $toplam['hedef'] }}</span>
        <span class="sm-ozet-etiket">paylaşım</span>
    </div>
    <div class="sm-ozet-kutu {{ $toplam['eksik_gun'] > 0 ? 'uyari' : '' }}">
        <span class="sm-ozet-sayi">{{ $toplam['eksik_gun'] }}</span>
        <span class="sm-ozet-etiket">eksik gün</span>
    </div>
    <div class="sm-ozet-kutu">
        <span class="sm-ozet-sayi">{{ $toplam['ertelenen'] }}</span>
        <span class="sm-ozet-etiket">ertelenen</span>
    </div>
    <div class="sm-ozet-kutu">
        <span class="sm-ozet-sayi">{{ $toplam['iptal'] }}</span>
        <span class="sm-ozet-etiket">iptal</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Platform bazında</h2>
        <span class="card-desc">En düşük gerçekleşme üstte</span>
    </div>

    <div class="table-scroll">
        <table class="data-table sm-ay-tablo">
            <thead>
                <tr>
                    <th>Marka / Platform</th>
                    <th>Sorumlu</th>
                    <th class="sm-ay-sayi">Paylaşım</th>
                    <th class="sm-ay-sayi">Oran</th>
                    <th class="sm-ay-sayi">Eksik</th>
                    <th class="sm-ay-serit">Gün gün</th>
                </tr>
            </thead>
            <tbody>
            @foreach($satirlar as $s)
                <tr>
                    <td>
                        <div class="sm-plan-marka-ad">{{ $s['marka'] }}</div>
                        <div class="sm-plan-platform">{{ $s['hesap']->platform }}</div>
                    </td>
                    <td class="sm-ay-sorumlu">{{ $s['sorumlu'] }}</td>
                    <td class="sm-ay-sayi">{{ $s['yapilan'] }} / {{ $s['hedef'] }}</td>
                    <td class="sm-ay-sayi">
                        <span class="sm-oran {{ $s['oran'] >= 100 ? 'iyi' : ($s['oran'] >= 80 ? 'orta' : 'kotu') }}">
                            %{{ $s['oran'] }}
                        </span>
                    </td>
                    <td class="sm-ay-sayi">
                        @if($s['eksik_gun'] > 0)
                            <strong class="sm-eksik-gun">{{ $s['eksik_gun'] }}</strong>
                        @else
                            <span class="sm-nokta">—</span>
                        @endif
                    </td>
                    <td class="sm-ay-serit">
                        <div class="sm-serit">
                            @foreach($tumGunler as $g)
                                @php
                                    $ymd  = $g->toDateString();
                                    $d    = $s['gunler'][$ymd] ?? 'plansiz';
                                    [$fg, $bg, $ad] = $renk[$d];
                                @endphp
                                <span class="sm-kare" style="background:{{ $bg }};border-color:{{ $fg }}22"
                                      title="{{ $g->format('d.m') }} — {{ $ad }}"></span>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer sm-ay-lejant">
        @foreach($renk as $anahtar => [$fg, $bg, $ad])
            <span class="sm-lejant-oge">
                <span class="sm-kare" style="background:{{ $bg }};border-color:{{ $fg }}22"></span> {{ $ad }}
            </span>
        @endforeach
    </div>
</div>

{{-- ── UZMAN ÖZETİ ──
     Kişi bazlı toplamı ayrı çıkarıyoruz: "kim ne kadar yetiştirdi"
     sorusu platform tablosundan okunmuyor. --}}
@php
    $uzmanOzet = [];
    foreach ($satirlar as $s) {
        $k = $s['sorumlu'];
        $uzmanOzet[$k] ??= ['hedef' => 0, 'yapilan' => 0, 'eksik_gun' => 0, 'platform' => 0];
        $uzmanOzet[$k]['hedef']     += $s['hedef'];
        $uzmanOzet[$k]['yapilan']   += $s['yapilan'];
        $uzmanOzet[$k]['eksik_gun'] += $s['eksik_gun'];
        $uzmanOzet[$k]['platform']++;
    }
    uasort($uzmanOzet, fn ($a, $b) => $b['yapilan'] <=> $a['yapilan']);
@endphp

@if(count($uzmanOzet) > 1)
<div class="card">
    <div class="card-header"><h2 class="card-title">Uzman bazında</h2></div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Uzman</th>
                    <th class="sm-ay-sayi">Platform</th>
                    <th class="sm-ay-sayi">Paylaşım</th>
                    <th class="sm-ay-sayi">Oran</th>
                    <th class="sm-ay-sayi">Eksik gün</th>
                </tr>
            </thead>
            <tbody>
            @foreach($uzmanOzet as $adi => $u)
                @php $oran = $u['hedef'] > 0 ? (int) round($u['yapilan'] / $u['hedef'] * 100) : 100; @endphp
                <tr>
                    <td>{{ $adi }}</td>
                    <td class="sm-ay-sayi">{{ $u['platform'] }}</td>
                    <td class="sm-ay-sayi">{{ $u['yapilan'] }} / {{ $u['hedef'] }}</td>
                    <td class="sm-ay-sayi">
                        <span class="sm-oran {{ $oran >= 100 ? 'iyi' : ($oran >= 80 ? 'orta' : 'kotu') }}">%{{ $oran }}</span>
                    </td>
                    <td class="sm-ay-sayi">{{ $u['eksik_gun'] ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

<style>
.sm-ay-filtre { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.sm-ay-filtre label { font-size:12px; color: var(--muted,#6b7280); }
.sm-ay-filtre select { max-width:220px; }
.sm-ay-tablo td, .sm-ay-tablo th { vertical-align: middle; }
.sm-ay-sayi { text-align:center; white-space:nowrap; }
.sm-ay-sorumlu { font-size:13px; color: var(--muted,#6b7280); }
.sm-plan-marka-ad { font-weight:600; }
.sm-plan-platform { font-size:12px; color: var(--muted,#6b7280); }
.sm-oran { font-weight:700; padding:2px 8px; border-radius:20px; font-size:12px; }
.sm-oran.iyi  { background:#E2EFDA; color:#1E6B2F; }
.sm-oran.orta { background:#FFF2CC; color:#8A6200; }
.sm-oran.kotu { background:#F8D7DA; color:#9C2B2B; }
.sm-eksik-gun { color:#9C2B2B; }
.sm-nokta { color: var(--muted,#9aa0a6); }
.sm-ay-serit { min-width:230px; }
.sm-serit { display:flex; gap:2px; flex-wrap:nowrap; }
.sm-kare { width:9px; height:16px; border-radius:2px; border:1px solid; display:inline-block; flex:0 0 auto; }
.sm-ay-lejant { display:flex; gap:16px; flex-wrap:wrap; font-size:12px; color:var(--muted,#6b7280); }
.sm-lejant-oge { display:inline-flex; align-items:center; gap:5px; }
.sm-ozet { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.sm-ozet-kutu { flex:1; min-width:130px; padding:14px; border-radius:12px;
                background: var(--card,#fff); border:1px solid var(--border,#e5e7eb); text-align:center; }
.sm-ozet-kutu.uyari { background:#F8D7DA; border-color:#f1b7bc; }
.sm-ozet-kutu.tamam { background:#E2EFDA; border-color:#c3ddb4; }
.sm-ozet-sayi { display:block; font-size:20px; font-weight:800; }
.sm-ozet-etiket { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.3px; color:var(--muted,#6b7280); }
.sm-bos { text-align:center; padding:40px 20px; }
.sm-bos-alt { font-size:13px; color:var(--muted,#6b7280); }

/* ── Panelde tanimli OLMAYAN sinıflar ──────────────────────────────
   .card / .card-header / .card-title temada VAR, bunlar YOK. Tema
   dosyasini degistirmek yerine burada, temanin kendi token'lariyla
   tanimlaniyor -- boylece acik/koyu modda da dogru calisiyor.        */
.card-body   { /* .card zaten 20px padding veriyor, ek bosluk gereksiz */ }
.card-desc   { font-size: 12px; color: var(--text-muted); }
.card-footer {
    margin-top: 16px; padding-top: 16px;
    border-top: 1px solid var(--border);
}
/* Tablo iceren kartlarda .card'in padding'i tabloyu iceri ittiriyordu */
.card > .table-scroll { margin: 0 -20px -20px; }
.card > .table-scroll:first-child { margin-top: -20px; }

/* Temadaki .card'in alt bosluğu yok (.section'da var). Alt alta dizilen
   kartlar birbirine yapisiyordu -- .section ile ayni 16px verildi. */
.card { margin-bottom: 16px; }
</style>

@endsection
