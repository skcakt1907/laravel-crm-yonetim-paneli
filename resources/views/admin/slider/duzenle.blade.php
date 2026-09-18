@extends('admin._layout')

@section('title', 'Slide Düzenle')

@push('head')
@include("admin._partials.form-css.slider")
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.slider.index') }}">Slider</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($slider->adi ?? 'Düzenle', 40) }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Slide Düzenle
        </h1>
        <div class="page-subtitle">{{ $slider->adi ?? '—' }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.slider.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.slider.duzenlePost', $slider->id) }}" method="POST" enctype="multipart/form-data">
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
                        <input type="text" name="adi" value="{{ old('adi', $slider->adi ?? '') }}"
                               required class="form-input"
                               placeholder="Slide başlığı">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" rows="3" class="form-textarea"
                                  placeholder="Slide üzerinde gösterilecek açıklama metni">{{ old('aciklama', $slider->aciklama ?? '') }}</textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Hedef URL</label>
                        <input type="url" name="url" value="{{ old('url', $slider->url ?? '') }}"
                               class="form-input"
                               placeholder="https://...">
                        <small class="form-help">Slide'a tıklandığında açılacak adres (opsiyonel)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', (int)($slider->sira ?? 0)) }}"
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
                @if(!empty($slider->resim))
                    <div style="margin-bottom:10px">
                        <img src="{{ asset('tema/uploads/slider/' . $slider->resim) }}"
                             alt="Mevcut görsel"
                             style="width:100%;border-radius:var(--radius-md);object-fit:cover;max-height:200px;border:1px solid var(--border)"
                             onerror="this.style.display='none'">
                        <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12.5px;color:var(--text-secondary);cursor:pointer">
                            <input type="checkbox" name="delete_resim" value="1" style="margin:0">
                            <span>Mevcut görseli sil</span>
                        </label>
                    </div>
                @endif

                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:32px;height:32px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">{{ !empty($slider->resim) ? 'Görseli değiştir' : 'Görsel yükle' }}</div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
                        JPG, PNG, WEBP — önerilen 1920×800
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
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Sitede görünür</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', (int)($slider->durum ?? 1)) ? 'checked' : '' }}>
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
                               {{ old('sekme', (int)($slider->sekme ?? 0)) ? 'checked' : '' }}>
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
            <button type="button" class="btn btn-ghost"
                    style="color:var(--danger)"
                    onclick="silSlider()">
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

{{-- SİL FORMU --}}
<form id="del-slider-form"
      action="{{ route('admin.slider.sil', $slider->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function silSlider() {
    if (confirm('Bu slide\'ı silmek istediğinize emin misiniz?\n\n{{ addslashes($slider->adi ?? '') }}')) {
        document.getElementById('del-slider-form').submit();
    }
}

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