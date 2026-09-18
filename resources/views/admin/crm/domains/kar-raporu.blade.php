@extends('admin._layout')

@section('title', 'Domain & Hosting Kâr Raporu')

@section('content')
@php $f = fn ($x) => number_format((float) $x, 2, ',', '.'); @endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.domains.index') }}">Domain &amp; Hosting Takip</a>
    <span class="sep">/</span>
    <span class="current">Kâr Raporu</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Domain &amp; Hosting Kâr Raporu</h1>
        <div class="page-subtitle">{{ $v['yil'] }} yılı · satış − alış = kâr</div>
    </div>
    <div class="page-actions">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <select name="yil" class="form-select" style="width:auto" onchange="this.form.submit()">
                @foreach($v['yillar'] as $y)
                    <option value="{{ $y }}" {{ (int) $v['yil'] === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('admin.crm.domains.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> <span>Takip Ekranı</span>
        </a>
    </div>
</div>

@if(empty($v['yillar']))
    <div class="alert alert-warning">
        <i data-lucide="info"></i>
        <div>Henüz domain kaydı bulunamadı.</div>
    </div>
@else

{{-- ═══ Ana rakamlar ═══ --}}
<div class="mini-stat-grid">
    <div class="mini-stat">
        <div class="lbl">Ciro</div>
        <div class="val">{{ $f($v['ciro']) }} ₺</div>
        <div class="sub">{{ $v['adet'] }} hizmet kaydı</div>
    </div>
    <div class="mini-stat warning">
        <div class="lbl">Maliyet (Alış)</div>
        <div class="val" style="color:var(--warning)">{{ $f($v['maliyet']) }} ₺</div>
        <div class="sub">Uzantı alış fiyatları</div>
    </div>
    <div class="mini-stat success">
        <div class="lbl">Kâr</div>
        <div class="val" style="color:var(--success)">{{ $f($v['kar']) }} ₺</div>
        <div class="sub">Ciro − Maliyet</div>
    </div>
    <div class="mini-stat info">
        <div class="lbl">Kâr Marjı</div>
        <div class="val" style="color:var(--info)">%{{ number_format($v['marj'], 1, ',', '.') }}</div>
        <div class="sub">Maliyeti bilinen kayıtlarda</div>
    </div>
</div>

{{-- ═══ Veri kalitesi uyarıları — rakam yanlış okunmasın ═══ --}}
@if($v['maliyetsiz_adet'] > 0 || $v['tahmini_adet'] > 0 || $v['tutarsiz_adet'] > 0)
<div class="section" style="margin-top:16px;border-left:4px solid var(--warning)">
    <div class="section-title"><i data-lucide="alert-triangle"></i> <span>Bu Rakamlar Okunurken</span></div>

    @if($v['maliyetsiz_adet'] > 0)
        <div style="margin-bottom:10px">
            <strong>{{ $v['maliyetsiz_adet'] }} kaydın alış fiyatı girilmemiş</strong>
            ({{ $f($v['maliyetsiz_ciro']) }} ₺ ciro) — bunlar <u>kâra dâhil edilmedi</u>,
            yoksa kâr olduğundan yüksek görünürdü.
            @if(!empty($v['eksik_uzantilar']))
                <div class="form-help" style="margin-top:4px">
                    Alış fiyatı bekleyen uzantılar:
                    <strong>{{ collect($v['eksik_uzantilar'])->map(fn($u) => '.' . $u)->implode(', ') }}</strong>
                    — <a href="{{ route('admin.domain.fiyatlar.index') }}">Domain Fiyatları</a> ekranından girilebilir.
                </div>
            @endif
        </div>
    @endif

    @if($v['tahmini_adet'] > 0)
        <div style="margin-bottom:10px">
            <strong>{{ $v['tahmini_adet'] }} kaydın satış tutarı sistemde 0</strong>
            — bunlar için uzantının <u>liste (yenileme) fiyatı</u> tahmini satış olarak alındı,
            toplam {{ $f($v['tahmini_ciro']) }} ₺. Gerçek tutar girilirse rakam netleşir.
        </div>
    @endif

    @if($v['tutarsiz_adet'] > 0)
        <div>
            <strong>{{ $v['tutarsiz_adet'] }} kayıt hesaba katılamadı</strong>
            — ne satış tutarı ne de uzantı liste fiyatı var.
        </div>
    @endif

    {{-- Tahmine düşen rakamları gerçeğe çevirmenin kısa yolu --}}
    @if(($v['tahmini_adet'] > 0 || $v['tutarsiz_adet'] > 0) && Route::has('admin.crm.domains.toplu-tutar'))
        <div style="margin-top:10px">
            <a href="{{ route('admin.crm.domains.toplu-tutar') }}" class="btn btn-primary btn-sm">
                <i data-lucide="pencil-line"></i> <span>Eksik tutarları toplu gir</span>
            </a>
        </div>
    @endif
</div>
@endif

{{-- ═══ Aylık döküm ═══ --}}
<div class="table-wrap" style="margin-top:16px;margin-bottom:16px">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0">
        <div class="card-title">Aylık Döküm — {{ $v['yil'] }}</div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ay</th>
                    <th style="width:90px" class="text-right">Adet</th>
                    <th style="width:150px" class="text-right">Ciro</th>
                    <th style="width:150px" class="text-right">Maliyet</th>
                    <th style="width:150px" class="text-right">Kâr</th>
                </tr>
            </thead>
            <tbody>
                @foreach($v['aylar'] as $ay)
                <tr @if($ay['adet'] === 0) style="opacity:.45" @endif>
                    <td><strong>{{ $ay['etiket'] }}</strong></td>
                    <td class="text-right">{{ $ay['adet'] }}</td>
                    <td class="text-right">{{ $f($ay['ciro']) }} ₺</td>
                    <td class="text-right" style="color:var(--warning)">{{ $f($ay['maliyet']) }} ₺</td>
                    <td class="text-right"><strong style="color:var(--success)">{{ $f($ay['kar']) }} ₺</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ Uzantı kırılımı ═══ --}}
<div class="table-wrap">
    <div class="card-header" style="padding:16px 18px;margin-bottom:0">
        <div class="card-title">Uzantıya Göre</div>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Uzantı</th>
                    <th style="width:90px" class="text-right">Adet</th>
                    <th style="width:150px" class="text-right">Ciro</th>
                    <th style="width:150px" class="text-right">Maliyet</th>
                    <th style="width:150px" class="text-right">Kâr</th>
                    <th style="width:140px">Durum</th>
                </tr>
            </thead>
            <tbody>
                @forelse($v['uzantilar'] as $u)
                <tr>
                    <td><strong>{{ $u['uzanti'] === 'bilinmiyor' ? 'Uzantı tanınmadı' : '.' . $u['uzanti'] }}</strong></td>
                    <td class="text-right">{{ $u['adet'] }}</td>
                    <td class="text-right">{{ $f($u['ciro']) }} ₺</td>
                    <td class="text-right" style="color:var(--warning)">{{ $f($u['maliyet']) }} ₺</td>
                    <td class="text-right"><strong style="color:var(--success)">{{ $f($u['kar']) }} ₺</strong></td>
                    <td>
                        @if($u['maliyetsiz'] > 0)
                            <span class="badge badge-warning">{{ $u['maliyetsiz'] }} maliyetsiz</span>
                        @else
                            <span class="badge badge-success">tam</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-state" style="padding:28px 0"><p>{{ $v['yil'] }} yılında domain kaydı yok.</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="form-help" style="margin-top:14px">
    Kâr = satış − alış. Alış fiyatı önce kaydın kendi maliyetinden, yoksa
    <a href="{{ route('admin.domain.fiyatlar.index') }}">Domain Fiyatları</a> ekranındaki
    uzantı alış fiyatından alınır. Tarih olarak hizmet kaydının oluşturulma tarihi kullanılır.
</div>

@endif
@endsection
