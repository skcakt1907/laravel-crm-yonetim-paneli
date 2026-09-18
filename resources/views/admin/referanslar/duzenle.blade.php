@extends('admin._layout')

@section('title', 'Referans Düzenle')

@push('head')
@include("admin._partials.form-css.referanslar")
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.referanslar.index') }}">Referanslar</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($referans->adi ?? $referans->baslik ?? 'Düzenle', 40) }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Referans Düzenle
        </h1>
        <div class="page-subtitle">{{ $referans->adi ?? $referans->baslik ?? '—' }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.referanslar.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.referanslar.duzenlePost', $referans->id) }}" method="POST" enctype="multipart/form-data">
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
                        <input type="text" name="baslik" value="{{ old('baslik', $referans->adi ?? $referans->baslik ?? '') }}"
                               required class="form-input"
                               placeholder="Örn: ABC Şirketi">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama / Referans Yazısı</label>
                        <textarea name="aciklama" rows="5" class="form-textarea"
                                  placeholder="Müşterinin görüşü, deneyimi, yorumu...">{{ old('aciklama', $referans->aciklama ?? '') }}</textarea>
                        <small class="form-help">Sitede referans kartında görünür</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Web Sitesi</label>
                        <input type="url" name="url" value="{{ old('url', $referans->url ?? $referans->link ?? '') }}"
                               class="form-input"
                               placeholder="https://...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', (int)($referans->sira ?? 0)) }}"
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
                    'kayit_id'  => $referans->id,
                    'tr_values' => [
                        'baslik'   => $referans->adi ?? $referans->baslik ?? '',
                        'aciklama' => $referans->aciklama ?? '',
                    ],
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
                @php
                    $refResim = $referans->resim ?? $referans->logo ?? null;
                    if ($refResim && !str_starts_with($refResim, 'tema/')) {
                        $refResimPath = asset('tema/uploads/referanslar/' . $refResim);
                    } else {
                        $refResimPath = $refResim ? asset($refResim) : null;
                    }
                @endphp
                @if($refResimPath)
                    <div style="margin-bottom:10px">
                        <img src="{{ $refResimPath }}"
                             alt="Mevcut görsel"
                             style="width:100%;border-radius:var(--radius-md);object-fit:contain;max-height:180px;border:1px solid var(--border);background:var(--bg-subtle);padding:12px"
                             onerror="this.style.display='none'">
                        <small class="form-help" style="margin-top:6px">
                            <i data-lucide="info" style="width:12px;height:12px;display:inline;vertical-align:middle"></i>
                            Yeni görsel yüklersen eski yerine geçer.
                        </small>
                    </div>
                @endif

                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:30px;height:30px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">{{ $refResimPath ? 'Logoyu değiştir' : 'Logo yükle' }}</div>
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
                               {{ old('durum', (int)($referans->durum ?? 1)) ? 'checked' : '' }}>
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
            <button type="button" class="btn btn-ghost"
                    style="color:var(--danger)"
                    onclick="silRef()">
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
<form id="del-ref-form"
      action="{{ route('admin.referanslar.sil', $referans->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function silRef() {
    if (confirm('Bu referansı silmek istediğinize emin misiniz?\n\n{{ addslashes($referans->adi ?? $referans->baslik ?? '') }}')) {
        document.getElementById('del-ref-form').submit();
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