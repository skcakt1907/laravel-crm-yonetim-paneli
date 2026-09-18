@extends('admin._layout')

@section('title', 'Yeni Kampanya')

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

    .image-upload {
        position: relative;
        border: 2px dashed var(--border);
        border-radius: var(--radius-md);
        padding: 30px 20px;
        text-align: center;
        background: var(--bg-subtle);
        transition: all 0.2s;
        cursor: pointer;
    }
    .image-upload:hover { border-color: var(--brand); background: var(--brand-soft); }
    .image-upload input[type="file"] {
        position: absolute; inset: 0;
        opacity: 0; cursor: pointer;
    }
    .image-upload .ic { color: var(--brand-dark); margin-bottom: 8px; }
    .image-upload .preview {
        max-width: 100%; max-height: 200px;
        border-radius: var(--radius-md);
        margin: 10px auto;
        display: block;
    }

    .indirim-input { position: relative; }
    .indirim-input::after {
        content: "%";
        position: absolute;
        right: 14px; top: 50%;
        transform: translateY(-50%);
        color: var(--brand-dark);
        font-weight: 700;
        pointer-events: none;
    }
    .indirim-input input { padding-right: 30px !important; }

    .tox-tinymce { border-color: var(--border) !important; border-radius: var(--radius-md) !important; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.kampanyalar.index') }}">Kampanyalar</a>
    <span class="sep">/</span>
    <span class="current">Yeni Kampanya</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="megaphone"></i>
            Yeni Kampanya
        </h1>
        <div class="page-subtitle">Yeni bir kampanya, fırsat veya özel teklif tanımlayın</div>
    </div>
</div>

<form action="{{ route('admin.kampanyalar.eklePost') }}" method="POST" enctype="multipart/form-data"
      onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf

    <div class="form-grid">
        <div>
            {{-- TEMEL BİLGİLER --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Temel Bilgiler</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Başlık <span class="required">*</span></label>
                        <input type="text" name="baslik" value="{{ old('baslik') }}"
                               required class="form-input"
                               placeholder="Örn: Yaz İndirimi 2026">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" rows="6" class="form-textarea rich-full"
                                  placeholder="Kampanyanın detaylı açıklaması">{{ old('aciklama') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- TARIH + İNDIRIM + LİNK --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Süre ve Detay</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="date" name="baslangic_tarihi"
                               value="{{ old('baslangic_tarihi') }}"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="bitis_tarihi"
                               value="{{ old('bitis_tarihi') }}"
                               class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">İndirim Oranı</label>
                        <div class="indirim-input">
                            <input type="number" step="0.01" min="0" max="100" name="indirim"
                                   value="{{ old('indirim', 0) }}"
                                   class="form-input"
                                   placeholder="0">
                        </div>
                        <small class="form-help">0–100 arasında. Yoksa 0 bırakın.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', 0) }}"
                               class="form-input"
                               placeholder="0">
                        <small class="form-help">Düşük sayı önce gösterilir</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Hedef Link (opsiyonel)</label>
                        <input type="url" name="link" value="{{ old('link') }}"
                               class="form-input"
                               placeholder="https://...">
                        <small class="form-help">Kampanyaya tıklandığında yönlendirilecek URL</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">SEO Slug (otomatik üretilir)</label>
                        <input type="text" name="seo" value="{{ old('seo') }}"
                               class="form-input"
                               placeholder="boş bırakırsanız başlıktan üretilir"
                               style="font-family:monospace;font-size:13px">
                    </div>
                    <div class="form-group" style="margin-top:16px;padding:14px;background:rgba(184,182,46,.08);border-radius:var(--radius);border:1px solid rgba(184,182,46,.3)">
                                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0">
                                <input type="checkbox" name="mail_gonder" value="1">
                                                            <span><strong>📧 Tüm izinli müşterilere kampanya mailı gönder</strong></span>
                                                            </label>
                                                <small style="display:block;margin-top:6px;font-size:11px;color:var(--text-muted)">
                                                        ⚠️ KVKK: Sadece kampanya iznı olan müşterilere gönderilir. İptal edemezsiniz!
                                                                </small>
                                                </div>
                </div>
            </div>

            {{-- ÇOKLU DİL --}}
            <div style="margin-top:16px">
                @include('admin.partials.ceviri-tabs', [
                    'tablo'     => 'kampanyalar',
                    'kayit_id'  => null,
                    'tr_values' => [],
                    'fields'    => [
                        'baslik'   => ['label' => 'Başlık', 'type' => 'text', 'required' => true],
                        'aciklama' => ['label' => 'Açıklama', 'type' => 'wysiwyg', 'rows' => 6],
                    ],
                ])
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            {{-- RESİM --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Kapak Görseli</span>
                </div>
                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" class="ic" style="width:32px;height:32px"></i>
                    <div style="font-weight:600">Görsel yükle</div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px">
                        JPG, PNG, WEBP — max 2MB
                    </div>
                    <img id="resimPreview" class="preview" style="display:none">
                    <input type="file" name="resim" id="resimInput" accept="image/*"
                           onchange="onImageChange(this)">
                </div>
            </div>

            {{-- GÖRÜNÜRLÜK --}}
            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="eye"></i>
                    <span>Görünürlük</span>
                </div>
                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Sitede yayında ve görünür</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>İpuçları</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.7">
                    <p style="margin:0 0 8px"><span class="required">*</span> zorunlu</p>
                    <p style="margin:0 0 8px">Kapak görseli kart-grid'de en üstte görünür. Önerilen oran: <strong>16:9</strong>.</p>
                    <p style="margin:0">Tarih aralığı dışında kalan kampanyalar otomatik <strong>"Bitti"</strong> badge alır.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.kampanyalar.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Kampanyayı Kaydet</span>
            </button>
        </div>
    </div>
</form>

<script>
function onImageChange(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const preview = document.getElementById('resimPreview');
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

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
        height: 350,
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

const themeBtn = document.getElementById('themeToggle');
if (themeBtn) {
    themeBtn.addEventListener('click', () => {
        setTimeout(() => {
            if (window.tinymce) {
                tinymce.remove();
                initRich('textarea.rich-full');
                initRich('textarea.rich-mini', { height: 180 });
            }
        }, 100);
    });
}
</script>

@endsection