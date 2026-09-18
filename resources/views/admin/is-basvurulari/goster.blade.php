@extends('admin._layout')

@section('title', 'İş Başvurusu — ' . $basvuru->ad_soyad)

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.is-basvurulari.index') }}">İş Başvuruları</a>
    <span class="sep">/</span>
    <span class="current">{{ $basvuru->ad_soyad }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="user"></i> {{ $basvuru->ad_soyad }}</h1>
        <div class="page-subtitle">{{ $basvuru->pozisyon ?: 'Pozisyon belirtilmemiş' }} · {{ $basvuru->created_at ? $basvuru->created_at->format('d.m.Y H:i') : '' }}</div>
    </div>
    <div class="page-actions">
        @if($basvuru->cv_dosya)
            <a href="{{ asset($basvuru->cv_dosya) }}" target="_blank" class="btn btn-primary"><i data-lucide="file-down"></i> <span>CV / Özgeçmiş İndir</span></a>
        @endif
        <a href="{{ route('admin.is-basvurulari.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> <span>Listeye Dön</span></a>
    </div>
</div>

@php
    $bloklar = [
        'İletişim' => [
            'Ad Soyad' => $basvuru->ad_soyad,
            'E-posta'  => $basvuru->email,
            'Telefon'  => $basvuru->telefon,
        ],
        'Başvuru' => [
            'Pozisyon'        => $basvuru->pozisyon,
            'Çalışma durumu'  => $basvuru->calisma_durumu,
            'Eğitim düzeyi'   => $basvuru->egitim_duzeyi,
            'Deneyim (yıl)'   => (string) $basvuru->deneyim_yili,
            'Lokasyon'        => $basvuru->lokasyon,
            'Dil yetkinliği'  => $basvuru->dil,
            'Beklenen maaş'   => $basvuru->maas_beklenti,
        ],
    ];
@endphp

@foreach($bloklar as $baslik => $alanlar)
<div class="section" style="margin-bottom:16px">
    <div class="section-title"><i data-lucide="chevron-right"></i> {{ $baslik }}</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;padding:6px 2px">
        @foreach($alanlar as $etiket => $deger)
        <div>
            <div style="font-size:11px;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">{{ $etiket }}</div>
            <div style="font-size:14px;color:var(--text)">{{ $deger !== null && $deger !== '' ? $deger : '—' }}</div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

<div class="section">
    <div class="section-title"><i data-lucide="message-square"></i> Ek Bilgi</div>
    <div style="font-size:14px;color:var(--text);line-height:1.7;white-space:pre-line;padding:6px 2px">{{ $basvuru->ek_bilgi ?: '—' }}</div>
</div>

@if($basvuru->ip)
<div style="margin-top:12px;font-size:12px;color:var(--text-muted)">IP: {{ $basvuru->ip }}</div>
@endif

@endsection
