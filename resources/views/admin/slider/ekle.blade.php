@extends('admin._layout')

@section('title', 'Yeni Slide')

@push('head')
@include("admin._partials.form-css.slider")
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.slider.index') }}">Slider</a>
    <span class="sep">/</span>
    <span class="current">Yeni Slide</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="image-plus"></i>
            Yeni Slide
        </h1>
        <div class="page-subtitle">Anasayfa slider'ına yeni görsel ekleyin</div>
    </div>
</div>

<form action="{{ route('admin.slider.eklePost') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-grid">
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Slide Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Başlık <span class="required">*</span></label>
                        <input type="text" name="adi" value="{{ old('adi') }}"
                               required class="form-input"
                               placeholder="Slide başlığı">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" rows="3" class="form-textarea"
                                  placeholder="Slide üzerinde gösterilecek açıklama metni">{{ old('aciklama') }}</textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Hedef URL</label>
                        <input type="url" name="url" value="{{ old('url') }}"
                               class="form-input"
                               placeholder="https://...">
                        <small class="form-help">Slide'a tıklandığında açılacak adres (opsiyonel)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', 0) }}"
                               class="form-input"
                               placeholder="0">
                        <small class="form-help">Düşük sayı önce görünür</small>
                    </div>
                </div>
            </div>
        </div>

        <div>
            {{-- RESİM --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Slide Görseli <span class="required">*</span></span>
                </div>
                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:32px;height:32px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">Görsel yükle</div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
                        JPG, PNG, WEBP — önerilen 1920×800
                    </div>
                    <img id="resimPreview" class="preview" style="display:none">
                    <input type="file" name="resim" id="resimInput" accept="image/*"
                           onchange="onImageChange(this)" required>
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
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Sitede görünür</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Yeni Sekmede Aç</div>
                        <div class="desc">URL açıldığında yeni pencere</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="sekme" value="1"
                               {{ old('sekme') ? 'checked' : '' }}>
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
            <a href="{{ route('admin.slider.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Slide'ı Kaydet</span>
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

@endsection