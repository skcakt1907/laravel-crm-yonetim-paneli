@extends('admin._layout')

@section('title', 'Kampanya Düzenle')

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
        padding: 20px;
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
    .image-upload .preview {
        max-width: 100%; max-height: 220px;
        border-radius: var(--radius-md);
        margin: 10px auto 4px;
        display: block;
        object-fit: cover;
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

    .info-card {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 12px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-bottom: 8px;
    }
    .info-card .ic-wrap {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .info-card .info-body { flex: 1; min-width: 0; }
    .info-card .info-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .info-card .info-value { font-weight: 600; font-size: 13px; color: var(--text); font-family: monospace; }
</style>
@endpush

@section('content')

@php
    // Mevcut değerleri Carbon'a dönüştür (form input için)
    $bsTarihi = null;
    $btTarihi = null;
    try { if (!empty($kampanya->baslangic_tarihi)) $bsTarihi = \Carbon\Carbon::parse($kampanya->baslangic_tarihi)->format('Y-m-d'); }
    catch (\Throwable $e) {}
    try { if (!empty($kampanya->bitis_tarihi)) $btTarihi = \Carbon\Carbon::parse($kampanya->bitis_tarihi)->format('Y-m-d'); }
    catch (\Throwable $e) {}

    // Oluşturma tarihi
    $createdFmt = null;
    try { if (!empty($kampanya->created_at)) $createdFmt = \Carbon\Carbon::parse($kampanya->created_at)->format('d.m.Y H:i'); }
    catch (\Throwable $e) {}

    // Güncelleme tarihi
    $updatedFmt = null;
    try { if (!empty($kampanya->updated_at)) $updatedFmt = \Carbon\Carbon::parse($kampanya->updated_at)->diffForHumans(); }
    catch (\Throwable $e) {}

    // Süre durumu
    $sureLabel = null;
    $sureClass = null;
    try {
        if (!empty($kampanya->bitis_tarihi)) {
            $bitisCarbon = \Carbon\Carbon::parse($kampanya->bitis_tarihi);
            $kalan = (int) floor(now()->diffInDays($bitisCarbon, false));
            if ($kalan < 0) {
                $sureLabel = abs($kalan) . ' gün önce bitti';
                $sureClass = 'danger';
            } elseif ($kalan <= 7) {
                $sureLabel = $kalan . ' gün kaldı';
                $sureClass = 'warning';
            } else {
                $sureLabel = $kalan . ' gün kaldı';
                $sureClass = 'success';
            }
        }
    } catch (\Throwable $e) {}
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.kampanyalar.index') }}">Kampanyalar</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($kampanya->baslik ?? 'Düzenle', 40) }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Kampanya Düzenle
            @if($sureLabel)
                <span class="badge badge-{{ $sureClass }}" style="font-size:11.5px;vertical-align:middle;margin-left:6px">
                    @if($sureClass === 'danger')<i data-lucide="x-circle" style="width:11px;height:11px"></i>
                    @elseif($sureClass === 'warning')<i data-lucide="alert-triangle" style="width:11px;height:11px"></i>
                    @else<i data-lucide="check-circle" style="width:11px;height:11px"></i>@endif
                    {{ $sureLabel }}
                </span>
            @endif
        </h1>
        <div class="page-subtitle">{{ $kampanya->baslik ?? '—' }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.kampanyalar.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.kampanyalar.duzenlePost', $kampanya->id) }}"
      method="POST" enctype="multipart/form-data"
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
                        <input type="text" name="baslik"
                               value="{{ old('baslik', $kampanya->baslik ?? '') }}"
                               required class="form-input">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" rows="6" class="form-textarea rich-full">{{ old('aciklama', $kampanya->aciklama ?? '') }}</textarea>
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
                               value="{{ old('baslangic_tarihi', $bsTarihi) }}"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="date" name="bitis_tarihi"
                               value="{{ old('bitis_tarihi', $btTarihi) }}"
                               class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">İndirim Oranı</label>
                        <div class="indirim-input">
                            <input type="number" step="0.01" min="0" max="100" name="indirim"
                                   value="{{ old('indirim', $kampanya->indirim ?? 0) }}"
                                   class="form-input">
                        </div>
                        <small class="form-help">0–100 arasında.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', $kampanya->sira ?? 0) }}"
                               class="form-input">
                        <small class="form-help">Düşük sayı önce gösterilir</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Hedef Link (opsiyonel)</label>
                        <input type="url" name="link"
                               value="{{ old('link', $kampanya->link ?? '') }}"
                               class="form-input"
                               placeholder="https://...">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">SEO Slug</label>
                        <input type="text" name="seo"
                               value="{{ old('seo', $kampanya->seo ?? '') }}"
                               class="form-input"
                               style="font-family:monospace;font-size:13px">
                        <small class="form-help">URL'de kullanılır. Değiştirirsen otomatik unique kontrolü yapılır.</small>
                    </div>
                </div>
            </div>

            {{-- ÇOKLU DİL --}}
            <div style="margin-top:16px">
                @include('admin.partials.ceviri-tabs', [
                    'tablo'     => 'kampanyalar',
                    'kayit_id'  => $kampanya->id,
                    'tr_values' => [
                        'baslik'   => $kampanya->baslik ?? '',
                        'aciklama' => $kampanya->aciklama ?? '',
                    ],
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

                @if(!empty($kampanya->resim))
                    <div style="margin-bottom:10px">
                        <img src="{{ asset('tema/uploads/kampanyalar/' . $kampanya->resim) }}"
                             alt="Mevcut görsel"
                             style="width:100%;border-radius:var(--radius-md);object-fit:cover;max-height:200px"
                             onerror="this.style.display='none'">
                        <small class="form-help" style="margin-top:6px">
                            <i data-lucide="info" style="width:12px;height:12px;display:inline;vertical-align:middle"></i>
                            Yeni görsel yüklersen eski silinir.
                        </small>
                    </div>
                @endif

                <div class="image-upload" onclick="document.getElementById('resimInput').click()">
                    <i data-lucide="image-plus" style="width:28px;height:28px;color:var(--brand-dark)"></i>
                    <div style="font-weight:600;margin-top:6px">
                        {{ !empty($kampanya->resim) ? 'Görseli değiştir' : 'Görsel yükle' }}
                    </div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px">
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
                               {{ old('durum', (int)($kampanya->durum ?? 1)) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            {{-- KAYIT BİLGİSİ --}}
            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Kayıt Bilgisi</span>
                </div>

                <div class="info-card">
                    <div class="ic-wrap"><i data-lucide="hash" style="width:14px;height:14px"></i></div>
                    <div class="info-body">
                        <div class="info-label">ID</div>
                        <div class="info-value">#{{ $kampanya->id }}</div>
                    </div>
                </div>

                @if(!empty($kampanya->seo))
                    <div class="info-card">
                        <div class="ic-wrap"><i data-lucide="link" style="width:14px;height:14px"></i></div>
                        <div class="info-body">
                            <div class="info-label">Slug</div>
                            <div class="info-value" style="font-size:12px;word-break:break-all">{{ $kampanya->seo }}</div>
                        </div>
                    </div>
                @endif

                @if($createdFmt)
                    <div class="info-card">
                        <div class="ic-wrap"><i data-lucide="calendar-plus" style="width:14px;height:14px"></i></div>
                        <div class="info-body">
                            <div class="info-label">Oluşturuldu</div>
                            <div class="info-value" style="font-size:12px">{{ $createdFmt }}</div>
                        </div>
                    </div>
                @endif

                @if($updatedFmt)
                    <div class="info-card">
                        <div class="ic-wrap"><i data-lucide="refresh-cw" style="width:14px;height:14px"></i></div>
                        <div class="info-body">
                            <div class="info-label">Son Güncelleme</div>
                            <div class="info-value" style="font-size:12px;font-family:Poppins">{{ $updatedFmt }}</div>
                        </div>
                    </div>
                @endif
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
            <button type="button" class="btn btn-ghost"
                    style="color:var(--danger)"
                    onclick="silKampanya()">
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
<form id="del-kampanya-form"
      action="{{ route('admin.kampanyalar.sil', $kampanya->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
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

function silKampanya() {
    if (confirm('Bu kampanyayı silmek istediğinize emin misiniz?\n\n{{ addslashes($kampanya->baslik ?? '') }}\n\nBu işlem geri alınamaz.')) {
        document.getElementById('del-kampanya-form').submit();
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