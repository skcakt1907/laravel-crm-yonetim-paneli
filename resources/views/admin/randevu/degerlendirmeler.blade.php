@extends('admin._layout')

@section('title', 'Randevu Değerlendirmeleri')

@push('head')
<style>
    .dg-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
    .dg-stat {
        background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md);
        padding: 16px 18px;
    }
    .dg-stat .lbl { font-size: 12px; color: var(--text-soft); letter-spacing: .03em; margin-bottom: 6px; }
    .dg-stat .val { font-size: 24px; font-weight: 800; color: var(--text); }
    .dg-stat .val small { font-size: 13px; font-weight: 600; color: var(--text-soft); }

    .dg-filtre { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; align-items: center; }
    .dg-chip {
        display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 20px;
        font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid var(--border);
        color: var(--text-soft); background: var(--surface); transition: all .15s;
    }
    .dg-chip:hover { border-color: var(--brand); color: var(--text); }
    .dg-chip.aktif { background: var(--brand); border-color: var(--brand); color: #1f2937; }

    .dg-table { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border); }
    .dg-table th { text-align: left; font-size: 12px; letter-spacing: .04em; color: var(--text-soft); padding: 12px 14px; border-bottom: 1px solid var(--border); text-transform: uppercase; }
    .dg-table td { padding: 12px 14px; font-size: 14px; border-bottom: 1px solid var(--border); vertical-align: top; }
    .dg-table tr:last-child td { border-bottom: none; }
    .dg-table tr:hover td { background: var(--brand-soft); }

    .dg-stars { color: #d4a800; font-size: 15px; letter-spacing: 2px; white-space: nowrap; }
    .dg-stars .bos { color: #d6d8cf; }
    .dg-yorum { max-width: 420px; color: var(--text); line-height: 1.55; }
    .dg-yorum.bos { color: var(--text-soft); font-style: italic; }
    .dg-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
    .dg-badge.ok { background: rgba(16,185,129,.13); color: #047857; }
    .dg-badge.bekliyor { background: rgba(184,182,46,.16); color: #8a6d00; }
    .dg-musteri { font-weight: 700; color: var(--text); }
    .dg-alt { font-size: 12.5px; color: var(--text-soft); margin-top: 2px; }
    .dg-bos-durum { text-align: center; padding: 48px 16px; color: var(--text-soft); }
</style>
@endpush

@section('content')
<div class="page-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px">
    <h1 style="display:flex;align-items:center;gap:10px;font-size:20px;font-weight:800;margin:0">
        <i data-lucide="star"></i> Randevu Değerlendirmeleri
    </h1>
    <a href="{{ route('admin.randevu.randevular') }}" class="dg-chip">
        <i data-lucide="calendar" style="width:15px;height:15px"></i> Randevulara Dön
    </a>
</div>

{{-- ÖZET --}}
<div class="dg-stats">
    <div class="dg-stat">
        <div class="lbl">ORTALAMA PUAN</div>
        <div class="val">{{ $ozet['ortalama'] !== null ? number_format($ozet['ortalama'], 1, ',', '.') : '—' }} <small>/ 5</small></div>
    </div>
    <div class="dg-stat">
        <div class="lbl">DOLDURULAN</div>
        <div class="val">{{ $ozet['doldurulan'] }} <small>/ {{ $ozet['toplam'] }} davet</small></div>
    </div>
    <div class="dg-stat">
        <div class="lbl">5 YILDIZ</div>
        <div class="val">{{ $ozet['bes'] }}</div>
    </div>
</div>

{{-- FİLTRELER --}}
<div class="dg-filtre">
    <a href="{{ route('admin.randevu.degerlendirmeler') }}" class="dg-chip {{ empty($secimPuan) && empty($secimDurum) ? 'aktif' : '' }}">Tümü</a>
    <a href="{{ route('admin.randevu.degerlendirmeler', ['durum' => 'dolduruldu']) }}" class="dg-chip {{ $secimDurum === 'dolduruldu' ? 'aktif' : '' }}">Dolduruldu</a>
    <a href="{{ route('admin.randevu.degerlendirmeler', ['durum' => 'bekliyor']) }}" class="dg-chip {{ $secimDurum === 'bekliyor' ? 'aktif' : '' }}">Bekliyor</a>
    <span style="width:1px;height:22px;background:var(--border);margin:0 4px"></span>
    @for($p = 5; $p >= 1; $p--)
        <a href="{{ route('admin.randevu.degerlendirmeler', ['puan' => $p]) }}" class="dg-chip {{ (string) $secimPuan === (string) $p ? 'aktif' : '' }}">{{ $p }} ⭐</a>
    @endfor
</div>

{{-- LİSTE --}}
@if($liste->count() === 0)
    <div class="dg-bos-durum">
        <div style="font-size:40px;margin-bottom:10px">⭐</div>
        <div style="font-weight:700;color:var(--text);margin-bottom:4px">Henüz değerlendirme yok</div>
        <div>Randevulardan 2 saat sonra müşterilere otomatik değerlendirme daveti gönderilir; sonuçlar burada listelenir.</div>
    </div>
@else
    <table class="dg-table">
        <thead>
            <tr>
                <th>Müşteri</th>
                <th>Randevu</th>
                <th>Puan</th>
                <th>Yorum</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            @foreach($liste as $d)
                <tr>
                    <td>
                        <div class="dg-musteri">{{ $d->musteri_adi ?? '—' }}</div>
                        @if(!empty($d->calisan_ad))<div class="dg-alt">İlgili: {{ $d->calisan_ad }}</div>@endif
                    </td>
                    <td>
                        @php
                            $randevuTarih = null;
                            if (!empty($d->baslangic)) {
                                try { $randevuTarih = \Carbon\Carbon::parse($d->baslangic)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
                            }
                        @endphp
                        <div>{{ $randevuTarih ?? '—' }}</div>
                        @if(!empty($d->lokasyon_ad))<div class="dg-alt">{{ $d->lokasyon_ad }}</div>@endif
                    </td>
                    <td>
                        @if(!empty($d->puan))
                            <span class="dg-stars">@for($i = 1; $i <= 5; $i++)@if($i <= (int) $d->puan)★@else<span class="bos">★</span>@endif @endfor</span>
                            <div class="dg-alt">{{ (int) $d->puan }}/5</div>
                        @else
                            <span style="color:var(--text-soft)">—</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($d->yorum))
                            <div class="dg-yorum">{{ $d->yorum }}</div>
                        @else
                            <div class="dg-yorum bos">Yorum yazılmamış</div>
                        @endif
                    </td>
                    <td>
                        @if(!empty($d->dolduruldu_at))
                            @php
                                $dolduruldu = null;
                                try { $dolduruldu = \Carbon\Carbon::parse($d->dolduruldu_at)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
                            @endphp
                            <span class="dg-badge ok">Dolduruldu</span>
                            @if($dolduruldu)<div class="dg-alt">{{ $dolduruldu }}</div>@endif
                        @else
                            <span class="dg-badge bekliyor">Bekliyor</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($liste->hasPages())
        <div style="margin-top:16px;display:flex;justify-content:center">{{ $liste->links() }}</div>
    @endif
@endif
@endsection