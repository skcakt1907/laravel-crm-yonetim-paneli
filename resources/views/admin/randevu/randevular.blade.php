@extends('admin._layout')

@section('title', 'Randevular')

@push('head')
<style>
    /* ─── Randevu takvimi ─── */
    .rdv-toolbar { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:16px; }
    .rdv-add-btn { background:var(--brand); color:#000; border:none; font-weight:700; padding:12px 22px; border-radius:10px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:14px; }
    .rdv-add-btn:hover { background:var(--brand-hover); }
    .rdv-nav { display:flex; align-items:center; gap:6px; }
    .rdv-nav button { background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); width:38px; height:38px; border-radius:9px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; color:var(--text,#111); }
    .rdv-nav button:hover { border-color:var(--brand); color:var(--brand); }
    .rdv-title { font-size:18px; font-weight:700; min-width:170px; text-align:center; color:var(--text,#111); }
    .rdv-views { display:flex; gap:0; margin-left:auto; border:1px solid var(--brand); border-radius:9px; overflow:hidden; }
    .rdv-views button { background:transparent; border:none; padding:9px 16px; cursor:pointer; font-weight:600; color:var(--brand); font-size:13px; border-right:1px solid var(--brand-medium); }
    .rdv-views button:last-child { border-right:none; }
    .rdv-views button.active { background:var(--brand-soft); }

    .rdv-cal-wrap { border:1px solid var(--border,#e5e7eb); border-radius:14px; overflow:auto; max-height:calc(100vh - 230px); background:var(--surface,#fff); }
    .rdv-head { display:flex; position:sticky; top:0; z-index:5; background:var(--surface,#fff); border-bottom:2px solid var(--border,#e5e7eb); }
    .rdv-head-time { flex:0 0 64px; }
    .rdv-head-staff { flex:1 1 0; min-width:150px; padding:12px 8px; display:flex; align-items:center; gap:8px; justify-content:center; border-left:1px solid var(--border,#eee); font-weight:600; color:var(--text,#111); font-size:14px; }
    .rdv-ava { width:34px; height:34px; border-radius:50%; object-fit:cover; flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; }
    .rdv-body { display:flex; }
    .rdv-axis { flex:0 0 64px; }
    .rdv-axis-h { box-sizing:border-box; padding:2px 8px 0 0; text-align:right; font-size:12px; color:var(--text-muted,#9ca3af); border-bottom:1px solid var(--border,#f0f0f0); }
    .rdv-col { flex:1 1 0; min-width:150px; position:relative; border-left:1px solid var(--border,#eee); }
    .rdv-slot { box-sizing:border-box; border-bottom:1px dashed var(--border,#f2f2f2); cursor:pointer; }
    .rdv-slot:nth-child(2n) { border-bottom:1px solid var(--border,#ededed); }
    .rdv-slot:hover { background:var(--brand-soft); }
    .rdv-event { position:absolute; left:3px; right:3px; border-radius:7px; padding:4px 7px; color:#fff; font-size:12px; overflow:hidden; cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,.15); z-index:2; }
    .rdv-event b { display:block; font-weight:700; }
    .rdv-event small { opacity:.9; }
    .rdv-event.iptal { text-decoration:line-through; opacity:.7; }

    #rdvFC { padding:14px; }
    .fc .fc-button-primary { background:var(--brand); border-color:var(--brand); color:#000; }
    .fc .fc-button-primary:hover { background:var(--brand-hover); border-color:var(--brand-hover); color:#000; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background:#8a8a1f; border-color:#8a8a1f; color:#fff; }

    /* Modal */
    .rdv-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.55); backdrop-filter:blur(4px); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
    .rdv-modal-bg.show { display:flex; }
    .rdv-modal { background:var(--surface,#fff); color:var(--text,#111); width:100%; max-width:480px; border-radius:16px; border:1px solid var(--border,#e5e7eb); max-height:92vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.35); }
    .rdv-modal-head { padding:18px 20px; border-bottom:1px solid var(--border,#eee); display:flex; align-items:center; justify-content:space-between; }
    .rdv-modal-head h3 { margin:0; font-size:17px; font-weight:700; }
    .rdv-modal-body { padding:18px 20px; display:grid; gap:12px; }
    .rdv-row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .rdv-field label { display:block; font-size:12px; font-weight:600; color:var(--text-muted,#6b7280); margin-bottom:4px; }
    .rdv-field input, .rdv-field select, .rdv-field textarea { width:100%; box-sizing:border-box; padding:9px 11px; border:1px solid var(--border,#d1d5db); border-radius:8px; background:var(--bg,#fff); color:var(--text,#111); font-size:14px; }
    .rdv-modal-foot { padding:14px 20px; border-top:1px solid var(--border,#eee); display:flex; gap:10px; justify-content:flex-end; }
    .rdv-btn { padding:10px 18px; border-radius:9px; border:none; font-weight:600; cursor:pointer; font-size:14px; }
    .rdv-btn-primary { background:var(--brand); color:#000; }
    .rdv-btn-ghost { background:transparent; border:1px solid var(--border,#d1d5db); color:var(--text,#111); }
    .rdv-btn-danger { background:#ef4444; color:#fff; margin-right:auto; }

    body.theme-dark .rdv-cal-wrap, body.theme-dark .rdv-head, body.theme-dark .rdv-modal { background:#1a1a1a; }
    body.theme-dark .rdv-slot:nth-child(2n) { border-color:#2a2a2a; }
</style>
@endpush

@section('content')
<div class="rdv-toolbar">
    <button class="rdv-add-btn" onclick="rdvAdd()"><i data-lucide="plus"></i> Randevu Ekle</button>
    <div class="rdv-nav">
        <button onclick="rdvNav(-1)" title="Önceki"><i data-lucide="chevron-left"></i></button>
        <button onclick="rdvNav(1)" title="Sonraki"><i data-lucide="chevron-right"></i></button>
        <button onclick="rdvToday()" title="Bugün"><i data-lucide="calendar"></i></button>
    </div>
    <div class="rdv-title" id="rdvTitle">—</div>
    <div class="rdv-views">
        <button data-view="gun" class="active" onclick="rdvSetView('gun')">Gün</button>
        <button data-view="dayGridMonth" onclick="rdvSetView('dayGridMonth')">Ay</button>
        <button data-view="timeGridWeek" onclick="rdvSetView('timeGridWeek')">Hafta</button>
        <button data-view="listWeek" onclick="rdvSetView('listWeek')">Liste</button>
    </div>
</div>

{{-- Günlük çalışan-kolonlu ızgara (custom) --}}
<div class="rdv-cal-wrap" id="rdvGunWrap">
    <div class="rdv-head" id="rdvHead"></div>
    <div class="rdv-body" id="rdvBody"></div>
</div>

{{-- Ay/Hafta/Liste (FullCalendar) --}}
<div class="rdv-cal-wrap" id="rdvFCWrap" style="display:none">
    <div id="rdvFC"></div>
</div>

{{-- ─── Randevu modalı ─── --}}
<div class="rdv-modal-bg" id="rdvModalBg">
    <div class="rdv-modal">
        <div class="rdv-modal-head">
            <h3 id="rdvModalTitle">Randevu Ekle</h3>
            <button class="rdv-btn rdv-btn-ghost" style="padding:4px 10px" onclick="rdvCloseModal()">✕</button>
        </div>
        <div class="rdv-modal-body">
            <input type="hidden" id="f_id">
            <div class="rdv-field">
                <label>Müşteri *
                    <a href="javascript:void(0)" onclick="rdvMusteriMod()" id="f_musteri_mod_link" style="font-size:12px;font-weight:500;margin-left:6px;color:#8a8718;text-decoration:none">+ Yeni müşteri</a>
                </label>
                <select id="f_crm_musteri_id" onchange="rdvMusteriChange()"></select>
                {{-- Manuel müşteri girişi: listede olmayan müşteriyi doğrudan yaz --}}
                <input type="text" id="f_musteri_ad_manuel" placeholder="Yeni müşteri adı / firma" style="display:none">
            </div>
            <div class="rdv-field">
                <label>Telefon</label>
                <input type="text" id="f_musteri_tel" placeholder="05xx...">
            </div>
            <div class="rdv-row2">
                <div class="rdv-field">
                    <label>Çalışan *</label>
                    <select id="f_calisan_id"></select>
                </div>
                <div class="rdv-field">
                    <label>Lokasyon</label>
                    <select id="f_hizmet_id" onchange="rdvHizmetChange()"></select>
                </div>
            </div>
            <div class="rdv-row2">
                <div class="rdv-field">
                    <label>Tarih *</label>
                    <input type="date" id="f_tarih">
                </div>
                <div class="rdv-field">
                    <label>Saat *</label>
                    <input type="time" id="f_saat">
                </div>
            </div>
            <div class="rdv-field">
                <label>Süre (dk)</label>
                <input type="number" id="f_sure_dk" min="5" step="5" value="30">
            </div>
            <div class="rdv-field" id="f_olusturan_wrap" style="display:none">
                <label>Oluşturan</label>
                <div id="f_olusturan" style="font-size:13px;color:var(--text-muted);padding:6px 0"></div>
            </div>
            <div class="rdv-field">
                <label>Durum</label>
                <select id="f_durum">
                    <option value="beklemede">Beklemede</option>
                    <option value="onaylandi">Onaylandı</option>
                    <option value="geldi">Geldi</option>
                    <option value="iptal">İptal</option>
                </select>
            </div>
            <div class="rdv-field">
                <label>Not <small style="font-weight:500;color:var(--text-muted)">(müşteriye SMS olarak gider)</small></label>
                <textarea id="f_notlar" rows="2" placeholder="Bu not kayıtta müşteriye SMS ile gönderilir"></textarea>
            </div>
            <div class="rdv-field">
                <label>Özel Not <small style="font-weight:500;color:var(--text-muted)">(yalnızca dahili — gönderilmez)</small></label>
                <textarea id="f_ozel_not" rows="2" placeholder="Sadece panelde görünür, SMS/mail ile gönderilmez"></textarea>
            </div>
        </div>
        <div class="rdv-modal-foot">
            <button class="rdv-btn rdv-btn-danger" id="rdvDeleteBtn" style="display:none" onclick="rdvDelete()">Sil</button>
            <button class="rdv-btn rdv-btn-ghost" onclick="rdvCloseModal()">Vazgeç</button>
            <button class="rdv-btn rdv-btn-primary" onclick="rdvSave()">Kaydet</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}?v={{ @filemtime(public_path('vendor/fullcalendar/index.global.min.js')) }}"></script>
<script>
const RDV = {
    tarih: '{{ $tarih->format('Y-m-d') }}',
    calisanlar: @json($calisanlar),
    hizmetler: @json($hizmetler),
    crmMusteriler: @json($crmMusteriler ?? []),
    csrf: '{{ csrf_token() }}',
    urls: {
        events: '{{ route('admin.randevu.events') }}',
        kaydet: '{{ route('admin.randevu.kaydet') }}',
        tasi:   '{{ route('admin.randevu.tasi', ['id'=>'__ID__']) }}',
        sil:    '{{ route('admin.randevu.sil', ['id'=>'__ID__']) }}',
    },
    // Izgara ayarları
    START_HOUR: 8, END_HOUR: 24, SLOT_MIN: 30, PX_PER_MIN: 1.4,
    view: 'gun', fc: null,
};

/* ── yardımcılar ── */
function rdvPad(n){ return String(n).padStart(2,'0'); }
function rdvAvatar(c){
    const initials = (c.ad||'?').trim().split(/\s+/).map(s=>s[0]).slice(0,2).join('').toUpperCase();
    if (c.foto) return `<img class="rdv-ava" src="${c.foto}" alt="">`;
    return `<span class="rdv-ava" style="background:${c.renk||'var(--brand)'}">${initials}</span>`;
}
function rdvTrTarih(d){
    const aylar=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    const gunler=['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
    return d.getDate()+' '+aylar[d.getMonth()]+' '+d.getFullYear()+' '+gunler[d.getDay()];
}

/* ── günlük ızgara ── */
function rdvRenderGun(){
    const head = document.getElementById('rdvHead');
    const body = document.getElementById('rdvBody');
    const totalMin = (RDV.END_HOUR - RDV.START_HOUR) * 60;
    const gridH = totalMin * RDV.PX_PER_MIN;
    const slotH = RDV.SLOT_MIN * RDV.PX_PER_MIN;

    // Başlık
    let h = '<div class="rdv-head-time"></div>';
    RDV.calisanlar.forEach(c => {
        h += `<div class="rdv-head-staff">${rdvAvatar(c)}<span>${c.ad}</span></div>`;
    });
    head.innerHTML = h;

    // Gövde: saat ekseni + kolonlar
    let axis = '<div class="rdv-axis">';
    for (let hour=RDV.START_HOUR; hour<RDV.END_HOUR; hour++){
        axis += `<div class="rdv-axis-h" style="height:${60*RDV.PX_PER_MIN}px">${rdvPad(hour)}:00</div>`;
    }
    axis += '</div>';

    let cols = '';
    RDV.calisanlar.forEach(c => {
        let slots = '';
        for (let m=RDV.START_HOUR*60; m<RDV.END_HOUR*60; m+=RDV.SLOT_MIN){
            slots += `<div class="rdv-slot" style="height:${slotH}px" data-min="${m}" data-calisan="${c.id}"></div>`;
        }
        cols += `<div class="rdv-col" data-calisan="${c.id}" style="height:${gridH}px">${slots}</div>`;
    });
    body.innerHTML = axis + cols;

    // Slot tıkla → ekle
    body.querySelectorAll('.rdv-slot').forEach(s => {
        s.addEventListener('click', () => {
            const min = parseInt(s.dataset.min,10);
            rdvAdd({ calisan_id: s.dataset.calisan, tarih: RDV.tarih, saat: rdvPad(Math.floor(min/60))+':'+rdvPad(min%60) });
        });
    });

    if (window.lucide) lucide.createIcons();
    rdvLoadGunEvents();
}

function rdvLoadGunEvents(){
    fetch(`${RDV.urls.events}?start=${RDV.tarih}&end=${RDV.tarih}`)
        .then(r => r.json())
        .then(list => {
            document.querySelectorAll('.rdv-event').forEach(e => e.remove());
            list.forEach(ev => rdvPlaceEvent(ev));
        });
}

function rdvPlaceEvent(ev){
    const cid = ev.extendedProps.calisan_id;
    const col = document.querySelector(`.rdv-col[data-calisan="${cid}"]`);
    if (!col) return;
    const s = new Date(ev.start), e = new Date(ev.end);
    const startMin = s.getHours()*60 + s.getMinutes();
    const durMin = Math.max(20, (e - s)/60000);
    const top = (startMin - RDV.START_HOUR*60) * RDV.PX_PER_MIN;
    const h = durMin * RDV.PX_PER_MIN - 2;
    const div = document.createElement('div');
    div.className = 'rdv-event' + (ev.extendedProps.durum==='iptal' ? ' iptal' : '');
    div.style.top = top+'px';
    div.style.height = h+'px';
    div.style.background = ev.backgroundColor;
    div.innerHTML = `<b>${rdvPad(s.getHours())}:${rdvPad(s.getMinutes())} ${ev.extendedProps.musteri_ad}</b>`
                  + (ev.extendedProps.hizmet_ad ? `<small>${ev.extendedProps.hizmet_ad}</small>` : '');
    div.addEventListener('click', (e) => { e.stopPropagation(); rdvEdit(ev); });
    col.appendChild(div);
}

/* ── görünüm değiştir ── */
function rdvSetView(v){
    RDV.view = v;
    document.querySelectorAll('.rdv-views button').forEach(b => b.classList.toggle('active', b.dataset.view===v));
    const isGun = (v==='gun');
    document.getElementById('rdvGunWrap').style.display = isGun ? '' : 'none';
    document.getElementById('rdvFCWrap').style.display = isGun ? 'none' : '';
    if (isGun){ rdvUpdateTitle(); rdvRenderGun(); }
    else { rdvFcEnsure(); RDV.fc.changeView(v); rdvUpdateTitle(); }
}

function rdvFcEnsure(){
    if (RDV.fc) return;
    RDV.fc = new FullCalendar.Calendar(document.getElementById('rdvFC'), {
        initialView: 'dayGridMonth',
        initialDate: RDV.tarih,
        locale: 'tr', firstDay: 1, height: 'auto',
        headerToolbar: false,
        slotMinTime: rdvPad(RDV.START_HOUR)+':00:00',
        slotMaxTime: rdvPad(RDV.END_HOUR)+':00:00',
        buttonText: { today:'Bugün' },
        events: { url: RDV.urls.events },
        eventClick: function(info){
            info.jsEvent.preventDefault();
            rdvEdit({ id: info.event.id, start: info.event.start, end: info.event.end, backgroundColor: info.event.backgroundColor, extendedProps: info.event.extendedProps });
        },
        dateClick: function(info){
            const d = info.date;
            rdvAdd({ tarih: info.dateStr.substr(0,10), saat: (info.allDay ? '09:00' : rdvPad(d.getHours())+':'+rdvPad(d.getMinutes())) });
        },
    });
    RDV.fc.render();
}

function rdvUpdateTitle(){
    if (RDV.view==='gun'){
        document.getElementById('rdvTitle').textContent = rdvTrTarih(new Date(RDV.tarih+'T00:00:00'));
    } else if (RDV.fc){
        document.getElementById('rdvTitle').textContent = RDV.fc.view.title;
    }
}

/* ── tarih navigasyonu ── */
function rdvNav(delta){
    if (RDV.view==='gun'){
        const d = new Date(RDV.tarih+'T00:00:00');
        d.setDate(d.getDate()+delta);
        RDV.tarih = d.getFullYear()+'-'+rdvPad(d.getMonth()+1)+'-'+rdvPad(d.getDate());
        rdvUpdateTitle(); rdvLoadGunEvents();
    } else {
        delta<0 ? RDV.fc.prev() : RDV.fc.next();
        rdvUpdateTitle();
    }
}
function rdvToday(){
    const d = new Date();
    RDV.tarih = d.getFullYear()+'-'+rdvPad(d.getMonth()+1)+'-'+rdvPad(d.getDate());
    if (RDV.view==='gun'){ rdvUpdateTitle(); rdvLoadGunEvents(); }
    else { RDV.fc.today(); rdvUpdateTitle(); }
}

/* ── modal ── */
function rdvFillSelects(){
    const cs = document.getElementById('f_calisan_id');
    cs.innerHTML = RDV.calisanlar.map(c => `<option value="${c.id}">${c.ad}</option>`).join('');
    const hs = document.getElementById('f_hizmet_id');
    hs.innerHTML = '<option value="">— Lokasyon —</option>' + RDV.hizmetler.map(h =>
        `<option value="${h.id}" data-sure="${h.sure_dk}">${h.ad}</option>`).join('');
    const ms = document.getElementById('f_crm_musteri_id');
    ms.innerHTML = '<option value="">— Müşteri seç —</option>' + RDV.crmMusteriler.map(m =>
        `<option value="${m.id}" data-tel="${(m.gsm || m.telefon || '')}">${rdvEsc(m.adi)}</option>`).join('');
}
function rdvEsc(t){ return String(t ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function rdvMusteriChange(){
    const opt = document.getElementById('f_crm_musteri_id').selectedOptions[0];
    if (opt && opt.dataset.tel){ document.getElementById('f_musteri_tel').value = opt.dataset.tel; }
}
// Listeden seçme <-> manuel yeni müşteri girişi arası geçiş
function rdvMusteriMod(zorla){
    const sel  = document.getElementById('f_crm_musteri_id');
    const inp  = document.getElementById('f_musteri_ad_manuel');
    const link = document.getElementById('f_musteri_mod_link');
    // zorla === 'liste' -> listeye dön; 'manuel' -> manuel; boş -> tersine çevir
    const manuelOl = zorla ? (zorla === 'manuel') : (inp.style.display === 'none');
    if (manuelOl){
        sel.style.display = 'none'; sel.value = '';
        inp.style.display = ''; if(!zorla) inp.focus();
        link.textContent = '↩ Listeden seç';
    } else {
        sel.style.display = ''; inp.style.display = 'none'; inp.value = '';
        link.textContent = '+ Yeni müşteri';
    }
}
function rdvHizmetChange(){
    const opt = document.getElementById('f_hizmet_id').selectedOptions[0];
    if (opt && opt.dataset.sure){ document.getElementById('f_sure_dk').value = opt.dataset.sure; }
}
function rdvAdd(pre){
    pre = pre || {};
    rdvFillSelects();
    document.getElementById('rdvModalTitle').textContent = 'Randevu Ekle';
    document.getElementById('rdvDeleteBtn').style.display = 'none';
    document.getElementById('f_id').value = '';
    document.getElementById('f_crm_musteri_id').value = '';
    document.getElementById('f_musteri_tel').value = '';
    rdvMusteriMod('liste'); // her yeni randevuda listeden-seç moduna dön
    document.getElementById('f_olusturan_wrap').style.display = 'none';
    document.getElementById('f_calisan_id').value = pre.calisan_id || (RDV.calisanlar[0] ? RDV.calisanlar[0].id : '');
    document.getElementById('f_hizmet_id').value = '';
    document.getElementById('f_tarih').value = pre.tarih || RDV.tarih;
    document.getElementById('f_saat').value = pre.saat || '09:00';
    document.getElementById('f_sure_dk').value = 30;
    document.getElementById('f_durum').value = 'beklemede';
    document.getElementById('f_notlar').value = '';
    document.getElementById('f_ozel_not').value = '';
    document.getElementById('rdvModalBg').classList.add('show');
}
function rdvEdit(ev){
    rdvFillSelects();
    rdvMusteriMod('liste'); // düzenlemede mevcut müşteri dropdown'da gösterilir
    const p = ev.extendedProps || {};
    const s = new Date(ev.start);
    document.getElementById('rdvModalTitle').textContent = 'Randevu Düzenle';
    document.getElementById('rdvDeleteBtn').style.display = '';
    document.getElementById('f_id').value = ev.id;
    const ms = document.getElementById('f_crm_musteri_id');
    if (p.crm_musteri_id) {
        ms.value = p.crm_musteri_id;
    } else {
        // Eski kayıt: CRM ile eşleşmemiş, mevcut adı geçici seçenek olarak göster
        ms.insertAdjacentHTML('afterbegin', `<option value="" selected>${rdvEsc(p.musteri_ad || '—')}</option>`);
    }
    document.getElementById('f_musteri_tel').value = p.musteri_tel || '';
    const ow = document.getElementById('f_olusturan_wrap');
    if (p.olusturan) { document.getElementById('f_olusturan').textContent = p.olusturan; ow.style.display = ''; }
    else { ow.style.display = 'none'; }
    document.getElementById('f_calisan_id').value = p.calisan_id || '';
    document.getElementById('f_hizmet_id').value = p.hizmet_id || '';
    document.getElementById('f_tarih').value = s.getFullYear()+'-'+rdvPad(s.getMonth()+1)+'-'+rdvPad(s.getDate());
    document.getElementById('f_saat').value = rdvPad(s.getHours())+':'+rdvPad(s.getMinutes());
    const dur = ev.end ? Math.round((new Date(ev.end)-s)/60000) : 30;
    document.getElementById('f_sure_dk').value = dur;
    document.getElementById('f_durum').value = p.durum || 'beklemede';
    document.getElementById('f_notlar').value = p.notlar || '';
    document.getElementById('f_ozel_not').value = p.ozel_not || '';
    document.getElementById('rdvModalBg').classList.add('show');
}
function rdvCloseModal(){ document.getElementById('rdvModalBg').classList.remove('show'); }

function rdvRefresh(){
    if (RDV.view==='gun') rdvLoadGunEvents();
    else if (RDV.fc) RDV.fc.refetchEvents();
}

function rdvSave(){
    // Manuel müşteri modu: serbest ad girişi açıksa onu kullan
    const manuelInp = document.getElementById('f_musteri_ad_manuel');
    const manuelMod = manuelInp.style.display !== 'none';
    const payload = {
        id: document.getElementById('f_id').value,
        calisan_id: document.getElementById('f_calisan_id').value,
        hizmet_id: document.getElementById('f_hizmet_id').value,
        crm_musteri_id: manuelMod ? '' : document.getElementById('f_crm_musteri_id').value,
        musteri_ad: manuelMod
            ? manuelInp.value.trim()
            : (document.getElementById('f_crm_musteri_id').selectedOptions[0]?.textContent || '').trim(),
        musteri_tel: document.getElementById('f_musteri_tel').value.trim(),
        tarih: document.getElementById('f_tarih').value,
        saat: document.getElementById('f_saat').value,
        sure_dk: document.getElementById('f_sure_dk').value,
        durum: document.getElementById('f_durum').value,
        notlar: document.getElementById('f_notlar').value,
        ozel_not: document.getElementById('f_ozel_not').value,
    };
    if (!payload.crm_musteri_id && (!payload.musteri_ad || payload.musteri_ad === '—' || payload.musteri_ad === '— Müşteri seç —')){ alert('Müşteri seçin veya yeni müşteri adı girin'); return; }
    if (!payload.calisan_id){ alert('Çalışan seçin'); return; }
    fetch(RDV.urls.kaydet, {
        method:'POST',
        headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':RDV.csrf, 'Accept':'application/json' },
        body: JSON.stringify(payload),
    }).then(r => r.json()).then(res => {
        if (res.ok){ rdvCloseModal(); rdvRefresh(); }
        else alert(res.msg || 'Kayıt başarısız');
    }).catch(() => alert('Sunucu hatası'));
}
function rdvDelete(){
    const id = document.getElementById('f_id').value;
    if (!id || !confirm('Randevu silinsin mi?')) return;
    fetch(RDV.urls.sil.replace('__ID__', id), {
        method:'DELETE',
        headers:{ 'X-CSRF-TOKEN':RDV.csrf, 'Accept':'application/json' },
    }).then(r => r.json()).then(res => {
        if (res.ok){ rdvCloseModal(); rdvRefresh(); }
    });
}

document.getElementById('rdvModalBg').addEventListener('click', e => { if (e.target.id==='rdvModalBg') rdvCloseModal(); });

/* ── başlat ── */
(function rdvInit(){
    function go(){ rdvUpdateTitle(); rdvRenderGun(); }
    if (document.readyState !== 'loading') go();
    else document.addEventListener('DOMContentLoaded', go);
})();
</script>
@endpush