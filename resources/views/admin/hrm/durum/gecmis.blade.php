@extends('admin._layout')

@section('title', 'Durum Geçmişi')

@push('head')
<style>
    .dg-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:18px}
    .dg-head h1{font-size:24px;font-weight:800;margin:0}
    .dg-head p{margin:4px 0 0;font-size:13px;color: var(--text-muted)}

    /* ── Suzgec ── */
    .dg-suz{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;
            background: var(--surface);border:1px solid var(--border);border-radius:14px;padding:14px 16px;margin-bottom:18px}
    .dg-suz label{display:block;font-size:11.5px;font-weight:700;color: var(--text-muted);margin-bottom:4px}
    .dg-kisayol{display:flex;gap:6px;flex-wrap:wrap;margin-left:auto;align-items:center}
    .dg-kisayol a{font-size:12px;font-weight:600;padding:7px 12px;border-radius:9px;
                  border:1px solid var(--border);background: var(--surface);color: var(--text-secondary);text-decoration:none}
    .dg-kisayol a:hover{border-color: var(--brand)}
    .dg-kisayol a.secili{background: var(--brand);border-color: var(--brand);color:#fff}

    /* ── Ozet kartlari ── */
    .dg-ozet{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:12px;margin-bottom:22px}
    .dg-kart{background: var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden}
    .dg-kart-bas{display:flex;align-items:baseline;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border)}
    .dg-kart-ad{font-weight:700;font-size:14px;color: var(--text)}
    .dg-kart-toplam{margin-left:auto;font-size:12.5px;font-weight:700;color: var(--text-secondary);
                    font-variant-numeric: tabular-nums}
    .dg-satir{display:flex;align-items:center;gap:9px;padding:8px 16px;font-size:13px}
    .dg-satir + .dg-satir{border-top:1px solid var(--border)}
    .dg-nokta{width:9px;height:9px;border-radius:50%;flex-shrink:0}
    .dg-ad{color: var(--text);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .dg-adet{font-size:11px;color: var(--text-muted)}
    .dg-sure{margin-left:auto;font-weight:700;color: var(--text-secondary);font-variant-numeric: tabular-nums;white-space:nowrap}

    /* ── Oran cubugu ── */
    .dg-cubuk{display:flex;height:6px;background: var(--bg-subtle)}
    .dg-cubuk span{display:block;height:100%}

    /* ── Dokum ── */
    .dg-bos{padding:36px 18px;text-align:center;color: var(--text-muted);font-size:13.5px}
    .dg-rozet{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;
              font-size:12px;font-weight:600;color:#fff;white-space:nowrap}
    .dg-not{font-size:12px;color: var(--text-muted);margin-top:2px}
    .dg-suruyor{font-size:10.5px;font-weight:700;color: var(--brand);margin-left:6px}
    .dg-saat{font-variant-numeric: tabular-nums;white-space:nowrap;font-size:12.5px}
    .dg-uyari{font-size:12.5px;color: var(--text-muted);margin-top:10px}
</style>
@endpush

@section('content')
@php
    // Kisayol baglantilari icin araliklar
    $buHafta   = ['bas' => now()->startOfWeek()->toDateString(),  'son' => now()->endOfWeek()->toDateString()];
    $gecenHafta= ['bas' => now()->subWeek()->startOfWeek()->toDateString(), 'son' => now()->subWeek()->endOfWeek()->toDateString()];
    $bugun     = ['bas' => now()->toDateString(), 'son' => now()->toDateString()];
    $dun       = ['bas' => now()->subDay()->toDateString(), 'son' => now()->subDay()->toDateString()];
    $buAy      = ['bas' => now()->startOfMonth()->toDateString(), 'son' => now()->endOfMonth()->toDateString()];

    $suAnki = ['bas' => $bas->toDateString(), 'son' => $son->toDateString()];
    $esit = fn ($a) => $a['bas'] === $suAnki['bas'] && $a['son'] === $suAnki['son'];
    $baglanti = fn ($a) => route('admin.hrm.durum.gecmis', array_merge($a, $kisiId ? ['kisi' => $kisiId] : []));
@endphp

<div class="dg-head">
    <div>
        <h1>Durum Geçmişi</h1>
        <p>
            Kim ne zaman hangi durumdaydı, ne kadar sürdü.
            {{ $bas->translatedFormat('d F Y') }} – {{ $son->translatedFormat('d F Y') }}
            @if($etiket) <strong>({{ $etiket }})</strong> @endif
        </p>
    </div>
    <a href="{{ route('admin.hrm.durum.index') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i> <span>Durum Panosu</span>
    </a>
</div>

<form method="GET" action="{{ route('admin.hrm.durum.gecmis') }}" class="dg-suz">
    <div>
        <label>Başlangıç</label>
        <input type="date" name="bas" class="form-input" value="{{ $bas->toDateString() }}">
    </div>
    <div>
        <label>Bitiş</label>
        <input type="date" name="son" class="form-input" value="{{ $son->toDateString() }}">
    </div>
    <div>
        <label>Kişi</label>
        <select name="kisi" class="form-select">
            <option value="">Herkes</option>
            @foreach($personeller as $p)
                <option value="{{ $p->id }}" {{ $kisiId === (int) $p->id ? 'selected' : '' }}>
                    {{ $p->adi ?: $p->kullaniciadi }}{{ $p->id == $aktifId ? ' (sen)' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Getir</button>

    <div class="dg-kisayol">
        <a href="{{ $baglanti($bugun) }}"      class="{{ $esit($bugun) ? 'secili' : '' }}">Bugün</a>
        <a href="{{ $baglanti($dun) }}"        class="{{ $esit($dun) ? 'secili' : '' }}">Dün</a>
        <a href="{{ $baglanti($buHafta) }}"    class="{{ $esit($buHafta) ? 'secili' : '' }}">Bu hafta</a>
        <a href="{{ $baglanti($gecenHafta) }}" class="{{ $esit($gecenHafta) ? 'secili' : '' }}">Geçen hafta</a>
        <a href="{{ $baglanti($buAy) }}"       class="{{ $esit($buAy) ? 'secili' : '' }}">Bu ay</a>
    </div>
</form>

@if($kayitlar->isEmpty())
    <div class="section">
        <div class="dg-bos">
            <i data-lucide="history" style="width:30px;height:30px;opacity:.5"></i>
            <div style="margin-top:10px">Bu aralıkta kayıt yok.</div>
        </div>
    </div>
@else

    {{-- ══ KİŞİ BAZINDA ÖZET ═══════════════════════════════════════
         Hangi durumda ne kadar vakit geçirilmiş. Süreler aralığa
         kırpılmış hâlleri: aralıktan önce başlayan bir durumun yalnızca
         aralığa düşen kısmı sayılır. --}}
    <div class="dg-ozet">
        @foreach($ozet as $satir)
            <div class="dg-kart">
                <div class="dg-kart-bas">
                    <span class="dg-kart-ad">{{ $satir['kisi'] }}</span>
                    <span class="dg-kart-toplam">{{ $satir['toplam_metin'] }}</span>
                </div>

                {{-- Oran cubugu: hangi durum gunun ne kadarini kapliyor --}}
                @if($satir['toplam'] > 0)
                    <div class="dg-cubuk">
                        @foreach($satir['durumlar'] as $d)
                            @if($d['dakika'] > 0)
                                <span style="width:{{ round($d['dakika'] / $satir['toplam'] * 100, 2) }}%;
                                             background:{{ $d['renk'] ?: '#6b7280' }}"
                                      title="{{ $d['ad'] }} — {{ $d['sure'] }}"></span>
                            @endif
                        @endforeach
                    </div>
                @endif

                @foreach($satir['durumlar'] as $d)
                    <div class="dg-satir">
                        <span class="dg-nokta" style="background:{{ $d['renk'] ?: '#6b7280' }}"></span>
                        <span class="dg-ad">{{ $d['emoji'] }} {{ $d['ad'] }}</span>
                        <span class="dg-adet">×{{ $d['adet'] }}</span>
                        <span class="dg-sure">{{ $d['sure'] }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- ══ DÖKÜM ═══════════════════════════════════════════════════ --}}
    <div class="section" style="padding:0">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border)">
            <h3 style="font-size:14px;font-weight:700;margin:0">
                Döküm
                <span class="badge badge-neutral" style="margin-left:6px">{{ $kayitlar->count() }}</span>
            </h3>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:150px">Kişi</th>
                        <th style="width:160px">Durum</th>
                        <th>Not</th>
                        <th style="width:180px">Başlangıç — Bitiş</th>
                        <th style="width:90px" class="text-right">Süre</th>
                        <th style="width:130px">Yazan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kayitlar as $k)
                        @php
                            $basladi = \Carbon\Carbon::parse($k->baslangic);
                            $bitti   = $k->bitis ? \Carbon\Carbon::parse($k->bitis) : null;
                            // Ayni gun icinde bitmisse bitis saatini tekrar tarihle yazma
                            $ayniGun = $bitti && $bitti->isSameDay($basladi);
                        @endphp
                        <tr>
                            <td style="font-weight:600;font-size:13px">
                                {{ $k->kisi_adi ?: $k->kisi_kullanici ?: '#'.$k->yonetici_id }}
                            </td>
                            <td>
                                <span class="dg-rozet" style="background:{{ $k->durum_renk ?: '#6b7280' }}">
                                    {{ $k->durum_emoji }} {{ $k->durum_adi }}
                                </span>
                            </td>
                            <td style="font-size:13px">
                                {{ $k->not ?: '—' }}
                            </td>
                            <td class="dg-saat">
                                {{ $basladi->translatedFormat('d.m.Y H:i') }}
                                @if($bitti)
                                    – {{ $ayniGun ? $bitti->format('H:i') : $bitti->translatedFormat('d.m.Y H:i') }}
                                @else
                                    <span class="dg-suruyor">sürüyor</span>
                                @endif
                            </td>
                            <td class="text-right dg-saat" style="font-weight:700">{{ $k->sure }}</td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                {{-- Durumu baskasi yazdiysa kim yazdi gorunsun --}}
                                @if($k->degistiren_id && (int) $k->degistiren_id !== (int) $k->yonetici_id)
                                    {{ $k->degistiren_adi ?: '#'.$k->degistiren_id }}
                                @else
                                    kendisi
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($kayitlar->count() >= $sinir)
        <p class="dg-uyari">
            Bu aralıkta {{ $sinir }}'den fazla kayıt var; en yenileri gösteriliyor.
            Daha dar bir tarih aralığı seçersen tamamını görürsün.
        </p>
    @endif
@endif
@endsection
