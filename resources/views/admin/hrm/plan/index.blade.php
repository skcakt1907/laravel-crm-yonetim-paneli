@extends('admin._layout')

@section('title', 'Gün Planı')

@push('head')
<style>
    .gp-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px}
    .gp-head h1{font-size:24px;font-weight:800;margin:0}
    .gp-head p{margin:4px 0 0;font-size:13px;color: var(--text-muted)}

    /* ── Gun gezinme ── */
    .gp-gun{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .gp-ok{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;
           border:1px solid var(--border);background: var(--surface);color: var(--text);text-decoration:none}
    .gp-ok:hover{border-color: var(--brand)}
    .gp-tarih{font-weight:700;font-size:14px;color: var(--text);padding:0 6px;min-width:190px;text-align:center}
    .gp-bugun{font-size:12.5px;font-weight:700;padding:7px 14px;border-radius:10px;border:1px solid var(--border);
              background: var(--surface);color: var(--text-secondary);text-decoration:none}

    /* ── Kisi blogu ── */
    .gp-kisi{background: var(--surface);border:1px solid var(--border);border-radius:16px;margin-bottom:14px;overflow:hidden}
    .gp-kisi-bas{display:flex;align-items:center;gap:10px;padding:13px 18px;border-bottom:1px solid var(--border)}
    .gp-av{width:32px;height:32px;border-radius:50%;object-fit:cover;background: var(--bg-subtle);flex-shrink:0}
    .gp-av-bos{width:32px;height:32px;border-radius:50%;background: var(--bg-subtle);flex-shrink:0;display:flex;
               align-items:center;justify-content:center;font-weight:800;font-size:13px;color: var(--text-muted)}
    .gp-ad{font-weight:700;font-size:14px;color: var(--text)}
    .gp-ozet{margin-left:auto;font-size:12px;color: var(--text-muted);display:flex;gap:12px;flex-wrap:wrap}

    /* ── Satirlar ── */
    .gp-satir{display:flex;align-items:flex-start;gap:12px;padding:11px 18px;border-bottom:1px solid var(--border)}
    .gp-satir:last-child{border-bottom:none}
    .gp-satir.bitti .gp-baslik{text-decoration: line-through;color: var(--text-muted)}
    .gp-saat{font-variant-numeric: tabular-nums;font-weight:700;font-size:13px;color: var(--text-secondary);
             white-space:nowrap;min-width:96px;padding-top:1px}
    .gp-icerik{flex:1;min-width:0}
    .gp-baslik{font-size:13.5px;font-weight:600;color: var(--text);word-break:break-word}
    .gp-aciklama{font-size:12px;color: var(--text-muted);margin-top:3px;word-break:break-word}
    .gp-yazan{font-size:11px;color: var(--text-muted);margin-top:3px}
    .gp-islem{display:flex;gap:6px;flex-shrink:0}
    .gp-mini{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:9px;
             border:1px solid var(--border);background: var(--surface);color: var(--text-secondary);cursor:pointer}
    .gp-mini:hover{border-color: var(--brand)}
    .gp-mini.sil:hover{border-color: var(--danger);color: var(--danger)}
    .gp-bos{padding:16px 18px;font-size:13px;color: var(--text-muted);font-style:italic}

    /* ── Ekleme formu ── */
    .gp-ekle{display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:13px 18px;background: var(--bg-subtle)}
    .gp-ekle input[type=time]{width:110px}
    .gp-ekle .gp-bas{flex:1;min-width:200px}
    .gp-ipucu{font-size:12px;color: var(--text-muted);display:flex;align-items:center;gap:6px;margin-top:10px}
</style>
@endpush

@section('content')
@php
    $dun    = $tarih->copy()->subDay();
    $yarin  = $tarih->copy()->addDay();
    $bugunMu = $tarih->isToday();
@endphp

<div class="gp-head">
    <div>
        <h1>Gün Planı</h1>
        <p>Saatli program. Herkes kendi gününü planlar; yöneticiler herkesinkini görür.</p>
    </div>

    <div class="gp-gun">
        <a href="{{ route('admin.hrm.plan.index', ['tarih' => $dun->toDateString()]) }}" class="gp-ok" title="Önceki gün">
            <i data-lucide="chevron-left"></i>
        </a>
        <span class="gp-tarih">{{ $tarih->translatedFormat('d F Y, l') }}</span>
        <a href="{{ route('admin.hrm.plan.index', ['tarih' => $yarin->toDateString()]) }}" class="gp-ok" title="Sonraki gün">
            <i data-lucide="chevron-right"></i>
        </a>
        @unless($bugunMu)
            <a href="{{ route('admin.hrm.plan.index') }}" class="gp-bugun">Bugüne dön</a>
        @endunless
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">{{ $errors->first() }}</div>
@endif

@foreach($personeller as $kisi)
    @php
        $satirlar = $planlar->get($kisi->id, collect());
        $benim    = $kisi->id == $aktifId;
        // Yalnizca kendi blogu ve yoneticiler duzenleyebilir
        $duzenle  = $benim || $yonetici;
    @endphp

    {{-- Baskalarinin bos gunleri ekrani sisirmesin; kendi blogun her
         zaman gorunur ki plan girebilesin. --}}
    @if($satirlar->isEmpty() && ! $benim)
        @continue
    @endif

    <div class="gp-kisi">
        <div class="gp-kisi-bas">
            @if($kisi->profil_foto)
                <img src="{{ asset('tema/uploads/profil/'.$kisi->profil_foto) }}" alt="" class="gp-av">
            @else
                <div class="gp-av-bos">{{ mb_strtoupper(mb_substr($kisi->adi ?: $kisi->kullaniciadi, 0, 1)) }}</div>
            @endif

            <span class="gp-ad">{{ $kisi->adi ?: $kisi->kullaniciadi }}{{ $benim ? ' (sen)' : '' }}</span>

            @php $ozet = \App\Services\PersonelGunPlani::ozet($satirlar); @endphp
            @if($ozet['adet'] > 0)
                <span class="gp-ozet">
                    <span>{{ $ozet['adet'] }} iş</span>
                    <span>{{ $ozet['tamamlanan'] }} tamamlandı</span>
                    @if($ozet['saat'] > 0)<span>{{ $ozet['saat'] }} saat</span>@endif
                </span>
            @endif
        </div>

        @forelse($satirlar as $satir)
            <div class="gp-satir {{ $satir->tamamlandi ? 'bitti' : '' }}">
                <span class="gp-saat">
                    {{ \Illuminate\Support\Str::substr($satir->baslangic, 0, 5) }}
                    @if($satir->bitis) – {{ \Illuminate\Support\Str::substr($satir->bitis, 0, 5) }} @endif
                </span>

                <div class="gp-icerik">
                    <div class="gp-baslik">{{ $satir->baslik }}</div>
                    @if($satir->aciklama)
                        <div class="gp-aciklama">{{ $satir->aciklama }}</div>
                    @endif
                    {{-- Satiri baskasi yazdiysa kim yazdi gorunsun --}}
                    @if($satir->olusturan_id && $satir->olusturan_id != $satir->yonetici_id)
                        <div class="gp-yazan">{{ $satir->olusturan_adi }} ekledi</div>
                    @endif
                </div>

                @if($duzenle)
                    <div class="gp-islem">
                        <form method="POST" action="{{ route('admin.hrm.plan.isaretle', $satir->id) }}">
                            @csrf
                            <button type="submit" class="gp-mini"
                                    title="{{ $satir->tamamlandi ? 'Geri al' : 'Tamamlandı işaretle' }}">
                                <i data-lucide="{{ $satir->tamamlandi ? 'rotate-ccw' : 'check' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.hrm.plan.sil', $satir->id) }}"
                              onsubmit="return confirm('Bu satırı silmek istediğine emin misin?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="gp-mini sil" title="Sil">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="gp-bos">Bu gün için plan girilmemiş.</div>
        @endforelse

        @if($duzenle)
            <form method="POST" action="{{ route('admin.hrm.plan.ekle') }}" class="gp-ekle">
                @csrf
                <input type="hidden" name="tarih" value="{{ $tarih->toDateString() }}">
                <input type="hidden" name="yonetici_id" value="{{ $kisi->id }}">

                <input type="time" name="baslangic" class="form-input" required title="Başlangıç">
                <input type="time" name="bitis" class="form-input" title="Bitiş (isteğe bağlı)">
                <input type="text" name="baslik" class="form-input gp-bas" maxlength="160" required
                       placeholder="Ne yapılacak? — örn. Vidal Dent çekimi">
                <button type="submit" class="btn btn-primary btn-sm">Ekle</button>
            </form>
        @endif
    </div>
@endforeach

{{-- ══ DÜNÜ KOPYALA ═══════════════════════════════════════════════
     Çoğu gün bir öncekine benziyor. Her sabah aynı satırları elle
     yazmak insanları planı hiç doldurmamaya iter. --}}
<form method="POST" action="{{ route('admin.hrm.plan.kopyala') }}"
      style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px">
    @csrf
    <input type="hidden" name="kaynak" value="{{ $dun->toDateString() }}">
    <input type="hidden" name="hedef" value="{{ $tarih->toDateString() }}">
    <button type="submit" class="btn btn-ghost btn-sm">
        <i data-lucide="copy"></i> <span>Dünkü planımı bu güne kopyala</span>
    </button>
    <span class="gp-ipucu">Mevcut satırlar silinmez, üzerine eklenir.</span>
</form>
@endsection
