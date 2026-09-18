@extends('layouts.master')
@section('title', $siteTitle ?? 'Müşteri Paneli')
@section('content')
@php
    $uye = Auth::guard('uye')->user();
    $sonGirisFormatli = null;
    if ($uye && !empty($uye->son_giris)) {
        try {
            $sonGirisFormatli = (new \DateTime($uye->son_giris, new \DateTimeZone('Europe/Istanbul')))->format('d.m.Y H:i');
        } catch (\Throwable $e) {
            $sonGirisFormatli = date('d.m.Y H:i', strtotime($uye->son_giris));
        }
    }
    $arkaplanUrl = asset('tema/uploads/arkaplan/uyelik/uyelik.jpg');
    $bayiKayit = $uye ? \Illuminate\Support\Facades\DB::table('bayilikler')->where('id', $uye->bayi ?? 0)->first() : null;
@endphp
<!-- ***** TOP HEADER ***** -->
<div class="top-header overlay" style="background-image: url('{{ $arkaplanUrl }}')">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading">@yield('page_title', 'Müşteri Paneli')</h1>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ***** ÜST BANNER (HOŞGELDİN) ***** -->
<div class="ustbanner">
    <div class="container">
        @php
            $_uyeFoto = (!empty($uye->profil_foto) && is_file(public_path($uye->profil_foto))) ? asset($uye->profil_foto) : null;
            $_uyeHarf = strtoupper(mb_substr(trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: 'U', 0, 1, 'UTF-8'));
        @endphp
        <div class="ust-flex">
            <span class="ust-hg" style="display:inline-flex;align-items:center;gap:12px">
                <span style="width:42px;height:42px;border-radius:50%;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,.2);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;color:#fff">
                    @if($_uyeFoto)<img src="{{ $_uyeFoto }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $_uyeHarf }}@endif
                </span>
                <span>Hoşgeldin <strong>{{ $uye->ad ?? '' }} {{ $uye->soyad ?? '' }}</strong>
                    @if($bayiKayit)
                        <em>({{ $bayiKayit->paketadi ?? '' }})</em>
                    @endif
                    <i class="hide-mobile">Hesabınıza giriş yaptınız.</i>
                </span>
            </span>
            <span class="ustson">
                <span><i class="fas fa-clock"></i> Son Giriş: <strong>{{ $sonGirisFormatli ?? '-' }}</strong></span>
                <span class="hide-mobile"><i class="fas fa-globe"></i> IP: <strong>{{ $uye->ip ?? request()->ip() }}</strong></span>
            </span>
        </div>
    </div>
</div>
<!-- Mobil sidebar toggle butonu -->
<button type="button" class="panel-mobile-toggle" id="panelMobileToggle" aria-label="Menüyü aç/kapat">
    <i class="fas fa-bars"></i> <span>Panel Menüsü</span>
</button>
<!-- ***** PANEL CONTENT WRAPPER ***** -->
<div class="mixcontainer">
    <div class="container" id="muspanel">
        <div id="wrapper">
            {{-- ═══ PROFİL EKSİK UYARI BANDI (ödeme öncesi profil kontrolü) ═══ --}}
            {{-- Query param (request) öncelikli — flash session redirect zincirinde kaybolabiliyor --}}
            @php
                $profilEksikVar = request('profil_eksik') == '1' || session('profil_eksik');
                $eksikListe = request('eksik') ?: null;
                if ($eksikListe) {
                    $profilEksikMesaj = 'Ödeme yapabilmek için profilinizde şu bilgiler eksik: ' . $eksikListe . '. Lütfen aşağıdaki eksik alanları doldurun.';
                } else {
                    $profilEksikMesaj = session('error') ?? 'Ödeme yapabilmek için profil bilgilerinizi tamamlamanız gerekiyor. Lütfen aşağıdaki eksik alanları doldurun.';
                }
            @endphp
            @if($profilEksikVar)
                <div class="row mb-3 mt-3" style="margin-right:0;margin-left:0">
                    <div class="col-12">
                        <div style="background:linear-gradient(90deg,#ef4444,#dc2626);color:#fff;padding:16px 20px;border-radius:12px;font-size:15px;font-weight:600;box-shadow:0 6px 20px rgba(239,68,68,.3);display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                            <span style="font-size:24px">⚠️</span>
                            <span style="flex:1;min-width:200px">{{ $profilEksikMesaj }}</span>
                        </div>
                    </div>
                </div>
            @endif
            {{-- Bildirimler artık site geneli tek popup ile gösteriliyor: _partials/flash-toast --}}
            @yield('panel_top')
            <div class="row" id="panelRow">
                {{-- SOL SIDEBAR --}}
                <div class="col-md-3 sidebar" id="panelSidebarCol">
                    <div class="panel panel-sidebar">
                        <div class="list-group">
                            <a href="{{ route('hesabim') }}" class="list-group-item {{ request()->routeIs('hesabim') ? 'active' : '' }}">
                                <i class="fas fa-home"></i> Hesabım
                            </a>
                            <a href="{{ route('destek.taleplerim') }}" class="list-group-item {{ request()->routeIs('destek.*') ? 'active' : '' }}">
                                <i class="fas fa-life-ring"></i> Destek Sistemi
                            </a>
                            <a href="{{ route('mesajlarim') }}" class="list-group-item {{ request()->routeIs('mesajlarim') ? 'active' : '' }}">
                                <i class="fas fa-comments"></i> Mesajlarım
                            </a>
                            <a href="{{ route('bilgilerim') }}" class="list-group-item {{ request()->routeIs('bilgilerim*') ? 'active' : '' }}">
                                <i class="fas fa-user"></i> Bilgilerim
                            </a>
                            <a href="{{ route('faturalarim') }}" class="list-group-item {{ request()->routeIs('faturalarim') || request()->routeIs('fatura.*') ? 'active' : '' }}">
                                <i class="fas fa-file-invoice"></i> Faturalarım
                            </a>
                            @if(Route::has('sepet'))
                            <a href="{{ route('sepet') }}" class="list-group-item {{ request()->routeIs('sepet*') ? 'active' : '' }}">
                                <i class="fas fa-shopping-basket"></i> Sepetim
                            </a>
                            @endif
                            <a href="{{ route('alan.adlarim') }}" class="list-group-item {{ request()->routeIs('alan.adlarim') ? 'active' : '' }}">
                                <i class="fas fa-globe"></i> Alan Adlarım
                            </a>
                            <a href="{{ route('hostinglerim') }}" class="list-group-item {{ request()->routeIs('hostinglerim') || request()->routeIs('hosting.yonetim*') || request()->routeIs('hosting.yenileme*') ? 'active' : '' }}">
                                <i class="fas fa-server"></i> Hostinglerim
                            </a>
                            <a href="{{ route('web.paketlerim') }}" class="list-group-item {{ request()->routeIs('web.paketlerim') ? 'active' : '' }}">
                                <i class="fab fa-chrome"></i> Web Paketlerim
                            </a>
                            <a href="{{ route('tekliflerim') }}" class="list-group-item {{ request()->routeIs('tekliflerim') ? 'active' : '' }}">
                                <i class="fas fa-briefcase"></i> Tekliflerim
                            </a>
                            @if(Route::has('hizmetlerim'))
                            <a href="{{ route('hizmetlerim') }}" class="list-group-item {{ request()->routeIs('hizmetlerim') ? 'active' : '' }}">
                                <i class="fas fa-briefcase"></i> Hizmetlerim
                            </a>
                            @endif
                            @if(Route::has('randevularim'))
                            <a href="{{ route('randevularim') }}" class="list-group-item {{ request()->routeIs('randevularim') ? 'active' : '' }}">
                                <i class="fas fa-calendar-check"></i> Randevularım
                            </a>
                            @endif
                            @if(Route::has('sozlesmelerim'))
                            <a href="{{ route('sozlesmelerim') }}" class="list-group-item {{ request()->routeIs('sozlesmelerim') ? 'active' : '' }}">
                                <i class="fas fa-file-contract"></i> Sözleşmelerim
                            </a>
                            @endif
                            @if(Route::has('raporlarim'))
                            <a href="{{ route('raporlarim') }}" class="list-group-item {{ request()->routeIs('raporlarim') ? 'active' : '' }}">
                                <i class="fas fa-chart-line"></i> Raporlarım
                            </a>
                            @endif
                            @if(Route::has('efaturalarim'))
                            <a href="{{ route('efaturalarim') }}" class="list-group-item {{ request()->routeIs('efaturalarim') ? 'active' : '' }}">
                                <i class="fas fa-file-invoice"></i> E-Faturalarım
                            </a>
                            @endif
                            @if(Route::has('referanslarim'))
                            <a href="{{ route('referanslarim') }}" class="list-group-item {{ request()->routeIs('referanslarim') ? 'active' : '' }}">
                                <i class="fas fa-star"></i> Referanslarım
                            </a>
                            @endif
                            @if(Route::has('dosyalarim'))
                            <a href="{{ route('dosyalarim') }}" class="list-group-item {{ request()->routeIs('dosyalarim') ? 'active' : '' }}">
                                <i class="fas fa-folder-open"></i> Dosyalarım
                            </a>
                            @endif
                            <a href="{{ route('favorilerim') }}" class="list-group-item {{ request()->routeIs('favorilerim') ? 'active' : '' }}">
                                <i class="far fa-heart"></i> Favorilerim
                            </a>
                            @if(Route::has('bakiyem'))
                            <a href="{{ route('bakiyem') }}" class="list-group-item {{ request()->routeIs('bakiyem') ? 'active' : '' }}">
                                <i class="fas fa-credit-card"></i> Bakiyem
                            </a>
                            @endif
                            @if(Route::has('dnbank'))
                            <a href="{{ route('dnbank') }}" class="list-group-item {{ request()->routeIs('dnbank') || request()->routeIs('kredilerim') ? 'active' : '' }}">
                                <i class="fas fa-university"></i> DN Bank
                            </a>
                            @endif
                            @if(Route::has('bildirimlerim'))
                            <a href="{{ route('bildirimlerim') }}" class="list-group-item {{ request()->routeIs('bildirimlerim') ? 'active' : '' }}">
                                <i class="fas fa-bell"></i> Bildirimlerim
                            </a>
                            @endif
                            {{-- Bayi panele geçiş artık sağ üst "Hesabım" dropdown'undan yapılıyor --}}
                            @if($uye && ($uye->bayi ?? 0) == 0 && Route::has('bayi.basvuru'))
                            <a href="{{ route('bayi.basvuru') }}" class="list-group-item {{ request()->routeIs('bayi.basvuru*') ? 'active' : '' }}">
                                <i class="fas fa-handshake"></i> Bayi Başvurusu
                            </a>
                            @endif
                            <form method="POST" action="{{ route('cikis') }}" id="logoutForm" style="margin:0">
                                @csrf
                                <a href="#" onclick="document.getElementById('logoutForm').submit(); return false;" class="list-group-item">
                                    <i class="fas fa-power-off"></i> Oturumu Kapat
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
                {{-- SAĞ İÇERİK --}}
                <div class="col-md-9">
                    @yield('panel_content')
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Müşteri ↔ yönetici mesajlaşma (yüzen widget) --}}
@include('_partials.musteri-dm-widget')
@push('styles')
<style>
/* ============================================
   MÜŞTERİ PANELİ — MODERN TASARIM (v2)
   Renk dili admin paneliyle uyumlu: navy (#1a2332) + lemon (#b8b62e)
   ============================================ */
:root{
    --mp-primary:#b8b62e;
    --mp-primary-2:#a3a128;
    --mp-accent:#b8b62e;
    --mp-navy:#1a2332;
    --mp-navy-2:#28364d;
    --mp-ink:#1a2332;
    --mp-muted:#64748b;
    --mp-line:#ececec;
    --mp-bg:#f4f5f2;
    --mp-radius:16px;
    --mp-shadow:0 6px 24px rgba(26,35,50,.06);
    --mp-shadow-hover:0 14px 38px rgba(26,35,50,.14);
}
@media (max-width: 768px) {
    .main-content table.table,
    .panel-content table.table,
    table#datatable {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        white-space: nowrap;
    }
}
/* Panel arka planı yumuşak gri — kartlar öne çıksın */
.mixcontainer { padding-top:0; background:var(--mp-bg); }
/* ── ÜST HERO (sayfa başlığı) ── */
.top-header.overlay{ position:relative; }
.top-header.overlay::after{
    content:""; position:absolute; inset:0;
    background:linear-gradient(120deg, rgba(26,35,50,.94), rgba(40,54,77,.82));
}
.top-header .wrapper{ position:relative; z-index:2; }
.top-header .heading{ font-weight:800; letter-spacing:.2px; }

/* ── HOŞGELDİN BANDI ── */
.ustbanner { background:transparent; padding:20px 0 8px 0; }
.ustbanner .container > .ust-flex {
    background:#fff;
    border:1px solid var(--mp-line);
    border-radius:var(--mp-radius);
    padding:16px 22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    font-size:13.5px;
    color:#475569;
    line-height:1.4;
    box-shadow:var(--mp-shadow);
}
.ustbanner .ust-flex .ust-hg { flex:1 1 auto; min-width:0; font-size:15px; color:var(--mp-ink); }
.ustbanner .ust-flex .ust-hg > span > span:first-child{
    background:linear-gradient(135deg,var(--mp-navy),var(--mp-navy-2)) !important;
    box-shadow:0 4px 12px rgba(26,35,50,.35);
}
.ustbanner .ust-flex .ust-hg i { font-style:normal; opacity:.6; margin-left:6px; font-size:12.5px; }
.ustbanner .ust-flex .ustson { display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:12.5px; }
.ustbanner .ust-flex .ustson > span{ background:#f4f5fb; border:1px solid var(--mp-line); padding:6px 12px; border-radius:999px; }
.ustbanner .ust-flex .ustson i { margin-right:4px; opacity:.85; color:var(--mp-primary); }
.ustbanner em { font-style:normal; color:#b45309; font-weight:600; font-size:12px; padding:2px 8px; background:#fef3c7; border-radius:6px; margin-left:4px; }
.ustbanner strong { color:var(--mp-ink); font-weight:700; }

/* ── İSTATİSTİK KARTLARI (tile) ── */
#muspanel .tile{
    position:relative;
    padding:22px 22px 18px !important;
    background:#fff !important;
    border:1px solid var(--mp-line) !important;
    border-right:1px solid var(--mp-line) !important;
    border-radius:var(--mp-radius);
    box-shadow:var(--mp-shadow);
    overflow:hidden;
    margin-bottom:14px;
}
#muspanel .tile::before{
    content:""; position:absolute; left:0; top:0; bottom:0; width:4px;
    background:linear-gradient(180deg,var(--mp-primary),var(--mp-primary-2));
}
#muspanel .tile:hover{ transform:translateY(-4px); box-shadow:var(--mp-shadow-hover); }
#muspanel .tile .icon{
    position:absolute; top:16px; right:16px;
    width:46px; height:46px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px !important; color:#1a2332 !important;
    background:rgba(26,35,50,.10);
    box-shadow:none;
}
#muspanel .tile:hover > a .icon .fa{ font-size:22px; }
#muspanel .tile .stat{ margin-top:6px; padding-right:50px; font-size:23px; line-height:1.15; font-weight:800; color:var(--mp-ink) !important; white-space:nowrap; }
/* padding-right:50px -> başlık, sağ üstteki ikonun altına girmesin (dar kartta
   "Ödenmemiş Faturalar" gibi uzun başlıklar ikonla çakışıyordu). */
#muspanel .tile .title{ font-weight:600; color:var(--mp-muted); text-transform:uppercase; letter-spacing:.4px; font-size:11.5px; padding:6px 50px 0 0; }
#muspanel .tile .highlight{ display:none; }
/* Renk varyantları — sol şerit + ikon chip */
#muspanel .panelrenk1::before{ background:linear-gradient(180deg,#22c55e,#16a34a); }
#muspanel .panelrenk1 .icon{ background:rgba(34,197,94,.14); color:#16a34a !important; box-shadow:none; }
#muspanel .panelrenk2::before{ background:linear-gradient(180deg,#3b82f6,#2563eb); }
#muspanel .panelrenk2 .icon{ background:rgba(59,130,246,.14); color:#2563eb !important; box-shadow:none; }
#muspanel .panelrenk3::before{ background:linear-gradient(180deg,#ef4444,#dc2626); }
#muspanel .panelrenk3 .icon{ background:rgba(239,68,68,.14); color:#dc2626 !important; box-shadow:none; }
#muspanel .panelrenk4::before{ background:linear-gradient(180deg,#f59e0b,#d97706); }
#muspanel .panelrenk4 .icon{ background:rgba(245,158,11,.16); color:#d97706 !important; box-shadow:none; }
#muspanel .panelrenk5::before{ background:linear-gradient(180deg,#06b6d4,#0891b2); }
#muspanel .panelrenk5 .icon{ background:rgba(6,182,212,.14); color:#0891b2 !important; box-shadow:none; }
#muspanel .panelrenk1 .stat,#muspanel .panelrenk2 .stat,#muspanel .panelrenk3 .stat,
#muspanel .panelrenk4 .stat,#muspanel .panelrenk5 .stat{ color:var(--mp-ink) !important; }
/* Stat kartları arasına boşluk (dip dibe duruyordu) */
@media (min-width: 768px){
    #muspanel .tile.col-md + .tile.col-md{ margin-left:18px; }
}

/* ── İÇERİK KARTLARI ── */
#muspanel .main-content{
    border:1px solid var(--mp-line);
    border-radius:var(--mp-radius);
    padding:24px;
    box-shadow:var(--mp-shadow);
    margin-bottom:22px;
}
#muspanel .title-area{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding-bottom:14px; margin-bottom:16px; border-bottom:1px solid var(--mp-line);
}
#muspanel .title-area .title{ color:var(--mp-ink); font-weight:700; font-size:16px; }
#muspanel .title-area .title i{ color:var(--mp-primary); margin-right:6px; }

/* ── TABLOLAR ── */
#muspanel .table{ border-collapse:separate; border-spacing:0; }
#muspanel .table thead th{
    background:#f8fafc; color:var(--mp-muted); text-transform:uppercase;
    font-size:11.5px; letter-spacing:.4px; font-weight:700;
    border:0; border-bottom:1px solid var(--mp-line); padding:12px 14px;
}
#muspanel .table tbody td,#muspanel .table tbody th{
    border:0; border-bottom:1px solid var(--mp-line); padding:14px; vertical-align:middle; background:transparent;
}
#muspanel .table.table-bordered td,#muspanel .table.table-bordered th{ border:0; border-bottom:1px solid var(--mp-line); }
#muspanel .table-striped tbody tr:nth-of-type(odd){ background:transparent; }
#muspanel .table tbody tr{ transition:background .15s; }
#muspanel .table tbody tr:hover{ background:#f8f9ff; }
#muspanel .t-detail{ font-size:12.5px; color:var(--mp-muted); margin-bottom:0; }

/* ── DURUM ETİKETLERİ (pill) ── */
#muspanel .alert-sm{
    display:inline-block; width:auto !important; min-width:96px; margin:0 !important;
    padding:6px 14px !important; border-radius:999px; font-size:12px; font-weight:600;
    border:1px solid transparent; text-align:center;
}
#muspanel .alert-sm.alert-danger{ background:#fef2f2; color:#dc2626; border-color:#fee2e2; }
#muspanel .alert-sm.alert-success{ background:#ecfdf5; color:#059669; border-color:#d1fae5; }
#muspanel .alert-sm.alert-info{ background:#eff6ff; color:#2563eb; border-color:#dbeafe; }
#muspanel .alert-sm.alert-secondary{ background:#f1f5f9; color:#475569; border-color:#e2e8f0; }

/* ── BUTONLAR ── */
#muspanel .btn-outline-primary{
    border-radius:10px; border-color:var(--mp-primary); color:var(--mp-primary);
    font-weight:600; transition:all .2s;
}
#muspanel .btn-outline-primary:hover{
    background:linear-gradient(135deg,var(--mp-primary),var(--mp-primary-2));
    border-color:transparent; color:#1a1a0e; box-shadow:0 6px 16px rgba(184,182,46,.35);
}
#muspanel .btn-sm{ padding:6px 14px; }
.panel-mobile-toggle {
    display:none;
    width:calc(100% - 30px);
    margin:15px;
    padding:12px 16px;
    background:linear-gradient(135deg, var(--mp-navy), var(--mp-navy-2));
    color:#fff;
    border:none;
    border-radius:10px;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    box-shadow:0 4px 12px rgba(26,35,50,.25);
    align-items:center;
    justify-content:center;
    gap:8px;
}
.panel-mobile-toggle:hover { background:linear-gradient(135deg, #12192a, var(--mp-navy)); color:#fff; }
.panel-mobile-toggle i { font-size:18px; }
/* ── SOL MENÜ (modern, sticky, iç kaydırmalı) ── */
#muspanel .sidebar .panel.panel-sidebar{ padding:8px !important; border-left:0 !important; }
#muspanel .panel-sidebar {
    background:#fff;
    border:1px solid var(--mp-line);
    border-radius:var(--mp-radius);
    box-shadow:var(--mp-shadow);
    overflow:hidden;
    position:sticky;
    top:16px;
}
#muspanel .panel-sidebar .list-group{
    max-height:calc(100vh - 48px);
    overflow-y:auto;
    overscroll-behavior:contain;
    padding:4px;
}
/* İnce, şık scrollbar */
#muspanel .panel-sidebar .list-group::-webkit-scrollbar{ width:7px; }
#muspanel .panel-sidebar .list-group::-webkit-scrollbar-thumb{ background:#dfe3ee; border-radius:999px; }
#muspanel .panel-sidebar .list-group::-webkit-scrollbar-thumb:hover{ background:#c4cadb; }
#muspanel .panel-sidebar .list-group{ scrollbar-width:thin; scrollbar-color:#dfe3ee transparent; }
#muspanel .panel-sidebar .list-group-item {
    border:0 !important;
    border-radius:10px;
    margin:2px 0;
    padding:11px 14px;
    color:#475569;
    font-size:14px;
    font-weight:500;
    display:flex;
    align-items:center;
    gap:6px;
    transition:all .18s ease;
    background:#fff;
}
#muspanel .panel-sidebar .list-group-item i { width:22px; text-align:center; color:#94a3b8; font-size:15px; transition:color .18s; }
#muspanel .panel-sidebar .list-group-item:hover { background:#faf9ee; color:var(--mp-navy); padding-left:18px; }
#muspanel .panel-sidebar .list-group-item:hover i { color:var(--mp-primary); }
#muspanel .panel-sidebar .list-group-item.active {
    background:rgba(184,182,46,.15);
    color:var(--mp-navy);
    font-weight:700;
    position:relative;
}
#muspanel .panel-sidebar .list-group-item.active::before{
    content:""; position:absolute; left:0; top:7px; bottom:7px; width:3px;
    border-radius:2px; background:var(--mp-primary);
}
#muspanel .panel-sidebar .list-group-item.active i { color:var(--mp-primary); }
/* Çıkış butonunu ayır */
#muspanel .panel-sidebar #logoutForm{ margin-top:6px; border-top:1px solid var(--mp-line); padding-top:6px; }
#muspanel .panel-sidebar #logoutForm .list-group-item{ color:#dc2626; }
#muspanel .panel-sidebar #logoutForm .list-group-item i{ color:#ef4444; }
#muspanel .panel-sidebar #logoutForm .list-group-item:hover{ background:#fef2f2; color:#b91c1c; }
#muspanel .main-content { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); padding:20px; margin-bottom:20px; }
#muspanel .table { margin-bottom:0; }
#muspanel .main-content table { display:block; overflow-x:auto; -webkit-overflow-scrolling:touch; }
@media (min-width: 992px) { #muspanel .main-content table { display:table; } }
.tile { transition:transform .2s, box-shadow .2s; }
.tile:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.1); }

/* ══════════════════════════════════════════════════════════
   İÇERİK REFRESH v2 — buton / rozet / form / boş-durum / sayfalama
   Ortak sınıfları modernler; tüm panel sayfaları otomatik yenilenir.
   ══════════════════════════════════════════════════════════ */
#muspanel .btn{ border-radius:10px; font-weight:600; transition:all .18s; }
#muspanel .btn-primary{ background:linear-gradient(135deg,var(--mp-primary),var(--mp-primary-2)); border-color:transparent; color:#1a1a0e; }
#muspanel .btn-primary:hover{ box-shadow:0 6px 16px rgba(184,182,46,.35); color:#1a1a0e; }
#muspanel .btn-secondary{ background:#fff; border:1px solid var(--mp-line); color:#475569; }
#muspanel .btn-secondary:hover{ border-color:var(--mp-navy); color:var(--mp-navy); background:#f8f9ff; }
#muspanel .btn-success{ background:linear-gradient(135deg,#22c55e,#16a34a); border-color:transparent; color:#fff; }
#muspanel .btn-danger{ background:linear-gradient(135deg,#ef4444,#dc2626); border-color:transparent; color:#fff; }
#muspanel .btn-warning{ background:linear-gradient(135deg,#f59e0b,#d97706); border-color:transparent; color:#fff; }
#muspanel .btn-info{ background:linear-gradient(135deg,#3b82f6,#2563eb); border-color:transparent; color:#fff; }
#muspanel .btn-dark{ background:linear-gradient(135deg,var(--mp-navy),var(--mp-navy-2)); border-color:transparent; color:#fff; }
#muspanel .btn-outline-secondary{ border-radius:10px; border-color:var(--mp-line); color:#475569; font-weight:600; }
#muspanel .btn-outline-secondary:hover{ background:var(--mp-navy); border-color:var(--mp-navy); color:#fff; }

/* Rozetler (Bootstrap badge — hem bg-* hem badge-* varyantları) */
#muspanel .badge{ font-weight:700; padding:5px 11px; border-radius:999px; font-size:11.5px; letter-spacing:.2px; }
#muspanel .badge.bg-success,#muspanel .badge-success{ background:#ecfdf5 !important; color:#059669 !important; }
#muspanel .badge.bg-danger,#muspanel .badge-danger{ background:#fef2f2 !important; color:#dc2626 !important; }
#muspanel .badge.bg-warning,#muspanel .badge-warning{ background:#fffbeb !important; color:#b45309 !important; }
#muspanel .badge.bg-info,#muspanel .badge-info{ background:#eff6ff !important; color:#2563eb !important; }
#muspanel .badge.bg-secondary,#muspanel .badge-secondary{ background:#f1f5f9 !important; color:#475569 !important; }
#muspanel .badge.bg-primary,#muspanel .badge-primary{ background:rgba(184,182,46,.16) !important; color:#8a8718 !important; }

/* Form alanları */
#muspanel .form-control,#muspanel .form-select,#muspanel select.form-control,#muspanel textarea.form-control{
    border:1.5px solid var(--mp-line); border-radius:10px; padding:10px 13px; font-size:14px;
    color:var(--mp-ink); transition:border-color .15s, box-shadow .15s; box-shadow:none;
}
#muspanel .form-control:focus,#muspanel .form-select:focus{ border-color:var(--mp-primary); box-shadow:0 0 0 3px rgba(184,182,46,.12); outline:none; }
#muspanel .form-group > label,#muspanel form > label{ font-weight:600; color:#334155; font-size:13.5px; margin-bottom:6px; }

/* Boş durum (liste boşken) */
#muspanel .panel-bos,#muspanel .empty-state{ text-align:center; padding:48px 20px; color:#94a3b8; }
#muspanel .panel-bos i,#muspanel .empty-state i{ font-size:44px; opacity:.4; display:block; margin-bottom:12px; }

/* Sayfalama */
#muspanel .pagination{ gap:4px; flex-wrap:wrap; }
#muspanel .pagination .page-link{ border:1px solid var(--mp-line); border-radius:9px !important; color:#475569; font-weight:600; margin:0 2px; }
#muspanel .pagination .page-item.active .page-link{ background:var(--mp-navy); border-color:var(--mp-navy); color:#fff; }
#muspanel .pagination .page-link:hover{ background:#f8f9ff; color:var(--mp-navy); }

/* Legacy .panel kutuları -> modern kart */
#muspanel .panel:not(.panel-sidebar){ background:#fff; border:1px solid var(--mp-line); border-radius:var(--mp-radius); box-shadow:var(--mp-shadow); overflow:hidden; margin-bottom:20px; }
#muspanel .panel:not(.panel-sidebar) .panel-heading{ background:#f8fafc; border-bottom:1px solid var(--mp-line); padding:14px 18px; font-weight:700; color:var(--mp-ink); }
#muspanel .panel:not(.panel-sidebar) .panel-body{ padding:18px; }

/* Sekmeler (nav-tabs) — lemon alt çizgi */
#muspanel .nav-tabs{ border-bottom:2px solid var(--mp-line); gap:2px; }
#muspanel .nav-tabs .nav-link{ border:0; border-bottom:3px solid transparent; color:var(--mp-muted); font-weight:600; padding:11px 8px; border-radius:0; text-align:center; }
#muspanel .nav-tabs .nav-link:hover{ color:var(--mp-navy); border-bottom-color:#e5e3ad; }
#muspanel .nav-tabs .nav-link.active{ color:var(--mp-navy); background:transparent; border-bottom-color:var(--mp-primary); font-weight:700; }
/* Bölüm etiketi (badge bg-pink -> navy) */
#muspanel .badge.bg-pink,#muspanel .hesap_bilgi.bg-pink{ background:rgba(26,35,50,.06) !important; color:var(--mp-navy) !important; font-size:12.5px; font-weight:700; padding:7px 14px; border-radius:8px; border-left:3px solid var(--mp-primary); display:inline-block; }
@media (max-width: 991.98px) {
    .panel-sidebar,
    .user-sidebar,
    #muspanel .panel-sidebar { display:block !important; }
    .panel-mobile-toggle { display:flex; }
    /* NOT: sitenin ana CSS'inde ".sidebar{display:none!important}" var (<=768px).
       Kolonda .sidebar class'ı olduğu için !important olmadan ezilemiyordu —
       menü açılıyor ama görünmüyordu. Bu yüzden ikisi de !important. */
    #panelSidebarCol {
        display:none !important;
        width:100%;
        max-width:100%;
        flex:0 0 100%;
        margin-bottom:16px;
    }
    #panelSidebarCol.is-open { display:block !important; }
    #panelRow > .col-md-9 { width:100%; max-width:100%; flex:0 0 100%; }
    .ustbanner { padding:10px 0; }
    .ustbanner .ust-flex { flex-direction:column; align-items:flex-start; gap:6px; font-size:13px; }
    .ustbanner .ust-flex .ustson { font-size:12px; }
    .ustbanner .hide-mobile { display:none; }
    .top-header { padding:30px 0 !important; }
    .top-header .heading { font-size:24px !important; }
    #muspanel .nav-tabs .nav-item { margin-bottom:4px; }
    #muspanel .nav-tabs .nav-link { padding:10px 8px; font-size:13px; text-align:center; }
    #muspanel .form-control { font-size:14px; padding:10px 12px; }
    #muspanel .form-group { margin-bottom:14px; }
    #muspanel .tile { padding:12px !important; margin-bottom:10px !important; }
    #muspanel .tile .stat { font-size:18px !important; }
    #muspanel .tile .title { font-size:12px !important; }
    #muspanel .tile .icon { font-size:22px !important; }
    #muspanel { padding-left:10px; padding-right:10px; }
    #muspanel .main-content { padding:14px; }

    /* MOBİL TABLO: panel listelerindeki çok sütunlu tablolar (Faturalar, Hizmetler,
       Alan Adları, Hosting...) mobilde ekrana sığmıyordu; sağdaki sütunlar (Durum,
       Tutar...) kesilip görünmüyordu. Tabloyu yatay kaydırılabilir yapıyoruz →
       kullanıcı parmakla kaydırıp tüm sütunları görebiliyor. */
    #muspanel .main-content table,
    #muspanel table.table {
        display:block;
        width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
        border:1px solid #eef0f2;
        border-radius:10px;
    }
    /* Kısa-veri sütunları (tarih, tutar, durum, işlem) tek satırda kalsın. */
    #muspanel .main-content table tbody tr > :not(:first-child),
    #muspanel .main-content table thead th:not(:first-child) {
        white-space:nowrap !important;
    }
    /* İlk sütun (th veya td) uzun metni sarsın — nowrap yüzünden tek satıra
       zorlanıp tabloyu 2000px+ genişletiyordu. İç paragraf/linkleri de sardır. */
    #muspanel .main-content table tbody tr > :first-child,
    #muspanel .main-content table tbody tr > :first-child * {
        white-space:normal !important;
        word-break:break-word !important;
        overflow-wrap:anywhere !important;
    }
    #muspanel .main-content table tbody tr > :first-child {
        min-width:150px;
        max-width:60vw;
    }
}
@media (max-width: 575.98px) {
    .ustbanner .ust-flex { font-size:12px; }
    .ustbanner em { display:block; }
    #muspanel .nav-tabs .nav-item { flex:0 0 50% !important; max-width:50% !important; }
    .top-header .heading { font-size:20px !important; }
    #muspanel .tile { font-size:12px; }

    /* TELEFONDA STAT KARTLARI TAM GENİŞLİK (alt alta):
       2'şerli dizilimde kartlar 183px'e düşüyor, "Ödenmemiş Faturalar" gibi uzun
       başlık + sağ üstteki ikon sığmıyor, ikon/yazı kesik-binmiş görünüyordu.
       Tam genişlikte kart ~345px olur; ikon rahat sağ üstte, başlık tek satırda. */
    #muspanel .tile {
        flex:0 0 100% !important;
        max-width:100% !important;
        width:100% !important;
        padding:16px 18px !important;
    }
    #muspanel .tile .icon { top:14px; right:14px; }
    #muspanel .tile .title { white-space:normal; }
}
@media (max-width: 991.98px) {
    .mixcontainer { padding-bottom:90px; }
}
</style>
@endpush
@push('scripts')
<script>
(function(){
    var btn = document.getElementById('panelMobileToggle');
    var col = document.getElementById('panelSidebarCol');
    if(!btn || !col) return;

    // Menünün orijinal (masaüstü) yerini hatırla: panelRow'un başı.
    var orijinalParent = col.parentNode;
    var orijinalSonraki = col.nextElementSibling;
    // Mobilde taşınacağı yer: içerik sarmalayıcısının (#wrapper) EN ÜSTÜ.
    // Buton tam bu sarmalayıcının üstünde durduğu için menü "tuşun hemen altından"
    // açılmış olur. #muspanel içinde kaldığı için menü stilleri de korunur.
    var wrapper = document.getElementById('wrapper');

    function yerlestir(){
        var mobil = window.matchMedia('(max-width: 991.98px)').matches;
        if(mobil && wrapper){
            if(wrapper.firstElementChild !== col){ wrapper.insertBefore(col, wrapper.firstElementChild); }
        } else {
            // masaüstü: orijinal konumuna (sol sütun) geri koy
            if(col.parentNode !== orijinalParent || col.nextElementSibling !== orijinalSonraki){
                if(orijinalSonraki){ orijinalParent.insertBefore(col, orijinalSonraki); }
                else { orijinalParent.appendChild(col); }
            }
            col.classList.remove('is-open'); // masaüstünde her zaman görünür
        }
    }
    yerlestir();
    window.addEventListener('resize', yerlestir);

    btn.addEventListener('click', function(){
        col.classList.toggle('is-open');
        var icon = btn.querySelector('i');
        var label = btn.querySelector('span');
        if(col.classList.contains('is-open')){
            if(icon) icon.className = 'fas fa-times';
            if(label) label.textContent = 'Menüyü Kapat';
        } else {
            if(icon) icon.className = 'fas fa-bars';
            if(label) label.textContent = 'Panel Menüsü';
        }
    });
})();
</script>
@endpush
@endsection