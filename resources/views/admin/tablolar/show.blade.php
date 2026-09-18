@extends('admin._layout')

@section('title', $sp->ad . ' — Tablo')

@push('head')
{{-- Luckysheet CSS dosyaları (sürüm SABİT — @latest sürüm kaymasıyla bug üretebilir) --}}
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/plugins/css/pluginsCss.css' />
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/plugins/plugins.css' />
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/css/luckysheet.css' />
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/assets/iconfont/iconfont.css' />

<style>
    /* Sayfa düzeni — Luckysheet için özel */
    .luckysheet-wrap {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        position: relative;
        height: calc(100vh - 220px);
        min-height: 580px;
    }

    #luckysheet {
        margin: 0;
        padding: 0;
        position: absolute;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
    }

    .save-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .save-indicator.idle { background: var(--bg-subtle); color: var(--text-muted); }
    .save-indicator.saving { background: var(--warning-soft); color: var(--warning); }
    .save-indicator.saved { background: var(--success-soft); color: var(--success); }
    .save-indicator.error { background: var(--danger-soft); color: var(--danger); }

    .autosave-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-secondary);
        cursor: pointer;
        user-select: none;
    }
    .autosave-toggle input { width: auto !important; margin: 0; }

    /* ── TAM EKRAN ── */
    .luckysheet-wrap.ls-tam-ekran {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 99999;
        height: 100vh !important;
        min-height: 100vh;
        border-radius: 0;
        border: 0;
        margin: 0;
    }
    body.ls-tam-ekran-acik { overflow: hidden; }
    #fullscreenExitBtn {
        display: none;
        position: absolute;
        right: 18px;
        bottom: 52px;
        z-index: 100000;
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid var(--border, #e5e7eb);
        background: var(--surface, #fff);
        color: var(--text, #1f2419);
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(0,0,0,.18);
        font-family: inherit;
    }
    #fullscreenExitBtn:hover { border-color: var(--brand, #b8b62e); }
    .luckysheet-wrap.ls-tam-ekran #fullscreenExitBtn { display: block; }

    /* ── PARA BİRİMİ MENÜSÜ ── */
    .para-birim-sec {
        display: block;
        width: 100%;
        text-align: left;
        padding: 10px 14px;
        border: 0;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        color: var(--text, #1f2419);
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
    }
    .para-birim-sec:hover { background: rgba(184,182,46,.12); }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.tablolar.index') }}">Tablolar</a>
    <span class="sep">/</span>
    <span class="current">{{ $sp->ad }}</span>
</div>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 class="page-title">{{ $sp->ikon }} {{ $sp->ad }}</h1>
            @if($sp->aciklama)
                <div class="page-subtitle">{{ $sp->aciklama }}</div>
            @else
                <div class="page-subtitle">Excel tablosu — formüller, hesaplamalar destekli</div>
            @endif
        </div>
    </div>
    <div class="page-actions" style="display:flex;align-items:center;gap:10px">
        <span id="saveIndicator" class="save-indicator idle">
            <i data-lucide="circle" style="width:10px;height:10px"></i>
            <span id="saveIndicatorText">Hazır</span>
        </span>
        <label class="autosave-toggle" title="Otomatik kaydet">
            <input type="checkbox" id="autoSaveToggle" {{ $sp->otomatik_kaydet ? 'checked' : '' }}>
            <span>Otomatik kaydet</span>
        </label>
        <button type="button" id="langToggleBtn" class="btn btn-secondary btn-sm" title="Tablo arayüz dili (TR/EN)">
            <i data-lucide="globe"></i>
            <span id="langToggleText">TR</span>
        </button>
        <div style="position:relative" id="paraBirimWrap">
            <button type="button" id="paraBirimBtn" class="btn btn-secondary btn-sm" title="Seçili hücrelere para birimi formatı uygula">
                <span style="font-weight:800">₺</span>
                <span>Para Birimi</span>
            </button>
            <div id="paraBirimMenu" style="display:none;position:absolute;top:calc(100% + 6px);right:0;z-index:9000;background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,.16);overflow:hidden;min-width:190px">
                <button type="button" class="para-birim-sec" data-fa='"₺"#,##0.00'>₺&nbsp;&nbsp;Türk Lirası (TL)</button>
                <button type="button" class="para-birim-sec" data-fa='"$"#,##0.00'>$&nbsp;&nbsp;Dolar (USD)</button>
                <button type="button" class="para-birim-sec" data-fa='"€"#,##0.00'>€&nbsp;&nbsp;Euro (EUR)</button>
                <button type="button" class="para-birim-sec" data-fa='"AED "#,##0.00'>AED&nbsp;&nbsp;BAE Dirhemi (Dubai)</button>
                <button type="button" class="para-birim-sec" data-fa="General" style="border-top:1px solid var(--border,#eee);color:var(--text-muted,#94a3b8)">✕&nbsp;&nbsp;Formatı temizle (sayı)</button>
            </div>
        </div>
        <button type="button" id="fullscreenBtn" class="btn btn-secondary btn-sm" title="Tabloyu tam ekran yap">
            <i data-lucide="maximize"></i>
            <span>Tam Ekran</span>
        </button>
        <button type="button" id="manualSaveBtn" class="btn btn-primary btn-sm">
            <i data-lucide="save"></i>
            <span>Kaydet</span>
        </button>
        <a href="{{ route('admin.tablolar.edit', $sp->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="settings"></i>
            <span>Ayarlar</span>
        </a>
    </div>
</div>

<div class="luckysheet-wrap" id="luckysheetWrap">
    <div id="luckysheet"></div>
    <button type="button" id="fullscreenExitBtn">✕ Tam ekrandan çık</button>
</div>

{{-- Luckysheet JS dosyaları (sıra önemli; sürüm SABİT) --}}
<script src="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/plugins/js/plugin.js"></script>
<script src="https://cdn.jsdelivr.net/npm/luckysheet@2.1.13/dist/luckysheet.umd.js"></script>
<script src="{{ asset('js/luckysheet-tr.js') }}?v=20260605b"></script>

<script>
const _CSRF = document.querySelector('meta[name="csrf-token"]').content;
const _SAVE_URL = @json(route('admin.tablolar.save', $sp->id));
const _AUTOSAVE_URL = @json(route('admin.tablolar.toggle-autosave', $sp->id));

// Spreadsheet verisi - PHP'den (object cast KRİTİK)
@php
    $sheets = [];
    if (!empty($sp->veri)) {
        $decoded = json_decode($sp->veri, true);
        if (is_array($decoded) && !empty($decoded)) {
            $sheets = $decoded;
        }
    }
    if (empty($sheets)) {
        // Boş varsayılan sayfa
        $sheets = [[
            'name' => 'Sayfa1',
            'index' => '0',
            'status' => 1,
            'order' => 0,
            'row' => 36,
            'column' => 18,
            'config' => (object) [],
            'celldata' => [],
        ]];
    } else {
        // Her sayfanın config'i object olmalı (Luckysheet kuralı)
        foreach ($sheets as $i => $s) {
            if (!isset($s['config']) || empty($s['config']) || (is_array($s['config']) && count($s['config']) === 0)) {
                $sheets[$i]['config'] = (object) [];
            }

            // ── ŞİŞME TEMİZLİĞİ (KRİTİK) ──
            // getAllSheets() ile kaydedilen veri, gerçek "celldata"nın yanında
            // devasa hesaplanmış "data" matrisi de içerir (14 MB şişmenin asıl kaynağı).
            // Luckysheet "celldata"dan kendi "data"sını zaten üretir; bu yüzden
            // şişmiş "data"yı yüklemeden ÖNCE atıyoruz. Gerçek veri (celldata) korunur.
            // Sadece celldata DOLU ise data'yı at (boşsa data'ya dokunma, veri kaybı olmasın).
            if (isset($sheets[$i]['data'])
                && isset($sheets[$i]['celldata'])
                && is_array($sheets[$i]['celldata'])
                && count($sheets[$i]['celldata']) > 0) {
                unset($sheets[$i]['data']);
            }
        }
    }
@endphp

const _SHEETS_DATA = @json($sheets, JSON_UNESCAPED_UNICODE);
// Orijinal veri boyutu (veri kaybı korumasında referans) — sunucudaki gerçek boyut
window._ORIJINAL_VERI_BOYUTU = {{ $sp->veri ? strlen($sp->veri) : 0 }};
let _saveTimer = null;
let _isDirty = false;
let _autoSaveEnabled = {{ $sp->otomatik_kaydet ? 'true' : 'false' }};
// İlk yükleme bitene kadar autosave TETİKLENMESİN (yarım yüklenmiş veriyi kaydetmeyi önler)
let _ilkYuklemeBitti = false;

// Save indicator helpers
function setSaveStatus(status, text) {
    const ind = document.getElementById('saveIndicator');
    const txt = document.getElementById('saveIndicatorText');
    ind.className = 'save-indicator ' + status;
    txt.textContent = text;
}

// Luckysheet initialize
document.addEventListener('DOMContentLoaded', function() {
    if (typeof luckysheet === 'undefined') {
        document.getElementById('luckysheet').innerHTML = '<div style="padding:60px;text-align:center;color:var(--danger)"><h3>⚠️ Luckysheet yüklenemedi</h3><p style="color:var(--text-muted)">CDN bağlantısını kontrol et veya sayfayı yenile.</p></div>';
        return;
    }

    luckysheet.create({
        container: 'luckysheet',
        title: @json($sp->ad),
        lang: 'en',  // Taban dil İngilizce; luckysheet-tr.js arayüzü Türkçeleştirir (resmi tr paketi yok)
        data: _SHEETS_DATA,
        devicePixelRatio: window.devicePixelRatio || 1, // koordinat/çizim kaymasına karşı (zoom'lu ekranlar)
        showinfobar: false,
        showsheetbar: true,
        showstatisticBar: true,
        sheetFormulaBar: true,
        enableAddRow: true,
        enableAddBackTop: false,
        userInfo: false,
        functionButton: '',
        showtoolbarConfig: {
            undoRedo: true, paintFormat: true, currencyFormat: true, percentageFormat: true,
            numberDecrease: true, numberIncrease: true, moreFormats: true,
            font: true, fontSize: true, bold: true, italic: true, strikethrough: true, underline: true,
            textColor: true, fillColor: true, border: true, mergeCell: true,
            horizontalAlignMode: true, verticalAlignMode: true, textWrapMode: true, textRotateMode: true,
            image: true, link: true, chart: true, postil: true, pivotTable: false,
            function: true, frozenMode: true, sortAndFilter: true, conditionalFormat: false,
            dataVerification: true, splitColumn: true, screenshot: true, findAndReplace: true,
            protection: true, print: true
        },
        hook: {
            cellEditBefore: function() { _isDirty = true; },
            cellMousedown: function() { /* tıklama */ },
            updated: function() {
                if (!_ilkYuklemeBitti) return; // ilk yükleme sırasındaki updated'ları yok say
                _isDirty = true;
                if (_autoSaveEnabled) scheduleAutoSave();
            },
            sheetCreateAfter: function() {
                _isDirty = true;
                if (_autoSaveEnabled) scheduleAutoSave();
            },
            sheetDeleteAfter: function() {
                _isDirty = true;
                if (_autoSaveEnabled) scheduleAutoSave();
            },
            sheetRenameAfter: function() {
                _isDirty = true;
                if (_autoSaveEnabled) scheduleAutoSave();
            },
        }
    });

    // ── KOORDİNAT KAYMASI KORUMASI ──
    // Kap boyutu değişince (sidebar aç/kapa, pencere boyutu, tam ekran) Luckysheet'in
    // tuvali yeniden hesaplanmazsa tıklanan hücre ile seçilen hücre KAYAR
    // (formülde E1122 gibi saçma referanslar bunun belirtisidir).
    // İlk yükleme tamamlandı işareti — 3 sn sonra autosave devreye girebilir
    // (Luckysheet'in ilk render + veri yükleme updated olaylarını atlatmak için)
    setTimeout(function() { _ilkYuklemeBitti = true; }, 3000);

    setTimeout(lsResize, 400); // ilk çizim oturduktan sonra bir kez hizala
    window.addEventListener('resize', lsResizeDebounced);
    if (window.ResizeObserver) {
        try {
            new ResizeObserver(lsResizeDebounced).observe(document.getElementById('luckysheetWrap'));
        } catch (e) {}
    }
});

// Luckysheet'i kap boyutuna yeniden hizala
function lsResize() {
    try { if (typeof luckysheet !== 'undefined' && luckysheet.resize) luckysheet.resize(); } catch (e) {}
}
let _lsResizeTimer = null;
function lsResizeDebounced() {
    if (_lsResizeTimer) clearTimeout(_lsResizeTimer);
    _lsResizeTimer = setTimeout(lsResize, 150);
}

// ── PARA BİRİMİ FORMATI (seçili hücrelere uygula) ──
(function() {
    var wrap = document.getElementById('paraBirimWrap');
    var btn = document.getElementById('paraBirimBtn');
    var menu = document.getElementById('paraBirimMenu');
    if (!wrap || !btn || !menu) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
    });
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#paraBirimWrap')) menu.style.display = 'none';
    });

    menu.querySelectorAll('.para-birim-sec').forEach(function(secim) {
        secim.addEventListener('click', function() {
            menu.style.display = 'none';
            if (typeof luckysheet === 'undefined') return;
            var fa = secim.getAttribute('data-fa');
            try {
                var secili = luckysheet.getRange && luckysheet.getRange();
                if (!secili || !secili.length) { alert('Önce formatlanacak hücreleri seç.'); return; }
                if (fa === 'General') {
                    luckysheet.setRangeFormat('ct', { fa: 'General', t: 'g' });
                } else {
                    luckysheet.setRangeFormat('ct', { fa: fa, t: 'n' });
                }
                _isDirty = true;
                if (_autoSaveEnabled) scheduleAutoSave();
            } catch (err) {
                console.error('Para birimi format hatası:', err);
                alert('Format uygulanamadı: ' + err.message);
            }
        });
    });
})();

// ── TAM EKRAN ──
(function() {
    var wrap = document.getElementById('luckysheetWrap');
    var btn = document.getElementById('fullscreenBtn');
    var exitBtn = document.getElementById('fullscreenExitBtn');
    if (!wrap || !btn || !exitBtn) return;

    function tamEkran(ac) {
        wrap.classList.toggle('ls-tam-ekran', ac);
        document.body.classList.toggle('ls-tam-ekran-acik', ac);
        var span = btn.querySelector('span');
        if (span) span.textContent = ac ? 'Küçült' : 'Tam Ekran';
        // Boyut değişti -> tuvali yeniden hizala
        setTimeout(lsResize, 60);
        setTimeout(lsResize, 350);
    }

    btn.addEventListener('click', function() {
        tamEkran(!wrap.classList.contains('ls-tam-ekran'));
    });
    exitBtn.addEventListener('click', function() {
        tamEkran(false);
    });
})();

// Otomatik kaydetme — debounce 3 saniye
function scheduleAutoSave() {
    if (_saveTimer) clearTimeout(_saveTimer);
    setSaveStatus('idle', 'Değişti…');
    _saveTimer = setTimeout(function() {
        saveSpreadsheet();
    }, 3000);
}

// Manuel kaydet
function saveSpreadsheet() {
    if (typeof luckysheet === 'undefined') return;
    setSaveStatus('saving', 'Kaydediliyor…');

    try {
        const data = luckysheet.getAllSheets();

        // ── ŞİŞME ÖNLEME ──
        // getAllSheets() devasa hesaplanmış "data" matrisi döndürür (14 MB şişmenin kaynağı).
        // celldata gerçek veriyi tutar; kaydederken data'yı atıp sadece celldata'yı saklarız.
        // Luckysheet açılışta celldata'dan data'yı yeniden üretir.
        if (Array.isArray(data)) {
            data.forEach(function(sheet) {
                if (sheet && sheet.data && Array.isArray(sheet.celldata) && sheet.celldata.length > 0) {
                    delete sheet.data;
                }
            });
        }

        const jsonData = JSON.stringify(data);

        // ── VERİ KAYBI KORUMASI (client) ──
        // Luckysheet düzgün yüklenmediyse boş/çok küçük veri döner. Bunu kaydetme!
        if (!data || !Array.isArray(data) || data.length === 0 || jsonData.length < 20) {
            setSaveStatus('error', '✗ Veri okunamadı, kaydetme iptal (veriniz korundu)');
            console.warn('Bos veri kaydetme engellendi (client):', jsonData.length);
            return;
        }
        // Sayfa ilk açılışında yüklenen orijinal boyutun çok altına düştüyse şüpheli
        if (window._ORIJINAL_VERI_BOYUTU && window._ORIJINAL_VERI_BOYUTU > 5000
            && jsonData.length < (window._ORIJINAL_VERI_BOYUTU * 0.2)) {
            setSaveStatus('error', '✗ Ani veri kaybı algılandı, kaydetme durduruldu (veriniz korundu)');
            console.warn('Ani kucukme, kaydetme engellendi:', jsonData.length, 'vs', window._ORIJINAL_VERI_BOYUTU);
            return;
        }

        fetch(_SAVE_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': _CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'veri=' + encodeURIComponent(jsonData) + '&_token=' + encodeURIComponent(_CSRF)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                _isDirty = false;
                setSaveStatus('saved', '✓ Kaydedildi ' + (d.kaydedildi_at || ''));
                setTimeout(() => {
                    if (!_isDirty) setSaveStatus('idle', 'Hazır');
                }, 3000);
            } else {
                setSaveStatus('error', '✗ Hata: ' + (d.error || 'Kaydedilemedi'));
            }
        })
        .catch(err => {
            setSaveStatus('error', '✗ Ağ hatası');
            console.error('Save error:', err);
        });
    } catch (e) {
        setSaveStatus('error', '✗ Hata: ' + e.message);
        console.error('Save exception:', e);
    }
}

// Manuel kaydet butonu
document.getElementById('manualSaveBtn').addEventListener('click', function() {
    saveSpreadsheet();
});

// TR/EN dil geçişi (luckysheet-tr.js)
(function() {
    var btn = document.getElementById('langToggleBtn');
    var txt = document.getElementById('langToggleText');
    if (!btn || !txt) return;
    var cur = (window.LuckysheetTR && window.LuckysheetTR.lang) ? window.LuckysheetTR.lang() : 'tr';
    txt.textContent = cur.toUpperCase();
    btn.addEventListener('click', function() {
        if (!window.LuckysheetTR) return;
        // Değişiklik varsa önce kaydet, sonra dili değiştir (sayfa yenilenir)
        if (_isDirty) {
            try { saveSpreadsheet(); } catch (e) {}
            setTimeout(function() { window.LuckysheetTR.toggle(); }, 800);
        } else {
            window.LuckysheetTR.toggle();
        }
    });
})();

// Otomatik kaydet toggle
document.getElementById('autoSaveToggle').addEventListener('change', function() {
    _autoSaveEnabled = this.checked;
    fetch(_AUTOSAVE_URL, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': _CSRF,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'otomatik_kaydet=' + (this.checked ? '1' : '0') + '&_token=' + encodeURIComponent(_CSRF)
    }).catch(err => console.error('Autosave toggle error:', err));
});

// Kaydet kısayolu: Ctrl/Cmd + S
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveSpreadsheet();
    }
});

// Sayfa kapatma uyarısı (değişiklik varsa)
window.addEventListener('beforeunload', function(e) {
    if (_isDirty && !_autoSaveEnabled) {
        e.preventDefault();
        e.returnValue = 'Kaydedilmemiş değişiklikler var. Çıkmak istediğine emin misin?';
        return e.returnValue;
    }
});
</script>

@endsection