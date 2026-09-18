@extends('admin._layout')

@section('title', 'Yeni Hizmet')

@push('head')
<style>
    .toggle-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; gap: 16px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
    }
    .toggle-card .desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .toggle-card .lbl-strong { font-weight: 600; font-size: 14px; }

    .ios-toggle { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
    .ios-toggle input { opacity: 0; width: 0; height: 0; }
    .ios-toggle .knob {
        position: absolute; cursor: pointer; inset: 0;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 26px;
        transition: 0.25s;
    }
    .ios-toggle .knob:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background: #fff;
        border-radius: 50%; transition: 0.25s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .ios-toggle input:checked + .knob {
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        border-color: var(--brand);
    }
    .ios-toggle input:checked + .knob:before { transform: translateX(22px); }

    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        z-index: 10;
    }

    .tox-tinymce { border-color: var(--border) !important; border-radius: var(--radius-md) !important; }

    /* MÜŞTERİ AUTOCOMPLETE */
    .musteri-ac-wrap { position: relative; }
    .musteri-ac-input {
        width: 100%;
        padding: 12px 14px 12px 42px;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--surface);
        color: var(--text);
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.15s;
    }
    .musteri-ac-input:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(184,182,46,0.15);
    }
    .musteri-ac-icon {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        width: 18px; height: 18px; color: var(--text-muted); pointer-events: none;
    }
    .musteri-ac-list {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        max-height: 280px; overflow-y: auto;
        z-index: 50;
        display: none;
    }
    .musteri-ac-list.show { display: block; }
    .musteri-ac-item {
        padding: 10px 14px;
        cursor: pointer;
        border-bottom: 1px solid var(--border);
        transition: background 0.1s;
    }
    .musteri-ac-item:last-child { border-bottom: none; }
    .musteri-ac-item:hover, .musteri-ac-item.active {
        background: var(--brand-soft);
    }
    .musteri-ac-item .nm { font-weight: 600; font-size: 13px; }
    .musteri-ac-item .em { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .musteri-ac-item.empty { color: var(--text-muted); cursor: default; text-align: center; padding: 16px; }
    .musteri-ac-item.empty:hover { background: transparent; }

    .selected-musteri {
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.3);
        border-radius: var(--radius-md);
        padding: 12px 14px;
        margin-top: 10px;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .selected-musteri.show { display: flex; }
    .selected-musteri .info { flex: 1; min-width: 0; }
    .selected-musteri .info .nm { font-weight: 600; font-size: 14px; }
    .selected-musteri .info .em { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .selected-musteri .clear {
        background: none; border: none; cursor: pointer; color: var(--text-muted);
        padding: 4px 8px; border-radius: 6px; transition: 0.15s;
    }
    .selected-musteri .clear:hover { color: var(--danger); background: rgba(0,0,0,0.05); }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.hizmetler.index') }}">Hizmetler</a>
    <span class="sep">/</span>
    <span class="current">Yeni Hizmet</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="wrench"></i>
            Yeni Hizmet
        </h1>
        <div class="page-subtitle">Bir müşteriye yeni hizmet atayın</div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:20px">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.hizmetler.eklePost') }}" method="POST" enctype="multipart/form-data"
      onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf

    <div class="form-grid">
        <div>
            {{-- MÜŞTERİ SEÇİMİ --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="user"></i>
                    <span>Müşteri Seçimi</span>
                </div>
                <div class="form-group full">
                    <label class="form-label">Müşteri Ara <span class="required">*</span></label>
                    <div class="musteri-ac-wrap">
                        <svg class="musteri-ac-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="musteriAcInput" class="musteri-ac-input"
                               placeholder="Ad, soyad veya e-posta yazın..." autocomplete="off">
                        <div id="musteriAcList" class="musteri-ac-list"></div>
                    </div>
                    <input type="hidden" name="uyeid" id="musteriIdInput" value="{{ old('uyeid') }}">

                    {{-- Seçili müşteri kartı --}}
                    <div id="selectedMusteri" class="selected-musteri">
                        <div class="info">
                            <div class="nm" id="selMusteriAd"></div>
                            <div class="em" id="selMusteriEmail"></div>
                        </div>
                        <button type="button" class="clear" onclick="clearMusteri()" title="Temizle">
                            <i data-lucide="x" style="width:16px;height:16px"></i>
                        </button>
                    </div>

                    <small class="form-help" style="margin-top:8px;display:block">
                        Bu hizmet seçilen müşterinin "Hizmetlerim" sayfasında görünecek ve e-posta bildirimi gönderilecektir.
                    </small>
                </div>
            </div>

            {{-- HİZMET BİLGİLERİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Hizmet Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Hizmet Adı <span class="required">*</span></label>
                        <input type="text" name="baslik" value="{{ old('baslik') }}"
                               required class="form-input"
                               placeholder="Örn: Mayıs Ayı Sosyal Medya Yönetimi">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tutar (₺)</label>
                        <input type="text" name="tutar" value="{{ old('tutar') }}"
                               class="form-input" placeholder="Örn: 5000">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Durum</label>
                        <select name="durum" class="form-select">
                            <option value="aktif" {{ old('durum') === 'aktif' ? 'selected' : '' }}>✓ Aktif</option>
                            <option value="beklemede" {{ old('durum') === 'beklemede' ? 'selected' : '' }}>⏳ Beklemede</option>
                            <option value="tamamlandi" {{ old('durum') === 'tamamlandi' ? 'selected' : '' }}>✓ Tamamlandı</option>
                            <option value="iptal" {{ old('durum') === 'iptal' ? 'selected' : '' }}>✗ İptal</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Detaylı İçerik</label>
                        <textarea name="icerik" rows="8" class="form-textarea rich-full"
                                  placeholder="Hizmetin ayrıntılı açıklaması, kapsamı, neyi içerdiği vb.">{{ old('icerik') }}</textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Dosya Ek (opsiyonel)</label>
                        <input type="file" name="dosya" class="form-input"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <small class="form-help">PDF, Word, Excel veya görsel. Maks 10 MB.</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.7">
                    <p style="margin:0 0 8px"><span class="required">*</span> ile işaretli alanlar zorunludur.</p>
                    <p style="margin:0 0 8px">Bu hizmet kaydı seçtiğiniz müşterinin <strong>"Hizmetlerim"</strong> sayfasında görünecektir.</p>
                    <p style="margin:0">Müşteriye otomatik e-posta bildirimi gönderilir.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.hizmetler.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Hizmeti Ata</span>
            </button>
        </div>
    </div>
</form>

{{-- TinyMCE --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
function initRich(selector, opts) {
    const isDark = document.body.classList.contains('theme-dark');
    const contentBg = isDark ? '#0a0a0a' : '#ffffff';
    const contentColor = isDark ? '#f1f5f9' : '#0f172a';

    tinymce.init(Object.assign({
        selector: selector,
        license_key: 'gpl',
        promotion: false,
        branding: false,
        height: 360,
        menubar: false,
        plugins: 'lists link image table code fullscreen autolink',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link image table | code fullscreen',
        fontsize_formats: '10px 12px 14px 16px 18px 20px 24px 28px 32px 40px',
        content_style: `body{font-family:Poppins,Inter,Arial,sans-serif;font-size:14px;background:${contentBg};color:${contentColor};padding:14px} a{color:#b8b62e}`,
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        language: 'tr',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@latest/langs7/tr.js'
    }, opts || {}));
}
initRich('textarea.rich-full');
</script>

{{-- MÜŞTERİ AUTOCOMPLETE --}}
<script>
(function() {
    const uyeler = @json($uyeler);
    const input = document.getElementById('musteriAcInput');
    const list = document.getElementById('musteriAcList');
    const hidden = document.getElementById('musteriIdInput');
    const selBox = document.getElementById('selectedMusteri');
    const selAd = document.getElementById('selMusteriAd');
    const selEm = document.getElementById('selMusteriEmail');

    let activeIndex = -1;
    let currentResults = [];

    function normalize(s) {
        return (s || '').toString().toLowerCase()
            .replace(/ı/g, 'i').replace(/İ/g, 'i')
            .replace(/ş/g, 's').replace(/Ş/g, 's')
            .replace(/ğ/g, 'g').replace(/Ğ/g, 'g')
            .replace(/ü/g, 'u').replace(/Ü/g, 'u')
            .replace(/ö/g, 'o').replace(/Ö/g, 'o')
            .replace(/ç/g, 'c').replace(/Ç/g, 'c');
    }

    function escapeHtml(s) {
        return (s || '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showList(query) {
        const q = normalize(query.trim());
        if (q.length === 0) {
            list.classList.remove('show');
            currentResults = [];
            return;
        }

        const results = uyeler.filter(u => {
            const full = normalize((u.ad || '') + ' ' + (u.soyad || ''));
            const em = normalize(u.email || '');
            return full.includes(q) || em.includes(q);
        }).slice(0, 30);

        currentResults = results;
        activeIndex = -1;

        if (results.length === 0) {
            list.innerHTML = '<div class="musteri-ac-item empty">Eşleşen müşteri yok</div>';
            list.classList.add('show');
            return;
        }

        list.innerHTML = results.map((u, i) =>
            '<div class="musteri-ac-item" data-id="' + u.id + '" data-index="' + i + '">' +
                '<div class="nm">' + escapeHtml(u.ad || '') + ' ' + escapeHtml(u.soyad || '') + '</div>' +
                '<div class="em">' + escapeHtml(u.email || '—') + '</div>' +
            '</div>'
        ).join('');
        list.classList.add('show');
    }

    function pick(u) {
        hidden.value = u.id;
        selAd.textContent = (u.ad || '') + ' ' + (u.soyad || '');
        selEm.textContent = u.email || '—';
        selBox.classList.add('show');
        input.value = '';
        list.classList.remove('show');
    }

    window.clearMusteri = function() {
        hidden.value = '';
        selBox.classList.remove('show');
        input.value = '';
        input.focus();
    };

    input.addEventListener('input', e => showList(e.target.value));
    input.addEventListener('focus', e => { if (e.target.value) showList(e.target.value); });
    input.addEventListener('blur', () => { setTimeout(() => list.classList.remove('show'), 200); });

    list.addEventListener('mousedown', e => {
        const item = e.target.closest('.musteri-ac-item[data-id]');
        if (!item) return;
        const id = parseInt(item.dataset.id, 10);
        const u = uyeler.find(x => x.id === id);
        if (u) pick(u);
    });

    input.addEventListener('keydown', e => {
        if (!list.classList.contains('show') || currentResults.length === 0) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, currentResults.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0) pick(currentResults[activeIndex]);
        } else if (e.key === 'Escape') {
            list.classList.remove('show');
            return;
        } else {
            return;
        }
        list.querySelectorAll('.musteri-ac-item').forEach((el, i) => {
            el.classList.toggle('active', i === activeIndex);
        });
    });

    // old() ile gelen uyeid varsa, müşteri kartını doldur
    const initialId = parseInt(hidden.value, 10);
    if (initialId) {
        const u = uyeler.find(x => x.id === initialId);
        if (u) pick(u);
    }
})();
</script>

@endsection