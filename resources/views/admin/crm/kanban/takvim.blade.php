@extends('admin._layout')

@section('title', ($board->adi ?? 'Pano') . ' — Takvim')

@push('head')
<style>
    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px;
    }

    .cal-header-cell {
        padding: 8px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--brand);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .cal-cell {
        background: var(--bg-elevated);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        min-height: 92px;
        padding: 8px;
        position: relative;
        overflow: hidden;
    }

    .cal-cell.other-month {
        opacity: 0.4;
    }

    .cal-cell.today {
        border-color: var(--brand);
        background: var(--brand-soft);
        box-shadow: 0 0 0 2px var(--brand-medium);
    }

    .cal-day-num {
        font-size: 12px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 4px;
    }

    .cal-cell.today .cal-day-num {
        color: var(--brand);
    }

    .cal-pill {
        display: block;
        font-size: 10px;
        font-weight: 600;
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    .cal-pill.overdue {
        box-shadow: 0 0 0 1px var(--danger);
        outline: 1px solid rgba(239,68,68,0.4);
    }

    /* Başlangıç–son tarih arası devam şeridi */
    .cal-bar {
        display: block;
        height: 5px;
        border-radius: 3px;
        margin-bottom: 3px;
        opacity: 0.55;
    }

    @media (max-width: 768px) {
        .cal-cell { min-height: 64px; padding: 4px; }
        .cal-day-num { font-size: 11px; }
        .cal-pill { font-size: 9px; padding: 1px 4px; }
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.index') }}">Kanban</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.show', $board->id) }}">{{ $board->adi ?? 'Pano' }}</a>
    <span class="sep">/</span>
    <span class="current">Takvim</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">📅 {{ $board->adi ?? 'Pano' }} — Takvim</h1>
        <div class="page-subtitle">▶ başlangıç · ⏰ son tarih · şerit = devam eden aralık</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.kanban.show', $board->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Panoya Dön</span>
        </a>
        <a href="{{ url('admin/crm/kanban/'.$board->id.'/arsiv') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="archive"></i>
            <span>Arşiv</span>
        </a>
    </div>
</div>

<div class="section">
    {{-- Ay navigasyonu --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px">
        <button id="prevMonth" class="btn btn-secondary btn-sm">
            <i data-lucide="chevron-left"></i>
            <span>Önceki</span>
        </button>
        <div id="monthTitle" style="font-size:20px;font-weight:700;color:var(--text);letter-spacing:-0.02em"></div>
        <button id="nextMonth" class="btn btn-secondary btn-sm">
            <span>Sonraki</span>
            <i data-lucide="chevron-right"></i>
        </button>
    </div>

    {{-- Gün başlıkları --}}
    <div class="cal-grid" style="margin-bottom:6px">
        <div class="cal-header-cell">Pzt</div>
        <div class="cal-header-cell">Sal</div>
        <div class="cal-header-cell">Çar</div>
        <div class="cal-header-cell">Per</div>
        <div class="cal-header-cell">Cum</div>
        <div class="cal-header-cell">Cmt</div>
        <div class="cal-header-cell">Paz</div>
    </div>

    {{-- Takvim hücreleri --}}
    <div id="calGrid" class="cal-grid"></div>
</div>

{{-- Açıklama (legend) --}}
<div class="section" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
    <span style="font-size:12px;font-weight:600;color:var(--text-secondary)">Öncelik:</span>
    <span class="cal-pill" style="background:#22c55e">🟢 Düşük</span>
    <span class="cal-pill" style="background:#b8b62e">🟡 Normal</span>
    <span class="cal-pill" style="background:#f97316">🟠 Yüksek</span>
    <span class="cal-pill" style="background:#ef4444">🔴 Acil</span>
    <span style="width:1px;height:18px;background:var(--border)"></span>
    <span class="cal-pill overdue" style="background:rgba(239,68,68,0.85)">⚠️ Tarihi Geçmiş</span>
    <span style="width:1px;height:18px;background:var(--border)"></span>
    <span style="font-size:12px;color:var(--text-secondary)">▶ Başlangıç · ⏰ Son tarih · <span class="cal-bar" style="display:inline-block;width:34px;vertical-align:middle;background:var(--brand)"></span> devam eden aralık</span>
</div>

@php
$takvimCards = $cards->map(function($c) {
    return [
        'id'        => $c->id,
        'baslik'    => $c->baslik,
        'son_tarih' => $c->son_tarih ? \Carbon\Carbon::parse($c->son_tarih)->format('Y-m-d') : null,
        'baslangic_tarihi' => $c->baslangic_tarihi ? \Carbon\Carbon::parse($c->baslangic_tarihi)->format('Y-m-d') : null,
        'oncelik'   => $c->oncelik,
        'liste'     => $c->list->baslik ?? $c->list->adi ?? '',
        'renk'      => $c->renk,
    ];
});
@endphp

<script>
const TAKVIM_CARDS = @json($takvimCards);

const ONCELIK_RENK = {
    dusuk: '#22c55e',
    normal: '#b8b62e',
    yuksek: '#f97316',
    acil: '#ef4444',
};

const TR_MONTHS = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

let curYear = new Date().getFullYear();
let curMonth = new Date().getMonth();

function buildCardMap(year, month) {
    const map = {};
    const todayStr = new Date().toISOString().slice(0, 10);
    const pad = n => String(n).padStart(2, '0');

    TAKVIM_CARDS.forEach(c => {
        // Başlangıç veya son tarihten en az biri olmalı
        const startRaw = c.baslangic_tarihi || c.son_tarih;
        const endRaw   = c.son_tarih || c.baslangic_tarihi;
        if (!startRaw || !endRaw) return;

        // Ters girilmişse düzelt
        const s = startRaw <= endRaw ? startRaw : endRaw;
        const e = startRaw <= endRaw ? endRaw : startRaw;
        const overdue = !!c.son_tarih && c.son_tarih < todayStr;

        // Aralıktaki her günü işle (güvenlik sınırı: 400 gün)
        let cur = new Date(s + 'T00:00:00');
        const endD = new Date(e + 'T00:00:00');
        let guard = 0;
        while (cur <= endD && guard++ < 400) {
            if (cur.getFullYear() === year && cur.getMonth() === month) {
                const d = cur.getDate();
                const ds = `${cur.getFullYear()}-${pad(cur.getMonth() + 1)}-${pad(d)}`;
                let type;
                if (s === e) type = 'single';
                else if (ds === s) type = 'start';
                else if (ds === e) type = 'end';
                else type = 'mid';
                if (!map[d]) map[d] = [];
                map[d].push({ ...c, overdue, type });
            }
            cur.setDate(cur.getDate() + 1);
        }
    });
    return map;
}

function renderCalendar() {
    const title = document.getElementById('monthTitle');
    const grid = document.getElementById('calGrid');
    title.textContent = TR_MONTHS[curMonth] + ' ' + curYear;

    const firstDay = new Date(curYear, curMonth, 1);
    let startDow = firstDay.getDay();
    startDow = startDow === 0 ? 6 : startDow - 1;

    const daysInMonth = new Date(curYear, curMonth + 1, 0).getDate();
    const daysInPrevM = new Date(curYear, curMonth, 0).getDate();
    const todayStr = new Date().toISOString().slice(0, 10);
    const cardMap = buildCardMap(curYear, curMonth);

    let html = '';
    const totalCells = Math.ceil((startDow + daysInMonth) / 7) * 7;

    for (let i = 0; i < totalCells; i++) {
        let dayNum, isOtherMonth, dateStr;

        if (i < startDow) {
            dayNum = daysInPrevM - startDow + i + 1;
            const pm = curMonth === 0 ? 12 : curMonth;
            const py = curMonth === 0 ? curYear - 1 : curYear;
            dateStr = `${py}-${String(pm).padStart(2,'0')}-${String(dayNum).padStart(2,'0')}`;
            isOtherMonth = true;
        } else if (i >= startDow + daysInMonth) {
            dayNum = i - startDow - daysInMonth + 1;
            const nm = curMonth === 11 ? 1 : curMonth + 2;
            const ny = curMonth === 11 ? curYear + 1 : curYear;
            dateStr = `${ny}-${String(nm).padStart(2,'0')}-${String(dayNum).padStart(2,'0')}`;
            isOtherMonth = true;
        } else {
            dayNum = i - startDow + 1;
            dateStr = `${curYear}-${String(curMonth+1).padStart(2,'0')}-${String(dayNum).padStart(2,'0')}`;
            isOtherMonth = false;
        }

        const isToday = dateStr === todayStr && !isOtherMonth;
        const cards = (!isOtherMonth && cardMap[dayNum]) ? cardMap[dayNum] : [];

        let pillsHtml = '';
        cards.forEach(c => {
            const bg = ONCELIK_RENK[c.oncelik] || '#b8b62e';
            const tt = `${c.baslik}${c.liste ? ' [' + c.liste + ']' : ''}`
                + (c.baslangic_tarihi ? ' · Başlangıç: ' + c.baslangic_tarihi : '')
                + (c.son_tarih ? ' · Son: ' + c.son_tarih : '');

            if (c.type === 'mid') {
                // Aradaki günler: ince devam şeridi
                pillsHtml += `<span class="cal-bar" style="background:${bg}" title="${escHtml(tt)}"></span>`;
            } else {
                let prefix = '';
                if (c.type === 'start') prefix = '▶ ';
                else if (c.type === 'end') prefix = '⏰ ';
                else if (c.type === 'single' && c.baslangic_tarihi && !c.son_tarih) prefix = '▶ ';
                const overdue = (c.overdue && (c.type === 'end' || c.type === 'single')) ? ' overdue' : '';
                pillsHtml += `<span class="cal-pill${overdue}" style="background:${bg}" title="${escHtml(tt)}">${prefix}${escHtml(c.baslik)}</span>`;
            }
        });

        html += `<div class="cal-cell${isOtherMonth?' other-month':''}${isToday?' today':''}">
            <div class="cal-day-num">${dayNum}</div>
            ${pillsHtml}
        </div>`;
    }

    grid.innerHTML = html;
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('prevMonth').addEventListener('click', () => {
    curMonth--;
    if (curMonth < 0) { curMonth = 11; curYear--; }
    renderCalendar();
});
document.getElementById('nextMonth').addEventListener('click', () => {
    curMonth++;
    if (curMonth > 11) { curMonth = 0; curYear++; }
    renderCalendar();
});

renderCalendar();
</script>

@endsection