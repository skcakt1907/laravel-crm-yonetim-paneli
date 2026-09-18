@extends('admin._layout')

@section('title', 'Paket Düzenle')

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
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        backdrop-filter: blur(10px);
        z-index: 10;
    }

    select[multiple] { min-height: 110px; padding: 8px !important; }

    /* Kapak görseli önizleme */
    .cover-preview {
        display: flex; align-items: center; gap: 14px;
        padding: 12px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
        margin-bottom: 10px;
    }
    .cover-preview img {
        width: 120px; height: 76px;
        object-fit: cover;
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
    }

    /* İçerik resim galerisi */
    .ic-resim-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill,minmax(140px,1fr));
        gap: 10px;
    }
    .ic-resim-item {
        position: relative;
        border-radius: var(--radius-md);
        overflow: hidden;
        border: 1px solid var(--border);
    }
    .ic-resim-item img {
        width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block;
    }
    .ic-resim-item button {
        position: absolute; top: 6px; right: 6px;
        width: 28px; height: 28px; border-radius: 50%;
        background: rgba(239,68,68,0.92); color: #fff;
        border: none; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: transform 0.15s;
    }
    .ic-resim-item button:hover { transform: scale(1.1); }

    .tox-tinymce { border-color: var(--border) !important; border-radius: var(--radius-md) !important; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.paketler.index') }}">Paketler</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($paket->adi ?? 'Paket Düzenle', 40) }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Paket Düzenle
        </h1>
        <div class="page-subtitle">
            <strong style="color:var(--text)">{{ $paket->adi ?? '—' }}</strong>
            · #{{ $paket->id }}
        </div>
    </div>
    <div class="page-actions">
        {{-- Önceki / Sonraki navigasyon --}}
        @if(!empty($prevPaket))
            <a href="{{ route('admin.paketler.duzenle', $prevPaket->id) }}"
               class="btn btn-secondary btn-sm" title="Önceki: {{ $prevPaket->adi }}">
                <i data-lucide="chevron-left"></i>
                <span>Önceki</span>
            </a>
        @endif
        @if(!empty($nextPaket))
            <a href="{{ route('admin.paketler.duzenle', $nextPaket->id) }}"
               class="btn btn-secondary btn-sm" title="Sonraki: {{ $nextPaket->adi }}">
                <span>Sonraki</span>
                <i data-lucide="chevron-right"></i>
            </a>
        @endif
    </div>
</div>

<form action="{{ route('admin.paketler.duzenlePost', $paket->id) }}" method="POST" enctype="multipart/form-data"
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
                                    @if((string)($paket->kategori ?? '') === (string)($k->id ?? $k)) selected @endif>
                                    {{ $k->adi ?? $k }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-help">Ctrl/Cmd ile birden fazla seçilebilir</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fiyat (₺) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="tutar"
                               value="{{ old('tutar', $paket->tutar ?? '') }}"
                               required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sıra</label>
                        <input type="number" name="sira"
                               value="{{ old('sira', $paket->sira ?? 0) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SEO Slug</label>
                        <input type="text" name="seo"
                               value="{{ old('seo', $paket->seo ?? '') }}"
                               placeholder="paket-adi" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Demo Link</label>
                        <input type="text" inputmode="url" name="demo_link"
                               value="{{ old('demo_link', $paket->demo_link ?? '') }}"
                               placeholder="https://demo..." class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Demo Admin Link</label>
                        <input type="text" inputmode="url" name="demo_admin_link"
                               value="{{ old('demo_admin_link', $paket->demo_admin_link ?? '') }}"
                               class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Etiketler</label>
                        <input type="text" name="etiketler"
                               value="{{ old('etiketler', $paket->etiketler ?? '') }}"
                               placeholder="web, kurumsal, e-ticaret" class="form-input">
                    </div>
                </div>
            </div>

            {{-- KAPAK GÖRSELİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Kapak Görseli</span>
                </div>

                @php
                    $resimUrl = null;
                    if (!empty($paket->resim)) {
                        foreach (['tema/uploads/webpaketleri/kapak/', 'tema/uploads/webpaketleri/', 'tema/uploads/webpaketleri/kucuk/'] as $d) {
                            if (file_exists(public_path($d . $paket->resim))) {
                                $resimUrl = asset($d . $paket->resim);
                                break;
                            }
                        }
                    }
                @endphp

                @if($resimUrl)
                    <div class="cover-preview">
                        <img src="{{ $resimUrl }}" alt="">
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:600;font-size:13px">Mevcut Kapak</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;font-family:monospace;word-break:break-all">
                                {{ $paket->resim }}
                            </div>
                        </div>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--danger);font-size:13px;font-weight:600">
                            <input type="checkbox" name="delete_resim" value="1" style="accent-color:var(--danger)">
                            <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                            <span>Sil</span>
                        </label>
                    </div>
                @endif

                <div class="form-group" style="margin:0">
                    <input type="file" name="resim" accept="image/*" class="form-input">
                    <small class="form-help">
                        @if($resimUrl)
                            Yeni resim yüklersen eskisi değişir. Sadece silmek için yukarıdaki "Sil"i işaretle.
                        @else
                            PNG / JPG / WebP — kart görseli olarak kullanılır
                        @endif
                    </small>
                </div>
            </div>

            {{-- ÇOKLU DİL TAB'LARI --}}
            <div style="margin-top:16px">
                @include('admin.partials.ceviri-tabs', [
                    'tablo'     => 'yazilimlar',
                    'kayit_id'  => $paket->id ?? null,
                    'tr_values' => [
                        'adi'      => $paket->adi ?? '',
                        'kisa'     => $paket->kisa ?? '',
                        'aciklama' => $paket->aciklama ?? '',
                        'ozellik'  => $paket->ozellik ?? '',
                    ],
                    'fields'    => [
                        'adi'      => ['label' => 'Paket Adı', 'type' => 'text', 'required' => true],
                        'kisa'     => ['label' => 'Kısa Açıklama', 'type' => 'textarea', 'rows' => 3],
                        'aciklama' => ['label' => 'Detaylı Açıklama', 'type' => 'wysiwyg', 'rows' => 6],
                        'ozellik'  => ['label' => 'Özellikler (her satır bir özellik)', 'type' => 'textarea', 'rows' => 6, 'placeholder' => "✓ Sınırsız sayfa\n✓ SSL Dahil\n✓ Mobil uyumlu"],
                    ],
                ])
            </div>

            {{-- İÇERİK RESİMLERİ --}}
            @if(!empty($icerikResimleri) && count($icerikResimleri) > 0)
                <div class="section" style="margin-top:16px">
                    <div class="section-title">
                        <i data-lucide="images"></i>
                        <span>İçerik Görselleri</span>
                        <span class="badge badge-neutral" style="margin-left:auto;font-size:11px">
                            {{ count($icerikResimleri) }} adet
                        </span>
                    </div>
                    <div class="ic-resim-grid">
                        @foreach($icerikResimleri as $ir)
                            @php
                                $irUrl = null;
                                foreach (['tema/uploads/webpaketleri/icerik/', 'tema/uploads/webpaketleri/'] as $dd) {
                                    if (file_exists(public_path($dd . ($ir->resim ?? '')))) {
                                        $irUrl = asset($dd . $ir->resim);
                                        break;
                                    }
                                }
                            @endphp
                            <div class="ic-resim-item" id="ic-resim-{{ $ir->id }}">
                                @if($irUrl)
                                    <img src="{{ $irUrl }}" alt="">
                                @else
                                    <div style="display:flex;align-items:center;justify-content:center;aspect-ratio:4/3;background:var(--bg-subtle);color:var(--text-muted);font-size:12px">
                                        Bulunamadı
                                    </div>
                                @endif
                                <button type="button" title="Sil"
                                        onclick="silIcerikResim({{ $ir->id }})">
                                    <i data-lucide="x" style="width:14px;height:14px"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <small class="form-help" style="margin-top:10px;display:block">
                        Silinen resimler hemen kaldırılır, kaydet butonuna basmaya gerek yoktur.
                    </small>
                </div>
            @endif

            {{-- YENİ İÇERİK RESİMLERİ EKLE --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="image-plus"></i>
                    <span>Yeni İçerik Görseli Ekle</span>
                </div>
                <div class="form-group">
                    <input type="file" name="icerik_resimleri[]" multiple accept="image/*" class="form-input">
                    <small class="form-help">En fazla 5 görsel, her biri max 10MB</small>
                </div>
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
                               {{ old('durum', $paket->durum ?? 1) ? 'checked' : '' }}>
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
                               {{ old('anasayfa', $paket->anasayfa ?? 0) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            {{-- PAKET BİLGİLERİ --}}
            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Paket Bilgisi</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="lbl">ID</span>
                        <span class="val">#{{ $paket->id }}</span>
                    </div>
                    @if(!empty($paket->tarih))
                        @php
                            $tFmt = null;
                            try { $tFmt = \Carbon\Carbon::parse($paket->tarih)->format('d.m.Y H:i'); }
                            catch (\Throwable $e) {}
                        @endphp
                        <div class="info-item">
                            <span class="lbl">Oluşturma</span>
                            <span class="val">{{ $tFmt ?? '—' }}</span>
                        </div>
                    @endif
                    @if(isset($paket->kategori))
                        <div class="info-item">
                            <span class="lbl">Mevcut Kategori ID</span>
                            <span class="val">{{ $paket->kategori }}</span>
                        </div>
                    @endif
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
                <span>Değişiklikleri Kaydet</span>
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

// Tema toggle ile uyumlu reinit
const themeBtn = document.getElementById('themeToggle');
if (themeBtn) {
    themeBtn.addEventListener('click', () => {
        setTimeout(() => {
            if (window.tinymce) {
                tinymce.remove();
                initRich('textarea.rich-full');
                initRich('textarea.rich-mini', { height: 180 });
                initRich('textarea.rich-list', { height: 240 });
            }
        }, 100);
    });
}

// İçerik resim silme (AJAX)
function silIcerikResim(id) {
    if (!confirm('Bu içerik görseli silinsin mi?')) return;

    const url = '{{ url("/admin/paketler/icerik-resim-sil") }}/' + id;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
            'X-HTTP-Method-Override': 'DELETE'
        },
        body: new URLSearchParams({ _method: 'DELETE', _token: csrf })
    })
    .then(r => {
        const el = document.getElementById('ic-resim-' + id);
        if (el) el.remove();
    })
    .catch(err => {
        console.error('Silme hatası:', err);
        alert('Silme işleminde hata oluştu.');
    });
}
</script>

@endsection