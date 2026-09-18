@extends('admin._layout')

@section('title', 'Yeni Referans')

@push('head')
@include("admin._partials.form-css.referanslar")
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.referanslar.index') }}">Referanslar</a>
    <span class="sep">/</span>
    <span class="current">Yeni Referans</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="award"></i>
            Yeni Referans
        </h1>
        <div class="page-subtitle">Müşteri referansı ekleyin (firma logo + görüş)</div>
    </div>
</div>

<form action="{{ route('admin.referanslar.eklePost') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-grid">
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Referans Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Firma / Müşteri Adı <span class="required">*</span></label>
                        <input type="text" name="baslik" value="{{ old('baslik') }}"
                               required class="form-input"
                               placeholder="Örn: ABC Şirketi">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama / Referans Yazısı</label>
                        <textarea name="aciklama" rows="5" class="form-textarea"
                                  placeholder="Müşterinin görüşü, deneyimi, yorumu...">{{ old('aciklama') }}</textarea>
                        <small class="form-help">Sitede referans kartında görünür</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Web Sitesi</label>
                        <input type="url" name="url" value="{{ old('url') }}"
                               class="form-input"
                               placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', 0) }}"
                               class="form-input"
                               placeholder="0">
                        <small class="form-help">Düşük sayı önce gösterilir</small>
                    </div>
                </div>
            </div>

            {{-- ÇOKLU DİL --}}
            @if(View::exists('admin.partials.ceviri-tabs'))
            <div style="margin-top:16px">
                @include('admin.partials.ceviri-tabs', [
                    'tablo'     => 'referanslar',
                    'kayit_id'  => null,
                    'tr_values' => [],
                    'fields'    => [
                        'baslik'   => ['label' => 'Firma Adı', 'type' => 'text'],
                        'aciklama' => ['label' => 'Açıklama', 'type' => 'textarea', 'rows' => 5],
                    ],
                ])
            </div>
            @endif
        </div>

        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Logo / Görsel</span>
                </div>
                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:30px;height:30px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">Logo yükle</div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
                        JPG, PNG, WEBP — kare oran önerilen
                    </div>
                    <img id="resimPreview" class="preview" style="display:none">
                    <input type="file" name="resim" id="resimInput" accept="image/*"
                           onchange="onImageChange(this)">
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Görünürlük</span>
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
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.referanslar.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Referansı Kaydet</span>
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