@extends('admin._layout')

@section('title', 'Ar-Ge Anketi — ' . $anket->ad_soyad)

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.arge-anketleri.index') }}">Ar-Ge Anketi</a>
    <span class="sep">/</span>
    <span class="current">{{ $anket->ad_soyad }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="clipboard-list"></i> {{ $anket->ad_soyad }}</h1>
        <div class="page-subtitle">
            #{{ $anket->id }} · {{ $anket->created_at ? $anket->created_at->format('d.m.Y H:i') : '' }}
            @if($anket->ip)· IP: {{ $anket->ip }}@endif
        </div>
    </div>
    <a href="{{ route('admin.arge-anketleri.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> <span>Listeye Dön</span></a>
</div>

@php
    $eh = fn($v) => $v === 'evet' ? '✓ Evet' : ($v === 'hayir' ? '✕ Hayır' : '—');
    // Bölümler: [başlık, [ [soru, cevap, uzunMu] ... ]]
    $bolumler = [
        ['İletişim', [
            ['Ad Soyad', $anket->ad_soyad, false],
            ['E-Posta', $anket->email, false],
            ['Telefon', $anket->telefon ?: '—', false],
            ['Doğum Tarihi', $anket->dogum_tarihi ? $anket->dogum_tarihi->format('d.m.Y') : '—', false],
        ]],
        ['Marka', [
            ['Markanızın güçlü yönleri', $anket->marka_guclu, true],
            ['Geliştirmek istediğiniz yönler', $anket->marka_gelistir, true],
            ['Geri bildirimleri önemsiyor mu?', $eh($anket->geri_bildirim), false],
            ['Rakip takibi ve düşünceler', $anket->rakipler, true],
            ['Beğendiği rakip kampanyası', $anket->rakip_kampanya, true],
        ]],
        ['Ajans Deneyimi', [
            ['Daha önce ajansla çalıştı mı?', $eh($anket->ajans_calisti), false],
            ['Ajansın katkısı / eksikleri', $anket->ajans_katki, true],
            ['Bir ajanstan beklentisi', $anket->ajans_beklenti, true],
            ['İş Ortağım\'ı duydu mu?', $eh($anket->duydu_mu), false],
            ['Ayırabileceği dijital bütçe', $anket->butce, true],
        ]],
    ];
@endphp

@foreach($bolumler as [$baslik, $satirlar])
<div class="section" style="margin-bottom:16px">
    <div class="section-title"><i data-lucide="chevron-right"></i> <span>{{ $baslik }}</span></div>
    <div style="display:flex;flex-direction:column;gap:14px;padding-top:6px">
        @foreach($satirlar as [$soru, $cevap, $uzun])
            <div>
                <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px">{{ $soru }}</div>
                @if($uzun)
                    <div style="font-size:14px;color:var(--text);line-height:1.65;background:var(--bg-subtle);border:1px solid var(--border);border-radius:10px;padding:12px 14px;white-space:pre-line">{{ $cevap ?: '—' }}</div>
                @else
                    <div style="font-size:15px;color:var(--text);font-weight:600">{{ $cevap }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endforeach

<div style="display:flex;gap:8px;margin-top:8px">
    <a href="mailto:{{ $anket->email }}" class="btn btn-primary btn-sm"><i data-lucide="mail"></i> <span>E-Posta Gönder</span></a>
    @if($anket->telefon)
    <a href="tel:{{ preg_replace('/\D/', '', $anket->telefon) }}" class="btn btn-secondary btn-sm"><i data-lucide="phone"></i> <span>Ara</span></a>
    @endif
    <form action="{{ route('admin.arge-anketleri.sil', $anket->id) }}" method="POST" style="margin-left:auto" onsubmit="return confirm('Silinsin mi? Geri alınamaz.')">
        @csrf @method('DELETE')
        <button class="btn btn-secondary btn-sm" style="color:var(--danger)"><i data-lucide="trash-2"></i> <span>Sil</span></button>
    </form>
</div>

@endsection
