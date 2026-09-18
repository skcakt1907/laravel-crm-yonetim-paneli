@extends('admin._layout')

@section('title', 'Sayfa Düzenle')

@push('head')
@include("admin._partials.form-css.sayfalar")
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.sayfalar.index') }}">Sayfalar</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($sayfa->adi ?? 'Düzenle', 40) }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Sayfa Düzenle
        </h1>
        <div class="page-subtitle">{{ $sayfa->adi ?? '—' }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.sayfalar.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.sayfalar.duzenlePost', $sayfa->id) }}" method="POST" enctype="multipart/form-data"
      onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf

    <div class="form-grid">
        <div>
            {{-- ÇOKLU DİL İÇERİK --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="globe"></i>
                    <span>İçerik</span>
                    <span style="margin-left:auto;font-size:11px;color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0">
                        EN/AR boş bırakılırsa TR kullanılır
                    </span>
                </div>

                <div class="lang-tabs">
                    <button type="button" class="lang-tab active" data-lang="tr">
                        🇹🇷 <span>Türkçe</span>
                    </button>
                    <button type="button" class="lang-tab" data-lang="en">
                        🇬🇧 <span>English</span>
                    </button>
                    <button type="button" class="lang-tab" data-lang="ar">
                        🇸🇦 <span>العربية</span>
                    </button>
                </div>

                {{-- TR --}}
                <div class="lang-pane active" data-pane="tr">
                    <div class="form-group">
                        <label class="form-label">Başlık <span class="required">*</span></label>
                        <input type="text" name="adi" value="{{ old('adi', $sayfa->adi ?? '') }}"
                               required class="form-input"
                               placeholder="Örn: Hakkımızda">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kısa Açıklama</label>
                        <textarea name="kisa" rows="2" class="form-textarea"
                                  placeholder="Sayfanın özet metni">{{ old('kisa', $sayfa->kisa ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Detaylı İçerik <span class="required">*</span></label>
                        <textarea name="aciklama" class="rich-full">{{ old('aciklama', $sayfa->aciklama ?? '') }}</textarea>
                    </div>
                </div>

                {{-- EN --}}
                <div class="lang-pane" data-pane="en">
                    <div class="form-group">
                        <label class="form-label">Title (EN)</label>
                        <input type="text" name="adi_en" value="{{ old('adi_en', $sayfa->adi_en ?? '') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Short description (EN)</label>
                        <textarea name="kisa_en" rows="2" class="form-textarea">{{ old('kisa_en', $sayfa->kisa_en ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Content (EN)</label>
                        <textarea name="aciklama_en" class="rich-full">{{ old('aciklama_en', $sayfa->aciklama_en ?? '') }}</textarea>
                    </div>
                </div>

                {{-- AR --}}
                <div class="lang-pane" data-pane="ar" dir="rtl">
                    <div class="form-group">
                        <label class="form-label">العنوان (AR)</label>
                        <input type="text" name="adi_ar" value="{{ old('adi_ar', $sayfa->adi_ar ?? '') }}" class="form-input" dir="rtl">
                    </div>
                    <div class="form-group">
                        <label class="form-label">وصف قصير (AR)</label>
                        <textarea name="kisa_ar" rows="2" class="form-textarea" dir="rtl">{{ old('kisa_ar', $sayfa->kisa_ar ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">المحتوى (AR)</label>
                        <textarea name="aciklama_ar" class="rich-full">{{ old('aciklama_ar', $sayfa->aciklama_ar ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="search"></i>
                    <span>SEO Ayarları</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">URL Slug</label>
                        <input type="text" name="seo" value="{{ old('seo', $sayfa->seo ?? '') }}"
                               class="form-input"
                               placeholder="boş bırakırsanız başlıktan üretilir"
                               style="font-family:monospace;font-size:13px">
                        <small class="form-help">URL: <code>siteniz.com/<strong>slug</strong></code></small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Anahtar Kelimeler</label>
                        <input type="text" name="keywords" value="{{ old('keywords', $sayfa->keywords ?? '') }}"
                               class="form-input"
                               placeholder="hakkımızda, şirket, hizmet">
                        <small class="form-help">Virgülle ayırarak yazın</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Meta Description</label>
                        <textarea name="description" rows="2" maxlength="160"
                                  class="form-textarea"
                                  onkeyup="updateCharCounter(this, 'descCounter', 160)"
                                  placeholder="Arama motorlarında görünen açıklama (max 160 karakter)">{{ old('description', $sayfa->description ?? '') }}</textarea>
                        <div class="char-counter" id="descCounter">0 / 160</div>
                    </div>
                </div>
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
                @if(!empty($sayfa->resim))
                    <div style="margin-bottom:10px">
                        <img src="{{ asset('tema/uploads/sayfalar/' . $sayfa->resim) }}"
                             alt="Mevcut görsel"
                             style="width:100%;border-radius:var(--radius-md);object-fit:cover;max-height:200px;border:1px solid var(--border)"
                             onerror="this.style.display='none'">
                        <small class="form-help" style="margin-top:6px">
                            <i data-lucide="info" style="width:12px;height:12px;display:inline;vertical-align:middle"></i>
                            Yeni görsel yüklersen eski yerine geçer.
                        </small>
                    </div>
                @endif

                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:28px;height:28px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">{{ !empty($sayfa->resim) ? 'Görseli değiştir' : 'Görsel yükle' }}</div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
                        JPG, PNG, WEBP — önerilen 1200×630
                    </div>
                    <img id="resimPreview" class="preview" style="display:none">
                    <input type="file" name="resim" id="resimInput" accept="image/*"
                           onchange="onImageChange(this)">
                </div>
            </div>

            {{-- AYARLAR --}}
            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Ayarlar</span>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Yayında</div>
                        <div class="desc">Sitede görünür</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', (int)($sayfa->durum ?? 1)) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Anasayfada Göster</div>
                        <div class="desc">Ana sayfada öne çıkarılır</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="anasayfa" value="1"
                               {{ old('anasayfa', (int)($sayfa->anasayfa ?? 0)) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.sayfalar.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="button" class="btn btn-ghost"
                    style="color:var(--danger)"
                    onclick="silSayfa()">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Değişiklikleri Kaydet</span>
            </button>
        </div>
    </div>
</form>

{{-- SİL FORMU (iç içe yasak) --}}
<form id="del-sayfa-form"
      action="{{ route('admin.sayfalar.sil', $sayfa->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function silSayfa() {
    if (confirm('Bu sayfayı silmek istediğinize emin misiniz?\n\n{{ addslashes($sayfa->adi ?? '') }}\n\nBu işlem geri alınamaz.')) {
        document.getElementById('del-sayfa-form').submit();
    }
}

// Dil tab switching
document.querySelectorAll('.lang-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const lang = this.dataset.lang;
        document.querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.lang-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.querySelector('[data-pane="' + lang + '"]').classList.add('active');
    });
});

// Resim preview
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

// Karakter sayacı
function updateCharCounter(textarea, counterId, max) {
    const counter = document.getElementById(counterId);
    if (!counter) return;
    const len = textarea.value.length;
    counter.textContent = len + ' / ' + max;
    counter.classList.toggle('warn', len > max * 0.85 && len <= max);
    counter.classList.toggle('over', len > max);
}
const desc = document.querySelector('[name="description"]');
if (desc) updateCharCounter(desc, 'descCounter', 160);
</script>

{{-- TinyMCE --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
function initRich(selector) {
    const isDark = document.body.classList.contains('theme-dark');
    const contentBg = isDark ? '#0a0a0a' : '#ffffff';
    const contentColor = isDark ? '#f1f5f9' : '#0f172a';

    tinymce.init({
        selector: selector,
        license_key: 'gpl',
        promotion: false,
        branding: false,
        height: 420,
        menubar: false,
        plugins: 'lists link image table code fullscreen autolink',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link image table | code fullscreen',
        fontsize_formats: '10px 12px 14px 16px 18px 20px 24px 28px 32px 40px',
        content_style: `body{font-family:Poppins,Inter,Arial,sans-serif;font-size:14px;background:${contentBg};color:${contentColor};padding:14px} a{color:#b8b62e}`,
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        language: 'tr',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@latest/langs7/tr.js'
    });
}
initRich('textarea.rich-full');

const themeBtn = document.getElementById('themeToggle');
if (themeBtn) {
    themeBtn.addEventListener('click', () => {
        setTimeout(() => {
            if (window.tinymce) {
                tinymce.remove();
                initRich('textarea.rich-full');
            }
        }, 100);
    });
}
</script>

@endsection