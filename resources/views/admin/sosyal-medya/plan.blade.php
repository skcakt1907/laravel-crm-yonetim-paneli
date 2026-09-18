@extends('admin._layout')

@section('title', 'Sosyal Medya Planı')

@section('content')

@php
    /* Aynı markanın satırları arka arkaya gelir; marka adı yalnız ilkinde
       yazılır, tekrar eden isim ızgarayı okunmaz hâle getiriyordu. */
    $oncekiMarka = null;
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.sosyal-medya-takip.index') }}">Sosyal Medya Takibi</a>
    <span class="sep">/</span>
    <span class="current">Plan</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Paylaşım Takvimi</h1>
        <div class="page-subtitle">
            Bir güne tıkla, o günün takibine git · {{ $hesaplar->count() }} platform
        </div>
    </div>
    <div class="page-actions">
        <button type="button" id="smSablonAc" class="btn btn-secondary btn-sm">
            <i data-lucide="table-2"></i> <span>Haftalık Şablon</span>
        </button>
        <button type="button" id="smRaporAc" class="btn btn-secondary btn-sm">
            <i data-lucide="mail"></i> <span>Rapor Ayarları</span>
        </button>
        <button type="button" id="smIstisnaAc" class="btn btn-secondary btn-sm">
            <i data-lucide="calendar-x"></i> <span>İstisnalar</span>
        </button>
        <button type="button" id="smEkleAc" class="btn btn-secondary btn-sm">
            <i data-lucide="plus"></i> <span>Marka / Platform Ekle</span>
        </button>
        <a href="{{ route('admin.crm.sosyal-medya-takip.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="calendar-check"></i> <span>Günlük takip</span>
        </a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

@include('admin.sosyal-medya._hesap-ekle-form')

@if($hesaplar->isEmpty())
    <div class="card">
        <div class="card-body sm-bos">
            <i data-lucide="inbox"></i>
            <p>Henüz sosyal medya hesabı tanımlı değil.</p>
            <p class="sm-bos-alt">Yukarıdaki formdan ilk marka ve platformunu ekle.</p>
        </div>
    </div>
@else

<form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.plan.kaydet') }}">
    @csrf

    <div class="card" id="smSablonKutu" hidden>
        <div class="card-header">
            <h2 class="card-title">Haftalık şablon</h2>
            <span class="card-desc">0 yazılan gün "paylaşım yok" demektir</span>
        </div>

        <div class="table-scroll">
            <table class="data-table sm-plan-tablo">
                <thead>
                    <tr>
                        <th class="sm-plan-marka">Marka / Platform</th>
                        @foreach($gunler as $no => $ad)
                            <th class="sm-plan-gun {{ $no === 7 ? 'pazar' : '' }}">
                                {{ mb_substr($ad, 0, 3) }}
                            </th>
                        @endforeach
                        <th class="sm-plan-toplam">Hafta</th>
                        <th class="sm-plan-sorumlu">Sorumlu uzman</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($hesaplar as $hesap)
                    @php
                        $marka   = $hesap->kayit->baslik ?? $hesap->kayit->firma ?? '—';
                        $satir   = $planlar[$hesap->id] ?? [];
                        $haftaTp = array_sum($satir);
                        $yeniMarka = $marka !== $oncekiMarka;
                        $oncekiMarka = $marka;
                    @endphp
                    <tr class="{{ $yeniMarka ? 'sm-plan-yeni-marka' : '' }}">
                        <td class="sm-plan-marka">
                            @if($yeniMarka)
                                <div class="sm-plan-marka-ad">{{ $marka }}</div>
                            @endif
                            <div class="sm-plan-platform">{{ $hesap->platform }}</div>
                        </td>

                        @foreach($gunler as $no => $ad)
                            <td class="sm-plan-gun {{ $no === 7 ? 'pazar' : '' }}">
                                <input type="number" min="0" max="50"
                                       name="hedef[{{ $hesap->id }}][{{ $no }}]"
                                       value="{{ $satir[$no] ?? 0 }}"
                                       class="sm-plan-input"
                                       title="{{ $marka }} · {{ $hesap->platform }} · {{ $ad }}"
                                       aria-label="{{ $marka }} {{ $hesap->platform }} {{ $ad }} hedefi">
                            </td>
                        @endforeach

                        <td class="sm-plan-toplam">
                            <span class="sm-hafta-toplam">{{ $haftaTp }}</span>
                        </td>

                        <td class="sm-plan-sorumlu">
                            <select name="sorumlu[{{ $hesap->id }}]" class="form-select">
                                <option value="">— atanmamış —</option>
                                @foreach($uzmanlar as $u)
                                    <option value="{{ $u->id }}" @selected($hesap->sorumlu_id == $u->id)>
                                        {{ $u->adi ?: $u->kullaniciadi }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer sm-plan-alt">
            <span class="sm-plan-ipucu">
                <i data-lucide="info"></i>
                Pazar sütunu varsayılan 0'dır. Tek bir tarihi değiştirmek için aşağıdaki
                <strong>istisna</strong> bölümünü kullanın — şablonu bozmadan o günü ezer.
            </span>
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="save"></i> <span>Planı kaydet</span>
            </button>
        </div>
    </div>
</form>

{{-- ── PAYLAŞIM EKLE ────────────────────────────────────────────
     Marka/platform + paylaşım adı + tarih. Girilen her paylaşım
     takvimde o güne düşer ve uzman ekranında checklist olarak çıkar. --}}
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Paylaşım ekle</h2>
        <span class="card-desc">Birden fazla eklemek için alt alta yaz</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.kalem.ekle') }}" class="sm-kalem-form">
            @csrf
            <div class="sm-kalem-ust">
                <div class="sm-istisna-alan genis">
                    <label>Marka / Platform</label>
                    <select name="hesap_id" class="form-select" required>
                        <option value="">Seçin…</option>
                        @foreach($hesaplar as $hesap)
                            <option value="{{ $hesap->id }}">
                                {{ $hesap->kayit->baslik ?? $hesap->kayit->firma ?? '—' }} · {{ $hesap->platform }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm-istisna-alan">
                    <label>Tarih</label>
                    <input type="date" name="tarih" id="smKalemTarih"
                           value="{{ now()->toDateString() }}" class="form-input" required>
                </div>
            </div>

            <div class="sm-istisna-alan">
                <label>Paylaşım adı <span class="sm-ekle-opsiyonel">(her satıra bir tane)</span></label>
                <textarea name="basliklar" rows="2" class="form-textarea" required
                          placeholder="Paylaşım adı…"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> <span>Ekle</span>
            </button>
        </form>
    </div>
</div>

{{-- ── TAKVİM ───────────────────────────────────────────────────
     Panelin kendi takvim ekranıyla AYNI kütüphane (FullCalendar) ve
     aynı ayarlar — iki ekran birbirine benzesin diye.

     Bir güne tıklayınca o günün takip ekranına gidilir; böylece
     "takvimde gör, tıkla, işaretle" akışı tek hamlede tamamlanıyor. --}}
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Takvim</h2>
        <span class="card-desc">Bir güne tıkla → o günün takibi açılır</span>
    </div>
    <div class="card-body">
        <div id="smTakvim"></div>
    </div>
</div>

{{-- ── GÜN SONU RAPORU ALICILARI ─────────────────────────────────
     Kimlere mail gideceği panelden seçilir. Hiç kimse seçilmezse
     sistem koddaki varsayılan listeye döner — rapor yanlışlıkla
     tamamen susturulmuş olmasın diye. --}}
<div class="card" id="smRaporKutu" hidden>
    <div class="card-header">
        <h2 class="card-title">Gün sonu raporu ayarları</h2>
        <span class="card-desc">Her gün {{ $raporSaati }}'te bu kişilere mail gider</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.rapor-alicilari') }}">
            @csrf
            <div class="sm-alici-liste">
                @foreach($uzmanlar as $u)
                    <label class="sm-alici">
                        <input type="checkbox" name="alicilar[]" value="{{ $u->id }}"
                               @checked(in_array($u->id, $raporAlicilari ?? []))>
                        <span>{{ $u->adi ?: $u->kullaniciadi }}</span>
                        @if($u->email)<span class="sm-soluk">{{ $u->email }}</span>@endif
                    </label>
                @endforeach
            </div>

            {{-- Gönderim saati: alıcılarla aynı formda — ikisi de "rapor
                 kime, ne zaman gitsin" ayarı, iki ayrı kaydet butonu gereksiz. --}}
            <div class="sm-rapor-saat">
                <label class="form-label" for="smRaporSaati">Gönderim saati</label>
                <input type="time" id="smRaporSaati" name="rapor_saati" class="form-input"
                       value="{{ $raporSaati }}" step="60" style="max-width:140px">
                <span class="sm-plan-ipucu">
                    <i data-lucide="clock"></i>
                    Boş bırakılırsa varsayılana ({{ \App\Services\SosyalMedyaRaporu::VARSAYILAN_SAAT }}) döner.
                </span>
            </div>
            @error('rapor_saati')<div class="sm-hata">{{ $message }}</div>@enderror

            <div class="sm-alici-alt">
                <span class="sm-plan-ipucu">
                    <i data-lucide="info"></i>
                    Hiç kimse seçilmezse varsayılan liste kullanılır:
                    Nurseli İnan, Nesimi Ateş, Dilan Ateş, Seda Baykal.
                </span>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="save"></i> <span>Ayarları kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── İSTİSNALAR ────────────────────────────────────────────────
     Şablonu bozmadan tek bir tarihi ezer. Tatil = hedef 0.        --}}
<div class="card" id="smIstisnaKutu" hidden>
    <div class="card-header">
        <h2 class="card-title">Tarihe özel istisnalar</h2>
        <span class="card-desc">Tatil için hedefi 0 yazın · kampanya için normalden yüksek</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.istisna.ekle') }}" class="sm-istisna-form">
            @csrf
            <div class="sm-istisna-alan">
                <label>Platform</label>
                <select name="hesap_id" class="form-select" required>
                    <option value="">Seçin…</option>
                    @foreach($hesaplar as $hesap)
                        <option value="{{ $hesap->id }}">
                            {{ $hesap->kayit->baslik ?? $hesap->kayit->firma ?? '—' }} · {{ $hesap->platform }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm-istisna-alan">
                <label>Tarih</label>
                <input type="date" name="tarih" class="form-input" required>
            </div>

            <div class="sm-istisna-alan dar">
                <label>Hedef</label>
                <input type="number" name="hedef_adet" min="0" max="50" value="0"
                       class="form-input" required>
            </div>

            <div class="sm-istisna-alan genis">
                <label>Sebep</label>
                <input type="text" name="sebep" maxlength="255"
                       placeholder="29 Ekim tatili / kampanya haftası…"
                       class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i> <span>Ekle</span>
            </button>
        </form>
    </div>

    @if($istisnalar->isNotEmpty())
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Marka / Platform</th>
                    <th>Hedef</th>
                    <th>Sebep</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($istisnalar as $i)
                @php $gecmis = $i->tarih->isPast() && ! $i->tarih->isToday(); @endphp
                <tr class="{{ $gecmis ? 'sm-gecmis' : '' }}">
                    <td>
                        {{ $i->tarih->translatedFormat('d F Y, l') }}
                        @if($i->tarih->isToday())<span class="badge badge-info">bugün</span>@endif
                    </td>
                    <td>
                        {{ $i->hesap->kayit->baslik ?? $i->hesap->kayit->firma ?? '—' }}
                        <span class="sm-plan-platform">{{ $i->hesap->platform ?? '' }}</span>
                    </td>
                    <td>
                        @if($i->hedef_adet === 0)
                            <span class="badge badge-info">tatil · 0</span>
                        @else
                            <span class="badge badge-brand">{{ $i->hedef_adet }} paylaşım</span>
                        @endif
                    </td>
                    <td>{{ $i->sebep ?: '—' }}</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('admin.crm.sosyal-medya-takip.istisna.sil', $i->id) }}"
                              onsubmit="return confirm('İstisna kaldırılsın mı? O gün haftalık şablona döner.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Kaldır">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="card-body sm-bos-kucuk">Tanımlı istisna yok — herkes haftalık şablona göre çalışıyor.</div>
    @endif
</div>

@endif

<style>
.sm-plan-tablo th, .sm-plan-tablo td { vertical-align: middle; }
.sm-plan-tablo .sm-plan-gun { width: 62px; text-align: center; }
.sm-plan-tablo .sm-plan-gun.pazar { background: rgba(0,0,0,.03); }
.sm-plan-tablo .sm-plan-toplam { width: 70px; text-align: center; }
.sm-plan-tablo .sm-plan-sorumlu { width: 190px; }
.sm-plan-tablo .sm-plan-marka { min-width: 190px; }
.sm-plan-marka-ad { font-weight: 600; }
.sm-plan-platform { font-size: 12px; color: var(--muted, #6b7280); }
.sm-plan-yeni-marka > td { border-top: 2px solid var(--border, #e5e7eb); }
.sm-plan-input {
    width: 52px; padding: 4px; text-align: center;
    border: 1px solid var(--border, #d1d5db); border-radius: 6px;
}
.sm-plan-input:focus { outline: 2px solid var(--primary, #2563eb); outline-offset: -1px; }
.sm-hafta-toplam { font-weight: 600; }
.sm-plan-alt { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.sm-plan-ipucu { font-size: 12px; color: var(--muted, #6b7280); display: flex; align-items: center; gap: 6px; }
.sm-istisna-form { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; }
.sm-istisna-alan { display: flex; flex-direction: column; gap: 6px; min-width: 150px; }
.sm-istisna-alan.dar { min-width: 90px; }
.sm-istisna-alan.genis { flex: 1; min-width: 220px; }
.sm-istisna-alan label { font-size: 12px; font-weight: 600; color: var(--muted, #6b7280); }
.sm-gecmis { opacity: .55; }
.sm-bos { text-align: center; padding: 40px 20px; }
.sm-bos-alt { font-size: 13px; color: var(--muted, #6b7280); }
.sm-bos-kucuk { font-size: 13px; color: var(--muted, #6b7280); }
.sm-ekle-form { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; }
.sm-ekle-alan { display: flex; flex-direction: column; gap: 6px; min-width: 170px; }
.sm-ekle-alan label { font-size: 12px; font-weight: 600; color: var(--muted, #6b7280); }
.sm-ekle-opsiyonel { font-weight: 400; }

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

/* ── Tarihe ozel paylasim listesi (checklist kalemleri) ── */
.sm-kalem-form { display: flex; flex-direction: column; gap: 14px; }
.sm-kalem-ust { display: flex; gap: 14px; flex-wrap: wrap; }
.sm-kalem-form textarea { resize: vertical; min-height: 76px; }
.sm-kalem-gun { border-top: 1px solid var(--border); padding: 14px 20px; }
.sm-kalem-gun-bas {
    display: flex; align-items: center; gap: 10px;
    font-weight: 600; font-size: 13px; margin-bottom: 8px;
}
.sm-kalem-liste { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.sm-kalem-liste li { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.sm-kalem-liste li svg { width: 15px; height: 15px; flex: 0 0 auto; color: var(--text-muted); }
.sm-kalem-liste li.yapildi svg { color: #1E6B2F; }
.sm-kalem-liste li.yapildi .sm-kalem-baslik { text-decoration: line-through; opacity: .65; }
.sm-kalem-baslik { font-weight: 500; }
.sm-kalem-liste form { margin-left: auto; }

/* ── Takvim ── */
#smTakvim { font-size: 13px; }
#smTakvim .fc-daygrid-event { white-space: normal; border-radius: 6px; padding: 1px 4px; }
#smTakvim .fc-daygrid-day:hover { background: var(--surface-hover); cursor: pointer; }
#smTakvim .fc-col-header-cell-cushion,
#smTakvim .fc-daygrid-day-number { color: var(--text); text-decoration: none; }
#smTakvim .fc-toolbar-title { font-size: 16px; font-weight: 700; }
#smTakvim .fc-button { font-size: 12px; }
.sm-alici-liste { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 8px; }
.sm-alici { display: flex; align-items: center; gap: 8px; padding: 8px 10px;
            border: 1px solid var(--border); border-radius: var(--radius-sm, 8px); cursor: pointer; font-size: 13px; }
.sm-alici:hover { border-color: var(--border-strong); }
.sm-alici input { margin: 0; }
.sm-alici .sm-soluk { margin-left: auto; font-size: 11px; }
.sm-alici-alt { display: flex; align-items: center; justify-content: space-between;
                gap: 16px; flex-wrap: wrap; margin-top: 14px; }
.sm-rapor-saat { display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
                 margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
.sm-rapor-saat .form-label { margin: 0; font-size: 13px; font-weight: 600; }
.sm-hata { margin-top: 6px; font-size: 12px; color: var(--danger, #9C2B2B); }
</style>

<script>
/* Haftalık toplam anında güncellensin — kaydetmeden önce "bu marka haftada
   kaç paylaşım alıyor" sorusunun cevabı ekranda görünür olsun diye. */
document.querySelectorAll('.sm-plan-tablo tbody tr').forEach(function (satir) {
    var kutular = satir.querySelectorAll('.sm-plan-input');
    var toplam  = satir.querySelector('.sm-hafta-toplam');
    if (!toplam) return;

    function hesapla() {
        var t = 0;
        kutular.forEach(function (k) { t += parseInt(k.value, 10) || 0; });
        toplam.textContent = t;
    }
    kutular.forEach(function (k) { k.addEventListener('input', hesapla); });
});
</script>


{{-- Panelin kendi takvim ekranindaki FullCalendar surumu --}}
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}?v={{ @filemtime(public_path('vendor/fullcalendar/index.global.min.js')) ?: 1 }}"></script>
<script>
/* Katlanabilir bolumler: takvim one ciksin, sablon/istisna istege bagli acilsin */
(function () {
    [['smSablonAc','smSablonKutu','Haftalık Şablon'],
     ['smRaporAc','smRaporKutu','Rapor Ayarları'],
     ['smIstisnaAc','smIstisnaKutu','İstisnalar']].forEach(function (p) {
        var b = document.getElementById(p[0]), k = document.getElementById(p[1]);
        if (!b || !k) return;
        b.addEventListener('click', function () {
            k.hidden = !k.hidden;
            b.querySelector('span').textContent = k.hidden ? p[2] : 'Kapat';
            if (!k.hidden) k.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();

/* Takvim — panelin takvim ekraniyla ayni ayarlar (locale tr, pazartesi basi) */
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('smTakvim');
    if (!el || typeof FullCalendar === 'undefined') return;

    var TAKIP_URL = "{{ route('admin.crm.sosyal-medya-takip.index') }}";

    var takvim = new FullCalendar.Calendar(el, {
        locale: 'tr',
        initialView: 'dayGridMonth',
        firstDay: 1,
        height: 'auto',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
        buttonText: { today: 'Bugün', month: 'Ay', list: 'Liste' },
        dayMaxEvents: 4,
        events: "{{ route('admin.crm.sosyal-medya-takip.etkinlikler') }}",

        /* Bos gune tiklayinca: o gunun takibine git. Ayrica ekleme formunun
           tarihini de o gune ayarla ki hemen paylasim eklenebilsin. */
        dateClick: function (info) {
            var t = document.getElementById('smKalemTarih');
            if (t) t.value = info.dateStr;
            window.location.href = TAKIP_URL + '?tarih=' + info.dateStr;
        },

        /* Paylasima tiklayinca da ayni gunun takibine git */
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            var g = info.event.extendedProps.tarih || info.event.startStr;
            window.location.href = TAKIP_URL + '?tarih=' + g;
        },
    });

    takvim.render();
});
</script>

@endsection
