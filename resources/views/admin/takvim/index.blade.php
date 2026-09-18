@extends('admin._layout')

@section('title', 'Takvim')

@push('head')
{{-- FullCalendar v6 global derlemesi CSS'i JS içinden enjekte eder; ayrı CSS gerekmez.
     CDN yerine yerel servis ediliyor (Tracking Prevention engellemesin diye). --}}
<style>
    /* FullCalendar admin temasına uyum */
    #takvim { background: var(--card, var(--surface)); border-radius: 16px; padding: 18px; }
    .fc { --fc-border-color: var(--border, rgba(0,0,0,.08)); --fc-today-bg-color: rgba(184,182,46,.10); }
    .fc .fc-button-primary { background: var(--brand, var(--brand)); border-color: var(--brand, var(--brand)); color: var(--text); font-weight:600; text-transform:capitalize; box-shadow:none; }
    .fc .fc-button-primary:hover { background: #a3a129; border-color: #a3a129; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background: var(--brand-hover); border-color: var(--brand-hover); }
    .fc .fc-toolbar-title { font-size:1.25rem; font-weight:700; }
    .fc-event { cursor:pointer; border: none; padding:1px 4px; font-size:12px; }
    .fc-daygrid-event { white-space:normal; }
    body.theme-dark #takvim { background: #141414; }
    body.theme-dark .fc { color: var(--text-inverse); --fc-border-color:rgba(184,182,46,.2); --fc-page-bg-color:#141414; }
    body.theme-dark .fc-col-header-cell-cushion, body.theme-dark .fc-daygrid-day-number, body.theme-dark .fc-list-day-text, body.theme-dark .fc-list-event-title { color: var(--text-inverse); }

    /* Modal */
    .tk-modal-bg { position:fixed; inset:0; background: rgba(0,0,0,.6); backdrop-filter:blur(6px); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
    .tk-modal-bg.show { display:flex; }
    .tk-modal { background: var(--card, var(--surface)); color: var(--text, var(--text)); width:100%; max-width:520px; border-radius: 18px; border: 1px solid var(--border, rgba(0,0,0,.1)); max-height:92vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.35); }
    body.theme-dark .tk-modal { background: #0f0f0f; color: var(--text-inverse); border-color: rgba(184,182,46,.3); }
    .tk-modal-head { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom: 1px solid var(--border, rgba(0,0,0,.08)); }
    .tk-modal-head h3 { margin:0; font-size:18px; font-weight:700; }
    .tk-modal-body { padding:20px 22px; }
    .tk-modal-foot { display:flex; gap:10px; justify-content:flex-end; padding:16px 22px; border-top: 1px solid var(--border, rgba(0,0,0,.08)); }
    .tk-field { margin-bottom:14px; }
    .tk-field label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
    .tk-field input[type=text], .tk-field input[type=datetime-local], .tk-field textarea, .tk-field select {
        width:100%; padding:10px 12px; border-radius: 10px; border: 1px solid var(--border, rgba(0,0,0,.15));
        background: var(--input-bg, var(--bg-subtle)); color: var(--text, var(--text)); font-family:inherit; font-size:14px; box-sizing:border-box;
    }
    body.theme-dark .tk-field input, body.theme-dark .tk-field textarea, body.theme-dark .tk-field select { background: rgba(255,255,255,.06); color: var(--text-inverse); border-color: rgba(184,182,46,.25); }.tk-field textarea{min-height:80px; resize:vertical;background: var(--surface);color: var(--text);}
    .tk-row { display:flex; gap:12px; } .tk-row .tk-field { flex:1; }
    .tk-check { display:flex; align-items:center; gap:8px; }.tk-check input{width:18px; height:18px;background: var(--surface);color: var(--text);}
    .tk-close { background: none; border: none; cursor:pointer; font-size:22px; line-height:1; color: inherit; opacity:.6; }
    .tk-close:hover { opacity:1; }
    .tk-renkler { display:flex; gap:8px; flex-wrap:wrap; }
    .tk-renk { width:30px; height:30px; border-radius: 50%; cursor:pointer; border: 3px solid transparent; }
    .tk-renk.sel { border-color: var(--text, #111); }

    /* ═══ Özel Takvim Filtre Dropdown ═══ */
    .tk-dd { position:relative; min-width:230px; font-family:inherit; }
    .tk-dd-trigger {
        display:flex; align-items:center; gap:10px; width:100%;
        padding:9px 12px; border-radius: 12px; cursor:pointer;
        background: var(--card, var(--surface)); color: var(--text, var(--text));
        border: 1.5px solid var(--border, rgba(0,0,0,.12));
        font-size:14px; font-weight:600; transition:border-color .15s, box-shadow .15s, background .15s;
    }
    .tk-dd-trigger:hover { border-color: var(--brand); }
    .tk-dd.open .tk-dd-trigger { border-color: var(--brand); box-shadow:0 0 0 3px rgba(184,182,46,.18); }
    .tk-dd-ava {
        flex:0 0 auto; width:28px; height:28px; border-radius: 50%;
        display:flex; align-items:center; justify-content:center;
        background: linear-gradient(135deg,var(--brand),var(--brand-hover)); color: var(--text-inverse);
        font-size:13px; font-weight:700; line-height:1;
    }
    .tk-dd-label { flex:1 1 auto; text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .tk-dd-caret { width:18px; height:18px; opacity:.55; transition:transform .2s; flex:0 0 auto; }
    .tk-dd.open .tk-dd-caret { transform:rotate(180deg); }

    .tk-dd-menu {
        position:absolute; top:calc(100% + 8px); right:0; left:0; z-index:1200;
        background: var(--card, var(--surface)); border: 1px solid var(--border, rgba(0,0,0,.1));
        border-radius: 14px; box-shadow:0 18px 48px rgba(0,0,0,.18);
        padding:8px; display:none; opacity:0; transform:translateY(-6px);
        transition:opacity .16s ease, transform .16s ease;
    }
    .tk-dd.open .tk-dd-menu { display:block; opacity:1; transform:translateY(0); }

    .tk-dd-search { position:relative; margin-bottom:6px; }
    .tk-dd-search i { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:16px; height:16px; opacity:.5; pointer-events:none; }
    .tk-dd-search input {
        width:100%; box-sizing:border-box; padding:8px 10px 8px 32px;
        border-radius: 9px; border: 1px solid var(--border, rgba(0,0,0,.12));
        background: var(--input-bg, var(--bg-subtle)); color: var(--text, var(--text)); font-size:13px; font-family:inherit;
    }
    .tk-dd-search input:focus { outline: none; border-color: var(--brand); }

    .tk-dd-list { max-height:280px; overflow-y:auto; }
    .tk-dd-opt {
        display:flex; align-items:center; gap:10px; padding:8px 10px;
        border-radius: 9px; cursor:pointer; transition:background .12s;
    }
    .tk-dd-opt:hover { background: rgba(184,182,46,.12); }
    .tk-dd-opt.selected { background: rgba(184,182,46,.16); }
    .tk-dd-opt-ava {
        flex:0 0 auto; width:30px; height:30px; border-radius: 50%;
        display:flex; align-items:center; justify-content:center;
        background: rgba(184,182,46,.18); color: #7a7a16; font-size:13px; font-weight:700; line-height:1;
    }
    .tk-dd-opt-ava.all { background: linear-gradient(135deg,var(--brand),var(--brand-hover)); color: var(--text-inverse); }
    .tk-dd-opt-txt { flex:1 1 auto; font-size:14px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color: var(--text,var(--text)); }
    .tk-dd-opt-check { width:18px; height:18px; color: var(--brand); opacity:0; flex:0 0 auto; }
    .tk-dd-opt.selected .tk-dd-opt-check { opacity:1; }
    .tk-dd-empty { padding:14px 10px; text-align:center; font-size:13px; opacity:.55; }

    /* Dark tema */
    body.theme-dark .tk-dd-trigger { background: #141414; color: #f1f1f1; border-color: rgba(184,182,46,.28); }
    body.theme-dark .tk-dd-menu { background: #0f0f0f; border-color: rgba(184,182,46,.3); box-shadow:0 18px 48px rgba(0,0,0,.55); }
    body.theme-dark .tk-dd-search input { background: rgba(255,255,255,.06); color: var(--text-inverse); border-color: rgba(184,182,46,.25); }
    body.theme-dark .tk-dd-opt:hover { background: rgba(184,182,46,.18); }
    body.theme-dark .tk-dd-opt.selected { background: rgba(184,182,46,.22); }
    body.theme-dark .tk-dd-opt-txt { color: #f1f1f1; }
    body.theme-dark .tk-dd-opt-ava { color: #d9d873; }

    @media (max-width:560px){ .tk-dd { min-width:0; flex:1 1 auto; } }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Takvim</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">📅 Takvim</h1>
        <div class="page-subtitle">
            @if($herkesiGorur)
                Tüm yöneticilerin takvimini görüntülüyorsunuz — kişiye göre filtreleyebilirsiniz
            @else
                Kişisel takviminiz — etkinlik, hatırlatma ve önemli tarihler
            @endif
        </div>
    </div>
    <div class="page-actions" style="display:flex;gap:10px;align-items:center">
        @if($herkesiGorur)
        {{-- Özel takvim filtre dropdown'ı (native select yerine marka temalı) --}}
        <div class="tk-dd" id="tkDd">
            <input type="hidden" id="tkFiltre" value="">
            <button type="button" class="tk-dd-trigger" id="tkDdTrigger" onclick="tkDdToggle()" aria-haspopup="listbox" aria-expanded="false">
                <span class="tk-dd-ava" id="tkDdAva">👥</span>
                <span class="tk-dd-label" id="tkDdLabel">Herkesin Takvimi</span>
                <i data-lucide="chevron-down" class="tk-dd-caret"></i>
            </button>
            <div class="tk-dd-menu" id="tkDdMenu" role="listbox">
                <div class="tk-dd-search">
                    <i data-lucide="search"></i>
                    <input type="text" id="tkDdSearch" placeholder="Yönetici ara..." oninput="tkDdAra(this.value)" autocomplete="off">
                </div>
                <div class="tk-dd-list" id="tkDdList">
                    <div class="tk-dd-opt selected" data-val="" data-ava="👥" data-label="Herkesin Takvimi" onclick="tkDdSec(this)">
                        <span class="tk-dd-opt-ava all">👥</span>
                        <span class="tk-dd-opt-txt">Herkesin Takvimi</span>
                        <i data-lucide="check" class="tk-dd-opt-check"></i>
                    </div>
                    @foreach($kullanicilar as $k)
                        @php
                            $kAd = $k->adi ?: $k->kullaniciadi;
                            $bas = mb_substr(trim($kAd), 0, 1, 'UTF-8');
                            $bas = $bas !== '' ? mb_strtoupper($bas, 'UTF-8') : '?';
                        @endphp
                        <div class="tk-dd-opt" data-val="{{ $k->id }}" data-ava="{{ $bas }}" data-label="{{ $kAd }}" onclick="tkDdSec(this)">
                            <span class="tk-dd-opt-ava">{{ $bas }}</span>
                            <span class="tk-dd-opt-txt">{{ $kAd }}</span>
                            <i data-lucide="check" class="tk-dd-opt-check"></i>
                        </div>
                    @endforeach
                    <div class="tk-dd-empty" id="tkDdEmpty" style="display:none">Sonuç yok</div>
                </div>
            </div>
        </div>
        @endif
        <button type="button" class="btn btn-primary" onclick="tkYeni()">
            <i data-lucide="plus"></i>
            <span>Yeni Etkinlik</span>
        </button>
    </div>
</div>

<div id="takvim"></div>

{{-- ═══ MODAL ═══ --}}
<div class="tk-modal-bg" id="tkModalBg">
    <div class="tk-modal">
        <div class="tk-modal-head">
            <h3 id="tkModalTitle">Yeni Etkinlik</h3>
            <button type="button" class="tk-close" onclick="tkKapat()">&times;</button>
        </div>
        <form id="tkForm">
            <div class="tk-modal-body">
                <input type="hidden" id="tk_id">
                <div class="tk-field">
                    <label>Başlık *</label>
                    <input type="text" id="tk_baslik" required maxlength="255" placeholder="Örn. Muhasebe toplantısı">
                </div>
                <div class="tk-row">
                    <div class="tk-field">
                        <label>Başlangıç *</label>
                        <input type="datetime-local" id="tk_baslangic" required>
                    </div>
                    <div class="tk-field">
                        <label>Bitiş</label>
                        <input type="datetime-local" id="tk_bitis">
                    </div>
                </div>
                <div class="tk-field tk-check">
                    <input type="checkbox" id="tk_tumgun">
                    <label for="tk_tumgun" style="margin:0">Tüm gün</label>
                </div>
                <div class="tk-field">
                    <label>Açıklama</label>
                    <textarea id="tk_aciklama" placeholder="İsteğe bağlı not..."></textarea>
                </div>
                <div class="tk-field">
                    <label>Renk</label>
                    <div class="tk-renkler" id="tkRenkler"></div>
                    <input type="hidden" id="tk_renk" value="#b8b62e">
                </div>
                <div id="tk_meta" style="font-size:12px;opacity:.6;margin-top:4px"></div>
            </div>
            <div class="tk-modal-foot">
                <button type="button" class="btn btn-danger" id="tkSilBtn" style="margin-right:auto;display:none" onclick="tkSil()">
                    <i data-lucide="trash-2"></i><span>Sil</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="tkKapat()">İptal</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="check"></i><span>Kaydet</span></button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}?v={{ @filemtime(public_path('vendor/fullcalendar/index.global.min.js')) ?: 1 }}"></script>
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const URL_EVENTS = "{{ route('admin.takvim.events') }}";
    const URL_STORE  = "{{ route('admin.takvim.store') }}";
    const URL_BASE   = "{{ url('admin/takvim') }}";
    const RENKLER = ['#b8b62e','#3b82f6','#10b981','#ef4444','#f59e0b','#8b5cf6','#ec4899','#6b7280'];

    let calendar;

    // datetime-local için yerel ISO (yyyy-MM-ddTHH:mm)
    function toLocalInput(d) {
        if (!d) return '';
        const dt = (d instanceof Date) ? d : new Date(d);
        const pad = n => String(n).padStart(2, '0');
        return dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
    }

    function tkInit() {
        // FullCalendar yüklenememişse (örn. engellendiyse) görünür uyarı ver
        if (typeof FullCalendar === 'undefined' || !FullCalendar.Calendar) {
            document.getElementById('takvim').innerHTML =
                '<div style="padding:30px;text-align:center;color: var(--danger)">Takvim kütüphanesi yüklenemedi. Sayfayı sert yenileyin (Ctrl+Shift+R).</div>';
            return;
        }
        // Renk paleti oluştur
        const wrap = document.getElementById('tkRenkler');
        RENKLER.forEach(function (c) {
            const el = document.createElement('div');
            el.className = 'tk-renk'; el.style.background = c; el.dataset.renk = c;
            el.onclick = function () { tkSecRenk(c); };
            wrap.appendChild(el);
        });
        tkSecRenk('#b8b62e');

        calendar = new FullCalendar.Calendar(document.getElementById('takvim'), {
            locale: 'tr',
            initialView: 'dayGridMonth',
            firstDay: 1,
            height: 'auto',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
            buttonText: { today: 'Bugün', month: 'Ay', week: 'Hafta', day: 'Gün', list: 'Liste' },
            editable: true,
            selectable: true,
            dayMaxEvents: true,
            events: function (info, successCallback, failureCallback) {
                const f = document.getElementById('tkFiltre');
                const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
                if (f && f.value) params.append('kullanici', f.value);
                fetch(URL_EVENTS + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json()).then(successCallback).catch(failureCallback);
            },
            select: function (info) {
                tkYeni();
                document.getElementById('tk_baslangic').value = toLocalInput(info.start);
                document.getElementById('tk_bitis').value = info.end ? toLocalInput(info.end) : '';
                document.getElementById('tk_tumgun').checked = info.allDay;
            },
            eventClick: function (info) { tkDuzenle(info.event); },
            eventDrop: function (info) { tkTasi(info.event); },
            eventResize: function (info) { tkTasi(info.event); },
        });
        calendar.render();

        // Dropdown ikonlari (chevron, search, check) cizilsin
        if (window.lucide) window.lucide.createIcons();
    }

    // DOM hazırsa hemen, değilse hazır olunca başlat
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tkInit);
    } else {
        tkInit();
    }

    function tkSecRenk(c) {
        document.getElementById('tk_renk').value = c;
        document.querySelectorAll('.tk-renk').forEach(function (el) {
            el.classList.toggle('sel', el.dataset.renk === c);
        });
    }

    window.tkYeni = function () {
        document.getElementById('tkForm').reset();
        document.getElementById('tk_id').value = '';
        document.getElementById('tkModalTitle').textContent = 'Yeni Etkinlik';
        document.getElementById('tkSilBtn').style.display = 'none';
        document.getElementById('tk_meta').textContent = '';
        tkSaltOkunur(false); // yeni eklemede alanlar acik olmali
        tkSecRenk('#b8b62e');
        const now = new Date(); now.setMinutes(0);
        document.getElementById('tk_baslangic').value = toLocalInput(now);
        tkAc();
    };

    window.tkDuzenle = function (ev) {
        document.getElementById('tkForm').reset();
        const benimMi = ev.extendedProps.benim_mi !== false; // tanimsizsa (kendi takvimi)
        // Yonetebilir mi: backend 'yonetebilir' bayragi gonderir (kendi etkinligi VEYA yetkili rol).
        // Eski feed'den (bayrak yoksa) geriye donuk: benimMi'ye dus.
        const yonetebilir = (ev.extendedProps.yonetebilir === true) ||
                            (ev.extendedProps.yonetebilir === undefined && benimMi);
        document.getElementById('tk_id').value = ev.id;

        // Baslikta "Isim • " oneki varsa goruntu icin temizle (sadece baskasininkinde eklenmis olur)
        let gosterilenBaslik = ev.title;
        if (!benimMi && ev.extendedProps.olusturan_adi) {
            const onek = ev.extendedProps.olusturan_adi + ' • ';
            if (gosterilenBaslik.indexOf(onek) === 0) {
                gosterilenBaslik = gosterilenBaslik.slice(onek.length);
            }
        }

        document.getElementById('tkModalTitle').textContent = yonetebilir ? 'Etkinliği Düzenle' : 'Etkinlik (Görüntüleme)';
        document.getElementById('tk_baslik').value = gosterilenBaslik;
        document.getElementById('tk_baslangic').value = toLocalInput(ev.start);
        document.getElementById('tk_bitis').value = ev.end ? toLocalInput(ev.end) : '';
        document.getElementById('tk_tumgun').checked = ev.allDay;
        document.getElementById('tk_aciklama').value = ev.extendedProps.aciklama || '';
        tkSecRenk(ev.backgroundColor || '#b8b62e');

        // Yetki: yonetemiyorsa alanlari kilitle, Sil/Kaydet gizle
        tkSaltOkunur(!yonetebilir);

        document.getElementById('tk_meta').textContent = ev.extendedProps.olusturan_adi ? ('Ekleyen: ' + ev.extendedProps.olusturan_adi) : '';
        tkAc();
    };

    // Modal alanlarini kilitle/ac (baskasinin etkinligi = salt okunur)
    function tkSaltOkunur(kilitli) {
        ['tk_baslik','tk_baslangic','tk_bitis','tk_tumgun','tk_aciklama'].forEach(function(idAdi){
            const el = document.getElementById(idAdi);
            if (el) el.disabled = kilitli;
        });
        document.querySelectorAll('.tk-renk').forEach(function(el){ el.style.pointerEvents = kilitli ? 'none' : ''; el.style.opacity = kilitli ? '.5' : ''; });
        document.getElementById('tkSilBtn').style.display = kilitli ? 'none' : 'inline-flex';
        const kaydetBtn = document.querySelector('#tkForm button[type=submit]');
        if (kaydetBtn) kaydetBtn.style.display = kilitli ? 'none' : 'inline-flex';
    }

    function tkAc() {
        document.getElementById('tkModalBg').classList.add('show');
        if (window.lucide) window.lucide.createIcons();
    }
    window.tkKapat = function () { document.getElementById('tkModalBg').classList.remove('show'); };

    window.tkFiltrele = function () { calendar.refetchEvents(); };

    /* ═══ Özel dropdown davranışı ═══ */
    window.tkDdToggle = function () {
        const dd = document.getElementById('tkDd');
        if (!dd) return;
        const acik = dd.classList.toggle('open');
        document.getElementById('tkDdTrigger').setAttribute('aria-expanded', acik ? 'true' : 'false');
        if (acik) {
            const s = document.getElementById('tkDdSearch');
            if (s) { s.value=''; tkDdAra(''); setTimeout(function(){ s.focus(); }, 30); }
        }
    };

    function tkDdKapat() {
        const dd = document.getElementById('tkDd');
        if (dd) { dd.classList.remove('open'); document.getElementById('tkDdTrigger').setAttribute('aria-expanded','false'); }
    }

    window.tkDdSec = function (el) {
        const val   = el.getAttribute('data-val') || '';
        const ava   = el.getAttribute('data-ava') || '👥';
        const label = el.getAttribute('data-label') || 'Herkesin Takvimi';

        document.getElementById('tkFiltre').value = val;
        document.getElementById('tkDdAva').textContent = ava;
        document.getElementById('tkDdLabel').textContent = label;

        document.querySelectorAll('#tkDdList .tk-dd-opt').forEach(function (o) { o.classList.remove('selected'); });
        el.classList.add('selected');

        tkDdKapat();
        tkFiltrele();
    };

    window.tkDdAra = function (q) {
        q = (q || '').toLocaleLowerCase('tr');
        let gorunen = 0;
        document.querySelectorAll('#tkDdList .tk-dd-opt').forEach(function (o) {
            const txt = (o.getAttribute('data-label') || '').toLocaleLowerCase('tr');
            const esles = txt.indexOf(q) !== -1;
            o.style.display = esles ? '' : 'none';
            if (esles) gorunen++;
        });
        const bos = document.getElementById('tkDdEmpty');
        if (bos) bos.style.display = gorunen === 0 ? 'block' : 'none';
    };

    // Dısarı tıklayınca / ESC ile kapan
    document.addEventListener('click', function (e) {
        const dd = document.getElementById('tkDd');
        if (dd && !dd.contains(e.target)) tkDdKapat();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tkDdKapat();
    });

    document.getElementById('tkModalBg').addEventListener('click', function (e) {
        if (e.target === this) tkKapat();
    });

    document.getElementById('tkForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('tk_id').value;
        const url = id ? (URL_BASE + '/' + id + '/guncelle') : URL_STORE;
        const body = new URLSearchParams();
        body.append('baslik', document.getElementById('tk_baslik').value);
        body.append('baslangic', document.getElementById('tk_baslangic').value);
        body.append('bitis', document.getElementById('tk_bitis').value);
        body.append('tum_gun', document.getElementById('tk_tumgun').checked ? 1 : 0);
        body.append('aciklama', document.getElementById('tk_aciklama').value);
        body.append('renk', document.getElementById('tk_renk').value);

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body })
            .then(r => r.json().catch(() => ({})).then(j => ({ ok: r.ok, j })))
            .then(({ ok }) => {
                if (!ok) { alert('Kaydedilemedi. Alanları kontrol edin.'); return; }
                tkKapat(); calendar.refetchEvents();
            })
            .catch(() => alert('Bir hata oluştu.'));
    });

    window.tkSil = function () {
        const id = document.getElementById('tk_id').value;
        if (!id || !confirm('Bu etkinlik silinsin mi?')) return;
        fetch(URL_BASE + '/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
            .then(r => { if (r.ok) { tkKapat(); calendar.refetchEvents(); } else alert('Silinemedi.'); });
    };

    function tkTasi(ev) {
        const body = new URLSearchParams();
        body.append('baslangic', toLocalInput(ev.start));
        body.append('bitis', ev.end ? toLocalInput(ev.end) : '');
        body.append('tum_gun', ev.allDay ? 1 : 0);
        fetch(URL_BASE + '/' + ev.id + '/tasi', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body })
            .then(r => { if (!r.ok) calendar.refetchEvents(); });
    }
})();
</script>
@endpush