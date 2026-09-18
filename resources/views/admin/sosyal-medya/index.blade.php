@extends('admin._layout')

@section('title', 'Sosyal Medya Takibi')

@section('content')

@php
    $bugun    = \Illuminate\Support\Carbon::today();
    $eksikSay = $satirlar->sum('eksik');
    $tamamSay = $satirlar->where('tamam', true)->whereNull('not')->count();
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Sosyal Medya Takibi</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Sosyal Medya Takibi</h1>
        <div class="page-subtitle">
            {{ $tarih->translatedFormat('d F Y, l') }}
            @if($tarih->isToday()) · bugün @endif
            · {{ $satirlar->count() }} platform
        </div>
    </div>
    <div class="page-actions">
        {{-- Geriye gitme: sınır servisten geliyor, ekranda da aynı sınır uygulanır --}}
        @php $onceki = $tarih->copy()->subDay(); @endphp
        @if($onceki->diffInDays($bugun) <= $geriyeGun)
            <a href="{{ route('admin.crm.sosyal-medya-takip.index', ['tarih' => $onceki->toDateString(), 'benim' => $sadeceBenim ? 1 : null]) }}"
               class="btn btn-secondary btn-sm"><i data-lucide="chevron-left"></i> <span>Önceki gün</span></a>
        @endif

        @if(! $tarih->isToday())
            <a href="{{ route('admin.crm.sosyal-medya-takip.index', ['benim' => $sadeceBenim ? 1 : null]) }}"
               class="btn btn-secondary btn-sm"><i data-lucide="calendar"></i> <span>Bugün</span></a>
        @endif

        <a href="{{ route('admin.crm.sosyal-medya-takip.index', ['tarih' => $tarih->toDateString(), 'benim' => $sadeceBenim ? null : 1]) }}"
           class="btn {{ $sadeceBenim ? 'btn-primary' : 'btn-secondary' }} btn-sm">
            <i data-lucide="user"></i> <span>{{ $sadeceBenim ? 'Sadece benim' : 'Tümü' }}</span>
        </a>

        <a href="{{ route('admin.crm.sosyal-medya-takip.plan') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="calendar-cog"></i> <span>Plan</span>
        </a>

        <a href="{{ route('admin.crm.sosyal-medya-takip.aylik') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="bar-chart-3"></i> <span>Aylık rapor</span>
        </a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

{{-- Günün özeti: eksik varsa önce o görünsün --}}
<div class="sm-ozet">
    <div class="sm-ozet-kutu {{ $eksikSay > 0 ? 'uyari' : 'tamam' }}">
        <span class="sm-ozet-sayi">{{ $eksikSay }}</span>
        <span class="sm-ozet-etiket">eksik paylaşım</span>
    </div>
    <div class="sm-ozet-kutu">
        <span class="sm-ozet-sayi">{{ $tamamSay }}</span>
        <span class="sm-ozet-etiket">tamamlanan platform</span>
    </div>
    <div class="sm-ozet-kutu">
        <span class="sm-ozet-sayi">{{ $satirlar->sum('yapilan') }}<span class="sm-bolu">/{{ $satirlar->sum('hedef') }}</span></span>
        <span class="sm-ozet-etiket">toplam paylaşım</span>
    </div>
</div>

@unless($isaretlenebilir)
    <div class="alert alert-warning">
        Bu tarih işaretleme penceresinin dışında (en fazla {{ $geriyeGun }} gün geriye).
        Kayıtlar salt okunur.
    </div>
@endunless

@if($bugueErtelenenler->count())
    <div class="section sm-ertelenen-uyari">
        <strong><i data-lucide="corner-down-right"></i> Bugüne ertelenenler</strong>
        <ul>
            @foreach($bugueErtelenenler as $e)
                <li>
                    {{ $e->hesap->kayit->baslik ?? '—' }} · {{ $e->hesap->platform ?? '—' }}
                    <span class="sm-soluk">({{ optional($e->tarih)->format('d.m.Y') }} tarihinden ertelendi{{ $e->sebep ? ' — ' . $e->sebep : '' }})</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@forelse($satirlar as $s)
    @php
        $h    = $s['hesap'];
        $not  = $s['not'];
        $oran = $s['hedef'] > 0 ? min(100, round($s['yapilan'] / $s['hedef'] * 100)) : 0;
    @endphp

    <div class="sm-kart {{ $not ? 'sm-notlu' : ($s['tamam'] ? 'sm-tamam' : 'sm-eksik') }}">

        <div class="sm-kart-bas">
            <div>
                <div class="sm-marka">{{ $s['marka'] }}</div>
                <div class="sm-platform">
                    <i data-lucide="at-sign"></i> {{ $h->platform }}
                    @if($h->kullanici_adi)<span class="sm-soluk">· {{ $h->kullanici_adi }}</span>@endif
                    @if($h->link)
                        <a href="{{ $h->link }}" target="_blank" rel="noopener" class="sm-soluk">· profil</a>
                    @endif
                </div>
            </div>

            <div class="sm-sag">
                <div class="sm-sorumlu">
                    <i data-lucide="user"></i>
                    {{ optional($h->sorumlu)->adi ?? 'Atanmamış' }}
                </div>
                <div class="sm-oran {{ $s['tamam'] ? 'ok' : 'eksik' }}">
                    {{ $s['yapilan'] }}<span class="sm-bolu">/{{ $s['hedef'] }}</span>
                </div>

                {{-- Platformu bu günün listesinden çıkar. Haftalık plana
                     DOKUNMAZ — sadece bu tarihe hedef 0 yazılır, gelecek
                     haftalar aynı kalır. Başlıkta duruyor ki erteleme/iptal
                     notu varken de erişilebilsin. --}}
                @if($isaretlenebilir)
                    <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.gunden-kaldir') }}"
                          class="sm-gunden-kaldir"
                          onsubmit="return confirm('{{ $s['marka'] }} · {{ $h->platform }} bu günün listesinden çıkarılsın mı?\n\nHaftalık plan değişmez, sadece {{ $tarih->format('d.m.Y') }} için geçerlidir.')">
                        @csrf
                        <input type="hidden" name="hesap_id" value="{{ $h->id }}">
                        <input type="hidden" name="tarih" value="{{ $tarih->toDateString() }}">
                        <button type="submit" class="btn btn-ghost btn-xs"
                                title="Bu günün listesinden çıkar">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="sm-cubuk"><span style="width:{{ $oran }}%"></span></div>

        {{-- Erteleme / iptal varsa paylaşım eklenemez; önce kaldırılmalı --}}
        @if($not)
            <div class="sm-not">
                <span class="sm-rozet {{ $not->tip === 'ertelendi' ? 'ertelendi' : 'iptal' }}">
                    {{ $not->tip === 'ertelendi' ? 'Ertelendi' : 'İptal' }}
                </span>
                @if($not->tip === 'ertelendi' && $not->ertelendi_tarih)
                    <strong>→ {{ $not->ertelendi_tarih->format('d.m.Y') }}</strong>
                @endif
                <span class="sm-soluk">{{ $not->sebep }}</span>
                <span class="sm-soluk">· {{ optional($not->isaretleyen)->adi ?? '—' }},
                    {{ optional($not->isaretlendi_at)->format('d.m H:i') }}</span>

                @if($isaretlenebilir)
                    <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.gun-notu.sil', $not->id) }}"
                          onsubmit="return confirm('Bu kaydı kaldırmak istediğinize emin misiniz?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-secondary btn-xs">Kaldır</button>
                    </form>
                @endif
            </div>
        @else
            {{-- ── CHECKLIST ──────────────────────────────────────────
                 O güne isimli paylaşım girilmişse (Plan ekranından),
                 serbest "Yapıldı" yerine tek tek işaretlenen liste
                 gösterilir — hangi postun eksik kaldığı belli olsun. --}}
            @if($s['kalemler']->count())
                <ul class="sm-checklist">
                    @foreach($s['kalemler'] as $kalem)
                        @php $yapildi = $kalem->paylasim; @endphp
                        <li class="{{ $yapildi ? 'yapildi' : '' }}">
                            <i data-lucide="{{ $yapildi ? 'check-circle-2' : 'circle' }}"></i>
                            <span class="sm-checklist-baslik">{{ $kalem->baslik }}</span>

                            @if($yapildi)
                                <span class="sm-saat">{{ optional($yapildi->isaretlendi_at)->format('H:i') ?? '—' }}</span>
                                @if($yapildi->gecIsaretlendi())
                                    <span class="sm-rozet gec" title="Paylaşım günü ile işaretleme günü farklı">sonradan</span>
                                @endif
                                @if($yapildi->link)
                                    <a href="{{ $yapildi->link }}" target="_blank" rel="noopener">bağlantı</a>
                                @endif
                                @if($yapildi->not)<span class="sm-soluk">{{ $yapildi->not }}</span>@endif

                                @if($isaretlenebilir)
                                    <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.paylasim.sil', $yapildi->id) }}"
                                          onsubmit="return confirm('İşaret kaldırılsın mı?')">
                                        @csrf @method('DELETE')
                                        <button class="sm-sil" title="İşareti kaldır">&times;</button>
                                    </form>
                                @endif
                            @elseif($isaretlenebilir)
                                <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.paylasim.ekle') }}"
                                      class="sm-checklist-form">
                                    @csrf
                                    <input type="hidden" name="hesap_id" value="{{ $h->id }}">
                                    <input type="hidden" name="tarih" value="{{ $tarih->toDateString() }}">
                                    <input type="hidden" name="kalem_id" value="{{ $kalem->id }}">
                                    <input type="url" name="link" class="form-input" placeholder="Link (isteğe bağlı)">
                                    <button class="btn btn-primary btn-xs">
                                        <i data-lucide="check"></i> <span>Yapıldı</span>
                                    </button>
                                </form>
                            @endif

                            {{-- Paylaşımı PLANDAN kaldır. "İşareti kaldır"dan farklı:
                                 o sadece yapıldı damgasını siler, paylaşım listede kalır.
                                 Bu ise paylaşımın kendisini listeden çıkarır.
                                 İşaretlenmişse yapılan iş kaydı SİLİNMEZ, bağı kopar. --}}
                            @if($isaretlenebilir)
                                <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.kalem.sil', $kalem->id) }}"
                                      class="sm-kalem-sil"
                                      onsubmit="return confirm('{{ $kalem->baslik }} — bu paylaşım plandan kaldırılsın mı?{{ $yapildi ? ' (İşaretlenmiş kayıt silinmez, sadece plandan çıkar.)' : '' }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs" title="Paylaşımı plandan kaldır">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Kaleme bağlı OLMAYAN paylaşımlar (serbest işaretlemeler) --}}
            @if($s['paylasimlar']->whereNull('kalem_id')->count())
                <ul class="sm-paylasimlar">
                    @foreach($s['paylasimlar']->whereNull('kalem_id')->values() as $i => $p)
                        <li>
                            <span class="sm-sira">{{ $i + 1 }}</span>
                            <span class="sm-saat">{{ optional($p->isaretlendi_at)->format('H:i') ?? '—' }}</span>
                            @if($p->gecIsaretlendi())
                                <span class="sm-rozet gec" title="Paylaşım günü ile işaretleme günü farklı">sonradan işaretlendi</span>
                            @endif
                            @if($p->link)
                                <a href="{{ $p->link }}" target="_blank" rel="noopener">bağlantı</a>
                            @endif
                            @if($p->not)<span class="sm-soluk">{{ $p->not }}</span>@endif
                            <span class="sm-soluk">· {{ optional($p->isaretleyen)->adi ?? '—' }}</span>

                            @if($isaretlenebilir)
                                <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.paylasim.sil', $p->id) }}"
                                      onsubmit="return confirm('Bu paylaşım kaydı silinsin mi?')">
                                    @csrf @method('DELETE')
                                    <button class="sm-sil" title="Sil">&times;</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($isaretlenebilir)
                <div class="sm-islem">
                    {{-- Serbest "Yapıldı" yalnızca kalem GİRİLMEMİŞ günlerde:
                         kalem varken hem checklist hem serbest buton olursa
                         hedef 3 iken 4 paylaşım işaretlenebilirdi. --}}
                    @if($s['kalemler']->isEmpty())
                    <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.paylasim.ekle') }}" class="sm-form">
                        @csrf
                        <input type="hidden" name="hesap_id" value="{{ $h->id }}">
                        <input type="hidden" name="tarih" value="{{ $tarih->toDateString() }}">
                        <input type="url" name="link" class="form-input" placeholder="Paylaşım linki (isteğe bağlı)">
                        <input type="text" name="not" class="form-input" placeholder="Not (isteğe bağlı)">
                        <button class="btn btn-primary btn-sm"><i data-lucide="check"></i> <span>Yapıldı</span></button>
                    </form>
                    @endif

                    {{-- Ertele / İptal --}}
                    <button type="button" class="btn btn-secondary btn-sm"
                            onclick="smNotAc({{ $h->id }})"><i data-lucide="clock"></i> <span>Ertele / İptal</span></button>
                </div>

                <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.gun-notu') }}"
                      id="smNot{{ $h->id }}" class="sm-not-form" style="display:none">
                    @csrf
                    <input type="hidden" name="hesap_id" value="{{ $h->id }}">
                    <input type="hidden" name="tarih" value="{{ $tarih->toDateString() }}">

                    <select name="tip" class="form-select" onchange="smTipDegisti(this, {{ $h->id }})">
                        <option value="ertelendi">Ertelendi — başka güne alındı</option>
                        <option value="iptal">İptal — yapılmayacak</option>
                    </select>

                    <input type="date" name="ertelendi_tarih" id="smTarih{{ $h->id }}"
                           class="form-input" min="{{ $tarih->copy()->addDay()->toDateString() }}"
                           placeholder="Yeni tarih">

                    <input type="text" name="sebep" class="form-input" required
                           placeholder="Sebep (zorunlu)">

                    <button class="btn btn-primary btn-sm">Kaydet</button>
                </form>
            @endif
        @endif
    </div>
@empty
    <div class="section sm-bos">
        <i data-lucide="calendar-off"></i>
        <strong>Bu gün için paylaşım planı yok.</strong>
        <p>Haftalık plan tanımlanmamış olabilir veya bugün kapalı bir gün.</p>
    </div>
@endforelse

<script>
function smNotAc(id){
    var f = document.getElementById('smNot' + id);
    f.style.display = f.style.display === 'none' ? 'flex' : 'none';
}
function smTipDegisti(sec, id){
    // İptal seçilirse tarih alanı anlamsız — gizle ve temizle
    var t = document.getElementById('smTarih' + id);
    var ertele = sec.value === 'ertelendi';
    t.style.display = ertele ? '' : 'none';
    if (!ertele) t.value = '';
}
</script>

<style>
.sm-ozet{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.sm-ozet-kutu{background:var(--card,#fff);border:1px solid var(--border,#e5e7eb);border-radius:10px;padding:10px 16px;min-width:130px}
.sm-ozet-kutu.uyari{border-color:#dc2626;background:rgba(220,38,38,.06)}
.sm-ozet-kutu.tamam{border-color:#16a34a;background:rgba(22,163,74,.06)}
.sm-ozet-sayi{display:block;font-size:24px;font-weight:700;line-height:1.1}
.sm-ozet-etiket{font-size:12px;color:var(--text-muted,#6b7280)}
.sm-bolu{font-size:.62em;font-weight:500;color:var(--text-muted,#6b7280)}

.sm-kart{background:var(--card,#fff);border:1px solid var(--border,#e5e7eb);border-left-width:4px;border-radius:10px;padding:12px 14px;margin-bottom:10px}
.sm-kart.sm-tamam{border-left-color:#16a34a}
.sm-kart.sm-eksik{border-left-color:#dc2626}
.sm-kart.sm-notlu{border-left-color:#a16207}

.sm-kart-bas{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}
.sm-marka{font-weight:700;font-size:15px}
.sm-platform{font-size:13px;color:var(--text-muted,#6b7280);display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.sm-platform i{width:14px;height:14px}
.sm-sag{text-align:right;display:flex;flex-direction:column;gap:2px;align-items:flex-end}
.sm-sorumlu{font-size:12px;color:var(--text-muted,#6b7280);display:flex;align-items:center;gap:4px}
.sm-sorumlu i{width:13px;height:13px}
.sm-oran{font-size:20px;font-weight:700;font-variant-numeric:tabular-nums}
.sm-oran.ok{color:#16a34a}
.sm-oran.eksik{color:#dc2626}

.sm-cubuk{height:4px;background:var(--border,#e5e7eb);border-radius:99px;margin:10px 0 8px;overflow:hidden}
.sm-cubuk span{display:block;height:100%;background:#16a34a;border-radius:99px}

.sm-paylasimlar{list-style:none;margin:0 0 8px;padding:0;font-size:13px}
.sm-paylasimlar li{display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid var(--border-soft,#f3f4f6);flex-wrap:wrap}
.sm-paylasimlar li:last-child{border-bottom:0}
.sm-sira{background:var(--border-soft,#f3f4f6);width:19px;height:19px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:600}
.sm-saat{font-variant-numeric:tabular-nums;font-weight:600}
.sm-soluk{color:var(--text-muted,#6b7280);font-size:12px}
.sm-sil{border:0;background:none;color:var(--text-muted,#9ca3af);cursor:pointer;font-size:17px;line-height:1;padding:0 3px}
.sm-sil:hover{color:#dc2626}
.sm-paylasimlar form{display:inline;margin-left:auto}

.sm-islem{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.sm-form{display:flex;gap:6px;flex-wrap:wrap;align-items:center;flex:1 1 320px}
.sm-form .form-input{flex:1 1 130px;min-width:0;font-size:13px;padding:5px 8px}
.sm-not-form{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:8px;padding-top:8px;border-top:1px dashed var(--border,#e5e7eb)}
.sm-not-form .form-input,.sm-not-form .form-select{font-size:13px;padding:5px 8px}

.sm-not{display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:13px}
.sm-not form{margin-left:auto}
.sm-rozet{font-size:11px;font-weight:600;padding:2px 7px;border-radius:4px}
.sm-rozet.ertelendi{background:rgba(161,98,7,.12);color:#a16207}
.sm-rozet.iptal{background:rgba(107,114,128,.14);color:#4b5563}
.sm-rozet.gec{background:rgba(161,98,7,.12);color:#a16207}

.sm-ertelenen-uyari{border-left:4px solid #a16207}
.sm-ertelenen-uyari ul{margin:6px 0 0;padding-left:18px;font-size:13px}
.sm-bos{text-align:center;color:var(--text-muted,#6b7280);padding:28px}
.sm-bos i{width:30px;height:30px;margin-bottom:8px}
.sm-bos p{margin:4px 0 0;font-size:13px}

@media (max-width:640px){
  .sm-kart-bas{flex-direction:column}
  .sm-sag{text-align:left;align-items:flex-start}
}

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

/* ── Checklist (tarihe ozel isimli paylasimlar) ── */
.sm-checklist { list-style: none; margin: 10px 0 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.sm-checklist li { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 13px; }
.sm-checklist li > svg { width: 16px; height: 16px; flex: 0 0 auto; color: var(--text-muted); }
.sm-checklist li.yapildi > svg { color: #1E6B2F; }
.sm-checklist li.yapildi .sm-checklist-baslik { text-decoration: line-through; opacity: .6; }
.sm-checklist-baslik { font-weight: 600; }
.sm-checklist-form { display: flex; gap: 6px; align-items: center; margin-left: auto; }
.sm-checklist-form .form-input { width: 200px; padding: 5px 9px; font-size: 12px; }
.sm-kalem-sil { margin-left: 4px; }
.sm-kalem-sil .btn svg { width: 13px; height: 13px; }
.sm-gunden-kaldir { display: inline-flex; }
.sm-gunden-kaldir .btn svg { width: 14px; height: 14px; }
.sm-sag { display: flex; align-items: center; gap: 10px; }
</style>

@endsection
