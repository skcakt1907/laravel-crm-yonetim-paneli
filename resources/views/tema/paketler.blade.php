@extends('layouts.master')

@section('title', __('messages.our_web_packages'))

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/paketler.css') }}">
<link rel="stylesheet" href="{{ asset('tema/css/paketler-fix.css') }}">
<link rel="stylesheet" href="{{ asset('tema/css/paket-karti.css') }}">
<style>
    /* Div ve container'lar için özel */
    .section div[style*="background"],
    .section div[style*="background-color"],
    .paketler-page div[style*="background"],
    .paketler-page div[style*="background-color"] {
        background: #f3f4f6 !important;
        background-color: #f3f4f6 !important;
    }

    /* Tablolar için */
    .section table,
    .section td,
    .section th,
    .paketler-page table,
    .paketler-page td,
    .paketler-page th {
        background-color: transparent !important;
        background: transparent !important;
        color: #1f2937 !important;
    }

    /* Paragraflar ve text elementleri */
    .section p,
    .section span,
    .section div,
    .section li,
    .paketler-page p,
    .paketler-page span,
    .paketler-page div,
    .paketler-page li {
        color: #475569 !important;
    }

    /* Linkler */
    .section a,
    .paketler-page a {
        color: #b8b62e !important;
    }

    .section a:hover,
    .paketler-page a:hover {
        color: #4338ca !important;
    }

    /* Arama input placeholder - ID spesifitesi ile MAKSİMUM koruma */
    #paket-arama-kelime {
        color: #1f2937 !important;
        -webkit-text-fill-color: #1f2937 !important;
        background: #f9fafb !important;
    }
    #paket-arama-kelime::placeholder,
    #paket-arama-kelime::-webkit-input-placeholder {
        color: #64748b !important;
        -webkit-text-fill-color: #64748b !important;
        opacity: 1 !important;
    }
    #paket-arama-kelime::-moz-placeholder {
        color: #64748b !important;
        opacity: 1 !important;
    }
    #paket-arama-kelime:-ms-input-placeholder {
        color: #64748b !important;
        opacity: 1 !important;
    }
    /* Chrome autofill sarı highlight'ı ez */
    #paket-arama-kelime:-webkit-autofill,
    #paket-arama-kelime:-webkit-autofill:hover,
    #paket-arama-kelime:-webkit-autofill:focus,
    #paket-arama-kelime:-webkit-autofill:active {
        -webkit-text-fill-color: #1f2937 !important;
        -webkit-box-shadow: 0 0 0px 1000px #f9fafb inset !important;
        box-shadow: 0 0 0px 1000px #f9fafb inset !important;
        transition: background-color 5000s ease-in-out 0s;
    }
    /* MOBİL FİX: sitenin global "input,select{padding:12px 15px!important}" kuralı
       (<=768px) ikon/ok boşluğunu eziyordu → arama yazısı büyütece, select yazısı
       oka biniyordu. Bu blok filtre kontrollerine doğru boşluğu geri verir. */
    @media (max-width: 768px) {
        .paket-filtre-form { flex-wrap: wrap; }
        .paket-filtre-form > select,
        .paket-filtre-form > div { flex: 1 1 100% !important; width: 100% !important; max-width: 100% !important; }
        .paket-filtre-form select { padding-left: 14px !important; padding-right: 34px !important; }
        #paket-arama-kelime { padding-left: 40px !important; padding-right: 14px !important; }
        .paket-filtre-form button[type="submit"] { flex: 1 1 100% !important; justify-content: center; }
    }
    /* Pagination - modern minimal SaaS tarzı */
    .paketler-pagination {
        display:flex; justify-content:center; align-items:center; gap:4px;
        flex-wrap:wrap; margin: 50px 0 20px;
        padding: 8px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
        width: fit-content; margin-left:auto; margin-right:auto;
        border: 1px solid #f1f5f9;
    }
    .paketler-pagination .pp-item {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:36px; height:36px; padding:0 10px;
        border-radius:9px;
        background:transparent; color:#64748b;
        border:none;
        text-decoration:none; font-size:13.5px; font-weight:600;
        transition: all .18s ease; cursor:pointer;
    }
    .paketler-pagination a.pp-item:hover {
        background: #f1f5f9; color:#0f172a;
    }
    .paketler-pagination .pp-active {
        background:#b8b62e !important; color:#1a1a0e !important;
        cursor:default;
        box-shadow: 0 2px 6px rgba(184,182,46,.30);
    }
    .paketler-pagination .pp-disabled {
        opacity:.35; cursor:not-allowed; color:#cbd5e1;
    }
    .paketler-pagination .pp-dots {
        display:inline-flex; align-items:center; justify-content:center;
        min-width:28px; height:36px; color:#cbd5e1; font-weight:600;
    }
    .paketler-pagination .pp-item i { font-size:18px; line-height:1; }
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    $ayarlar = DB::table('ayarlar')->first();
    $arkaplan = DB::table('arka_plan')->where('id', 1)->first();
    
    // Header arkaplan stilini belirle
    $headerBgStyle = '';
    $paketlerArkaplanTuru = $ayarlar->paketler_arkaplan_turu ?? 'resim';
    
    if ($paketlerArkaplanTuru == 'resim') {
        $paketler_resim = $arkaplan->paketler ?? 'bg.jpg';
        $headerBgStyle = "background-image: url(" . asset('tema/uploads/arkaplan/paketler/' . $paketler_resim) . ");";
    } elseif ($paketlerArkaplanTuru == 'renk') {
        $renk = $ayarlar->paketler_arkaplan_renk ?? '#141e30';
        $headerBgStyle = "background-color: {$renk};";
    } elseif ($paketlerArkaplanTuru == 'gradient') {
        $gradientBaslangic = $ayarlar->paketler_gradient_baslangic ?? '#141e30';
        $gradientBitis = $ayarlar->paketler_gradient_bitis ?? '#243b55';
        $headerBgStyle = "background: linear-gradient(135deg, {$gradientBaslangic} 0%, {$gradientBitis} 100%);";
    } else {
        $headerBgStyle = "background-image: url(" . asset('tema/uploads/arkaplan/paketler/bg.jpg') . ");";
    }
    
    // İçerik arkaplan stilini belirle
    $icerikBgStyle = '';
    $paketlerIcerikArkaplanTuru = $ayarlar->paketler_icerik_arkaplan_turu ?? 'gradient';
    
    if ($paketlerIcerikArkaplanTuru == 'renk') {
        $icerikRenk = $ayarlar->paketler_icerik_arkaplan_renk ?? '#141e30';
        $icerikBgStyle = "background: {$icerikRenk};";
    } else {
        $icerikGradientBaslangic = $ayarlar->paketler_icerik_gradient_baslangic ?? '#141e30';
        $icerikGradientBitis = $ayarlar->paketler_icerik_gradient_bitis ?? '#243b55';
        $icerikBgStyle = "background: linear-gradient(135deg, {$icerikGradientBaslangic} 0%, {$icerikGradientBitis} 100%);";
    }
@endphp
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
    <div class="container">
        <div style="font-size:13px; color:#999; margin-bottom:10px;">
            <a href="{{ localized_route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') }}</a>
            <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
            <span style="color:#1a1a1a; font-weight:600;">{{ __('messages.packages') }}</span>
        </div>
        <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ __('messages.packages') }}</h1>
        <p style="color:#999; font-size:14px; margin:6px 0 0;">{{ __('messages.packages_description') }}</p>
    </div>
</div>

<div style="padding: 60px 0; background: #f0f0ec; min-height: 100vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div class="row" style="margin: 0 -15px;">
            <div class="col-12 col-lg-3 mb-4 mb-lg-0" style="padding: 0 15px;">
                <aside class="sidebar">
                    <div class="modern-card" style="padding: 25px;">
                        <h3 style="color: #0f172a; font-size: 20px; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e5e7eb;">
                            <i class="mdi mdi-filter" style="color: #b8b62e; margin-right: 10px;"></i>{{ __('messages.categories') }}
                        </h3>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 8px;">
                                <a href="{{ localized_route('paketler') }}" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: {{ !$kategori ? 'rgba(184, 182, 46, 0.10)' : '#f3f4f6' }}; border-radius: 10px; color: {{ !$kategori ? '#b8b62e' : '#1f2937' }}; text-decoration: none; transition: all 0.3s ease; border-left: 3px solid {{ !$kategori ? '#b8b62e' : 'transparent' }};">
                                    <span>{{ __('messages.all_packages') }}</span>
                                    <span class="modern-badge" style="background: {{ !$kategori ? 'rgba(184, 182, 46, 0.18)' : '#e5e7eb' }}; color: {{ !$kategori ? '#b8b62e' : '#475569' }}; padding: 4px 10px; font-size: 11px;">{{ $kategori_sayilari['toplam'] ?? 0 }}</span>
                                </a>
                            </li>
                            @foreach($kategoriler as $kat)
                            @if(($kategori_sayilari[$kat->id] ?? 0) > 0)
                            <li style="margin-bottom: 8px;">
                                <a href="{{ localized_route('paketler.kategori', $kat->id) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: {{ $kategori == $kat->id ? 'rgba(184, 182, 46, 0.10)' : '#f3f4f6' }}; border-radius: 10px; color: {{ $kategori == $kat->id ? '#b8b62e' : '#1f2937' }}; text-decoration: none; transition: all 0.3s ease; border-left: 3px solid {{ $kategori == $kat->id ? '#b8b62e' : 'transparent' }};">
                                    <span>{{ \App\Helpers\TranslationHelper::translate($kat->adi) }}</span>
                                    <span class="modern-badge" style="background: {{ $kategori == $kat->id ? 'rgba(184, 182, 46, 0.18)' : '#e5e7eb' }}; color: {{ $kategori == $kat->id ? '#b8b62e' : '#475569' }}; padding: 4px 10px; font-size: 11px;">{{ $kategori_sayilari[$kat->id] ?? 0 }}</span>
                                </a>
                            </li>
                            @endif
                            @endforeach
                        </ul>
                    </div>
                </aside>
            </div>

            <div class="col-12 col-lg-9" style="padding: 0 15px;">
                @if($kategori)
                @php
                    $aktif_kategori = optional($kategoriler->firstWhere('id', $kategori))->adi ?? $kategori;
                @endphp
                <div style="background: rgba(184, 182, 46, 0.10); border: 1px solid rgba(184, 182, 46, 0.25); border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; color: #1f2937;">
                    <i class="mdi mdi-filter"></i> {{ __('messages.category_filter_active') }}:
                    <strong>{{ \App\Helpers\TranslationHelper::translate($aktif_kategori) }}</strong>
                    <a href="{{ localized_route('paketler') }}" style="float: right; padding: 8px 20px; font-size: 13px; background: #b8b62e; color: #1a1a0e; border-radius: 8px; text-decoration: none;">{{ __('messages.clear_filter') }}</a>
                </div>
                @endif
                
                {{-- FİLTRE BLOĞU (madde 16 — ince tek satır, dropdown'lı) --}}
                <div style="background:#fff;border-radius:12px;padding:8px;margin-bottom:24px;border:1px solid #eef0f2;box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                    <form action="{{ $kategori ? localized_route('paketler.kategori', $kategori) : localized_route('paketler') }}" method="GET" class="paket-filtre-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <select onchange="(function(v){var u=new URL(v);var k=document.getElementById('paket-arama-kelime');if(k&&k.value.trim())u.searchParams.set('kelime',k.value.trim());var s=document.querySelector('select[name=siralama]');if(s&&s.value)u.searchParams.set('siralama',s.value);window.location.href=u.toString();})(this.value)" style="flex:0 0 210px;width:210px;max-width:210px;height:44px;padding:0 34px 0 14px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;font-weight:500;outline:none;cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%2710%27 fill=%27%239ca3af%27 viewBox=%270 0 16 16%27%3E%3Cpath d=%27M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 12px center;">
                            <option value="{{ localized_route('paketler') }}">{{ __('messages.all_packages') }}</option>
                            @foreach($kategoriler as $kat)
                                @if(($kategori_sayilari[$kat->id] ?? 0) > 0)
                                <option value="{{ localized_route('paketler.kategori', $kat->id) }}" {{ $kategori == $kat->id ? 'selected' : '' }}>{{ \App\Helpers\TranslationHelper::translate($kat->adi) }} ({{ $kategori_sayilari[$kat->id] }})</option>
                                @endif
                            @endforeach
                        </select>
                        <div style="flex:1 1 220px;position:relative;display:flex;align-items:center;">
                            <i class="mdi mdi-magnify" style="position:absolute;left:13px;color:#9ca3af;font-size:18px;pointer-events:none;"></i>
                            <input type="text" name="kelime" id="paket-arama-kelime" value="{{ request('kelime') }}" placeholder="{{ __('messages.search_product') }}" class="paket-arama-input" autocomplete="off" style="width:100%;height:44px;padding:0 14px 0 40px;background:#f9fafb;color:#374151 !important;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;font-weight:500;outline:none;">
                        </div>
                        <select name="siralama" style="flex:0 0 175px;width:175px;max-width:175px;height:44px;padding:0 34px 0 14px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:10px;font-size:13.5px;font-weight:500;outline:none;cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%2710%27 fill=%27%239ca3af%27 viewBox=%270 0 16 16%27%3E%3Cpath d=%27M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 12px center;">
                            <option value="yeni" {{ request('siralama') == 'yeni' || !request('siralama') ? 'selected' : '' }}>{{ __('messages.sort_newest') }}</option>
                            <option value="eski" {{ request('siralama') == 'eski' ? 'selected' : '' }}>{{ __('messages.sort_oldest') }}</option>
                        </select>
                        <button type="submit" style="flex:0 0 auto;height:44px;padding:0 22px;background:#b8b62e;color:#1a1a0e;border:none;border-radius:10px;font-size:13.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;"><i class="mdi mdi-magnify"></i> ARA</button>
                    </form>
                </div>

                {{-- Tema alt-kategorileri (web site kategorisi içinde) --}}
                @if(!empty($temaKategorileri))
                <div style="margin:-8px 0 22px;">
                    <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">🎨 Tema Kategorileri</div>
                    @php $_katUrl = localized_route('paketler.kategori', $kategori); $_sep = \Illuminate\Support\Str::contains($_katUrl, '?') ? '&' : '?'; @endphp
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <a href="{{ $_katUrl }}" style="padding:6px 14px;border-radius:999px;font-size:12.5px;font-weight:600;text-decoration:none;border:1.5px solid {{ !request('kelime') ? '#b8b62e' : '#e5e7eb' }};background:{{ !request('kelime') ? '#b8b62e' : '#fff' }};color:{{ !request('kelime') ? '#1a1a0e' : '#64748b' }};">Tümü</a>
                        @foreach($temaKategorileri as $tk)
                        <a href="{{ $_katUrl }}{{ $_sep }}kelime={{ urlencode($tk['kelime']) }}" style="padding:6px 14px;border-radius:999px;font-size:12.5px;font-weight:600;text-decoration:none;border:1.5px solid {{ request('kelime') === $tk['kelime'] ? '#b8b62e' : '#e5e7eb' }};background:{{ request('kelime') === $tk['kelime'] ? '#b8b62e' : '#fff' }};color:{{ request('kelime') === $tk['kelime'] ? '#1a1a0e' : '#64748b' }};">{{ $tk['label'] }} <span style="opacity:.6;font-weight:500;">({{ $tk['adet'] }})</span></a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(isset($yazilimlar) && $yazilimlar && $yazilimlar->count() > 0)
                <div class="row" id="paketler-list" style="margin: 0 -6px; margin-top: 30px;">
                    @foreach($yazilimlar as $index => $yazilim)
                    <div class="col-md-4 col-sm-6 paketler-item" style="padding: 0 6px; margin-bottom: 24px;">
                        @include('tema.partials.paket-karti', ['yazilim' => $yazilim])
                    </div>
                    @endforeach
                </div>

                {{-- Pagination - manuel render (default Tailwind/Bootstrap stilini ezmek için) --}}
                @if(method_exists($yazilimlar, 'hasPages') && $yazilimlar->hasPages())
                @php
                  $current = $yazilimlar->currentPage();
                  $last = $yazilimlar->lastPage();
                  $start = max(1, $current - 2);
                  $end = min($last, $current + 2);
                @endphp
                <div class="paketler-pagination">
                    {{-- Önceki --}}
                    @if($yazilimlar->onFirstPage())
                        <span class="pp-item pp-disabled" aria-disabled="true"><i class="mdi mdi-chevron-left"></i></span>
                    @else
                        <a class="pp-item" href="{{ $yazilimlar->previousPageUrl() }}"><i class="mdi mdi-chevron-left"></i></a>
                    @endif

                    {{-- İlk sayfa + dots --}}
                    @if($start > 1)
                        <a class="pp-item" href="{{ $yazilimlar->url(1) }}">1</a>
                        @if($start > 2)<span class="pp-dots">…</span>@endif
                    @endif

                    {{-- Orta sayfalar --}}
                    @for($i = $start; $i <= $end; $i++)
                        @if($i == $current)
                            <span class="pp-item pp-active">{{ $i }}</span>
                        @else
                            <a class="pp-item" href="{{ $yazilimlar->url($i) }}">{{ $i }}</a>
                        @endif
                    @endfor

                    {{-- Dots + son sayfa --}}
                    @if($end < $last)
                        @if($end < $last - 1)<span class="pp-dots">…</span>@endif
                        <a class="pp-item" href="{{ $yazilimlar->url($last) }}">{{ $last }}</a>
                    @endif

                    {{-- Sonraki --}}
                    @if($yazilimlar->hasMorePages())
                        <a class="pp-item" href="{{ $yazilimlar->nextPageUrl() }}"><i class="mdi mdi-chevron-right"></i></a>
                    @else
                        <span class="pp-item pp-disabled" aria-disabled="true"><i class="mdi mdi-chevron-right"></i></span>
                    @endif
                </div>
                @endif
                @else
                <div style="background: #f3f4f6; border-radius: 16px; padding: 60px 40px; text-align: center; border: 1px solid #e5e7eb;">
                    <div style="margin: 0 auto 30px; width: 80px; height: 80px; background: rgba(184, 182, 46, 0.10); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-package-variant" style="font-size: 40px; color: #b8b62e;"></i>
                    </div>
                    <h4 style="color: #0f172a; font-size: 24px; font-weight: 700; margin-bottom: 15px;">{{ __('messages.no_packages_found') }}</h4>
                    <p style="color: #475569; font-size: 16px;">{{ __('messages.info') ?? 'Bilgi' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection