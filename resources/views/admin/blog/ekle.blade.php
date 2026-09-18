@extends('admin._layout')

@section('title', 'Yeni Blog Yazısı')

@push('head')
@include("admin._partials.form-css.blog")
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.blog.index') }}">Blog</a>
    <span class="sep">/</span>
    <span class="current">Yeni Yazı</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="file-plus-2"></i>
            Yeni Blog Yazısı
        </h1>
        <div class="page-subtitle">Yeni içerik üret, çevirilerle birlikte yayınla</div>
    </div>
</div>

<form action="{{ route('admin.blog.eklePost') }}" method="POST" enctype="multipart/form-data"
      onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf

    <div class="form-grid">
        <div>
            {{-- ÇOKLU DİL İÇERİK --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="globe"></i>
                    <span>Yazı İçeriği</span>
                    <span style="margin-left:auto;font-size:11px;color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0">
                        TR zorunlu — EN/AR opsiyonel
                    </span>
                </div>

                <div class="lang-tabs">
                    <button type="button" class="lang-tab active" data-lang="tr">🇹🇷 <span>Türkçe</span></button>
                    <button type="button" class="lang-tab" data-lang="en">🇬🇧 <span>English</span></button>
                    <button type="button" class="lang-tab" data-lang="ar">🇸🇦 <span>العربية</span></button>
                </div>

                {{-- TR --}}
                <div class="lang-pane active" data-pane="tr">
                    <div class="form-group">
                        <label class="form-label">Başlık <span class="required">*</span></label>
                        <input type="text" name="baslik" value="{{ old('baslik') }}"
                               required class="form-input"
                               placeholder="Yazı başlığı">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kısa Özet</label>
                        <textarea name="kisa" rows="2" class="form-textarea"
                                  placeholder="Yazının özet metni (liste sayfasında görünür)">{{ old('kisa') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">İçerik <span class="required">*</span></label>
                        <textarea name="icerik" class="rich-full">{{ old('icerik') }}</textarea>
                    </div>
                </div>

                {{-- EN --}}
                <div class="lang-pane" data-pane="en">
                    <div class="form-group">
                        <label class="form-label">Title (EN)</label>
                        <input type="text" name="adi_en" value="{{ old('adi_en') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Short summary (EN)</label>
                        <textarea name="ozet_en" rows="2" class="form-textarea">{{ old('ozet_en') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Content (EN)</label>
                        <textarea name="icerik_en" class="rich-full">{{ old('icerik_en') }}</textarea>
                    </div>
                </div>

                {{-- AR --}}
                <div class="lang-pane" data-pane="ar" dir="rtl">
                    <div class="form-group">
                        <label class="form-label">العنوان (AR)</label>
                        <input type="text" name="adi_ar" value="{{ old('adi_ar') }}" class="form-input" dir="rtl">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ملخص قصير (AR)</label>
                        <textarea name="ozet_ar" rows="2" class="form-textarea" dir="rtl">{{ old('ozet_ar') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">المحتوى (AR)</label>
                        <textarea name="icerik_ar" class="rich-full">{{ old('icerik_ar') }}</textarea>
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
                        <input type="text" name="seo" value="{{ old('seo') }}"
                               class="form-input"
                               placeholder="boş bırakırsanız başlıktan üretilir"
                               style="font-family:monospace;font-size:13px">
                        <small class="form-help">URL: <code>siteniz.com/blog/<strong>slug</strong></code></small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Anahtar Kelimeler</label>
                        <input type="text" name="keywords" value="{{ old('keywords') }}"
                               class="form-input"
                               placeholder="blog, içerik, makale">
                        <small class="form-help">Virgülle ayırarak yazın</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Meta Description</label>
                        <textarea name="description" rows="2" maxlength="160"
                                  class="form-textarea"
                                  onkeyup="updateCharCounter(this, 'descCounter', 160)"
                                  placeholder="Arama motorlarında görünen açıklama (max 160 karakter)">{{ old('description') }}</textarea>
                        <div class="char-counter" id="descCounter">0 / 160</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Kapak Görseli</span>
                </div>
                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:28px;height:28px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">Görsel yükle</div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
                        JPG, PNG, WEBP — önerilen 1200×630
                    </div>
                    <img id="resimPreview" class="preview" style="display:none">
                    <input type="file" name="resim" id="resimInput" accept="image/*"
                           onchange="onImageChange(this)">
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Yayın Ayarları</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Ana Dil</label>
                    <select name="dil" class="form-select">
                        @if(isset($diller) && count($diller) > 0)
                            @foreach($diller as $d)
                                @php
                                    $dId = $d->id ?? 1;
                                    $dAd = $d->ad ?? $d->adi ?? $d->isim ?? $d->name ?? $d->dil_adi ?? 'Türkçe';
                                @endphp
                                <option value="{{ $dId }}" {{ old('dil', 1) == $dId ? 'selected' : '' }}>
                                    {{ $dAd }}
                                </option>
                            @endforeach
                        @else
                            <option value="1" {{ old('dil', 1) == 1 ? 'selected' : '' }}>🇹🇷 Türkçe</option>
                            <option value="2" {{ old('dil') == 2 ? 'selected' : '' }}>🇬🇧 English</option>
                            <option value="3" {{ old('dil') == 3 ? 'selected' : '' }}>🇸🇦 العربية</option>
                        @endif
                    </select>
                    <small class="form-help">Bu yazının birincil yayın dili</small>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Yayında</div>
                        <div class="desc">Sitede görünür</div>
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
                    <p style="margin:0 0 8px">📝 <strong>Başlık</strong> arama sonuçlarında ilk görünen kısım — net ve çekici olmalı.</p>
                    <p style="margin:0 0 8px">🔍 <strong>Meta description</strong> arama motorlarında özet olarak görünür (max 160 karakter).</p>
                    <p style="margin:0">🖼️ <strong>Kapak görseli</strong> sosyal medya paylaşımlarında önizlenir.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.blog.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Yazıyı Kaydet</span>
            </button>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('.lang-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const lang = this.dataset.lang;
        document.querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.lang-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.querySelector('[data-pane="' + lang + '"]').classList.add('active');
    });
});

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