@extends('admin._layout')

@section('title', 'Yeni Paket')

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

    /* Sticky bottom save bar */
    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        backdrop-filter: blur(10px);
        z-index: 10;
    }

    /* Multi-select kategori */
    select[multiple] {
        min-height: 110px;
        padding: 8px !important;
    }

    /* TinyMCE temayla uyumlu olsun */
    .tox-tinymce {
        border-color: var(--border) !important;
        border-radius: var(--radius-md) !important;
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.paketler.index') }}">Paketler</a>
    <span class="sep">/</span>
    <span class="current">Yeni Paket</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="package-plus"></i>
            Yeni Paket
        </h1>
        <div class="page-subtitle">Web paketi oluştur ve yayına al</div>
    </div>
</div>

<form action="{{ route('admin.paketler.eklePost') }}" method="POST" enctype="multipart/form-data"
      onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf

    <div class="form-grid">
        {{-- SOL KOLON --}}
        <div>
            {{-- TEMEL BİLGİLER --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Temel Bilgiler</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="kategori[]" multiple class="form-select">
                            @foreach($kategoriler ?? [] as $k)
                                <option value="{{ $k->id ?? $k }}"
                                    @if(is_array(old('kategori')) && in_array($k->id ?? $k, old('kategori'))) selected @endif>
                                    {{ $k->adi ?? $k }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-help">Ctrl/Cmd ile birden fazla seçilebilir</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fiyat (₺) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="tutar" value="{{ old('tutar') }}"
                               placeholder="500.00" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sıra</label>
                        <input type="number" name="sira" value="{{ old('sira', 0) }}" class="form-input">
                        <small class="form-help">Küçük sayı önce gelir</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SEO Slug</label>
                        <input type="text" name="seo" value="{{ old('seo') }}"
                               placeholder="paket-adi" class="form-input">
                        <small class="form-help">Boş bırakırsan otomatik üretilir</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Demo Link</label>
                        <input type="text" inputmode="url" name="demo_link" value="{{ old('demo_link') }}"
                               placeholder="https://demo..." class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Demo Admin Link</label>
                        <input type="text" inputmode="url" name="demo_admin_link" value="{{ old('demo_admin_link') }}"
                               placeholder="https://demo-admin..." class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Etiketler</label>
                        <input type="text" name="etiketler" value="{{ old('etiketler') }}"
                               placeholder="web, kurumsal, e-ticaret" class="form-input">
                        <small class="form-help">Virgülle ayırarak yazın</small>
                    </div>
                </div>
            </div>

            {{-- KAPAK GÖRSELİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Kapak Görseli</span>
                </div>
                <div class="form-group">
                    <input type="file" name="resim" accept="image/*" class="form-input">
                    <small class="form-help">PNG / JPG / WebP — kart görseli olarak gösterilir (önerilen 16:9 oran)</small>
                </div>
            </div>

            {{-- ÇOKLU DİL TAB'LARI (mevcut partial) --}}
            <div style="margin-top:16px">
                @include('admin.partials.ceviri-tabs', [
                    'tablo'     => 'yazilimlar',
                    'kayit_id'  => null,
                    'tr_values' => [],
                    'fields'    => [
                        'adi'      => ['label' => 'Paket Adı', 'type' => 'text', 'required' => true],
                        'kisa'     => ['label' => 'Kısa Açıklama', 'type' => 'textarea', 'rows' => 3],
                        'aciklama' => ['label' => 'Detaylı Açıklama', 'type' => 'wysiwyg', 'rows' => 6],
                        'ozellik'  => ['label' => 'Özellikler (her satır bir özellik)', 'type' => 'textarea', 'rows' => 6, 'placeholder' => "✓ Sınırsız sayfa\n✓ SSL Dahil\n✓ Mobil uyumlu"],
                    ],
                ])
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            {{-- GÖRÜNÜRLÜK --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="eye"></i>
                    <span>Görünürlük</span>
                </div>

                <div class="toggle-card" style="margin-bottom:12px">
                    <div>
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Site üzerinde görünür ve satın alınabilir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Anasayfada Göster</div>
                        <div class="desc">Paketin KENDİ kategorisi ana sayfada bir bölüm olarak açılır, paket o bölümün başında listelenir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="anasayfa" value="1"
                               {{ old('anasayfa') ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            {{-- BİLGİ --}}
            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.7">
                    <p style="margin:0 0 8px"><span class="required">*</span> ile işaretli alanlar zorunludur.</p>
                    <p style="margin:0 0 8px">Çoklu dil sekmelerinde önce <strong>Türkçe</strong>'yi doldurmanız önerilir.</p>
                    <p style="margin:0">Pakete <strong>kategori</strong> seçilmesi arama ve filtreleme için önemlidir.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- STICKY ALT BAR --}}
    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.paketler.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Paketi Kaydet</span>
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
        height: 400,
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
initRich('textarea.rich-mini', { height: 180, toolbar: 'bold italic underline forecolor | bullist numlist | link' });
initRich('textarea.rich-list', { height: 240, toolbar: 'bold italic forecolor | bullist numlist | link' });

// Tema değişirse TinyMCE'yi yeniden başlat
const themeBtn = document.getElementById('themeToggle');
if (themeBtn) {
    themeBtn.addEventListener('click', () => {
        setTimeout(() => {
            if (window.tinymce) {
                tinymce.remove();
                initRich('textarea.rich-full');
                initRich('textarea.rich-mini', { height: 180, toolbar: 'bold italic underline forecolor | bullist numlist | link' });
                initRich('textarea.rich-list', { height: 240, toolbar: 'bold italic forecolor | bullist numlist | link' });
            }
        }, 100);
    });
}
</script>

@endsection