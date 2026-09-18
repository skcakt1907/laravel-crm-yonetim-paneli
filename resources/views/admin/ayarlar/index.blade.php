@extends('admin._layout')

@section('title', 'Genel Ayarlar')

@push('head')
@include('admin.ayarlar._partials.styles')
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Ayarlar — Genel</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="settings"></i>
            Genel Ayarlar
        </h1>
        <div class="page-subtitle">Site genel bilgileri, logo ve markalama</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'genel'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.guncelle') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- SİTE BİLGİLERİ --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="globe"></i>
                    <span>Site Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Site Başlığı</label>
                        <input type="text" name="site_baslik"
                               value="{{ old('site_baslik', $ayarlar->site_baslik ?? '') }}"
                               class="form-input"
                               placeholder="Örn: İş Ortağım">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Site URL</label>
                        <input type="url" name="site_url"
                               value="{{ old('site_url', $ayarlar->site_url ?? '') }}"
                               class="form-input"
                               placeholder="https://...">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Site Açıklaması (SEO)</label>
                        <textarea name="site_desc" rows="2" maxlength="160"
                                  class="form-textarea"
                                  onkeyup="updateCharCounter(this, 'descCount', 160)"
                                  placeholder="Arama motorlarında görünecek açıklama (max 160 karakter)">{{ old('site_desc', $ayarlar->site_desc ?? '') }}</textarea>
                        <div class="char-counter" id="descCount">0 / 160</div>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Anahtar Kelimeler</label>
                        <input type="text" name="site_keyw"
                               value="{{ old('site_keyw', $ayarlar->site_keyw ?? '') }}"
                               class="form-input"
                               placeholder="virgülle ayır: ekommerce, paket, hosting">
                    </div>
                </div>
            </div>

            {{-- FİRMA BİLGİLERİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="building-2"></i>
                    <span>Firma Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" name="firma_adi"
                               value="{{ old('firma_adi', $ayarlar->firma_adi ?? '') }}"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Copyright</label>
                        <input type="text" name="copyright"
                               value="{{ old('copyright', $ayarlar->copyright ?? '') }}"
                               class="form-input"
                               placeholder="© 2026 DN Kreatif">
                    </div>
                </div>
            </div>

            {{-- LOGOLAR --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Logolar ve Favicon</span>
                </div>

                <div class="form-grid">
                    {{-- Logo --}}
                    <div class="form-group">
                        <label class="form-label">Site Logosu</label>
                        @if(!empty($ayarlar->firma_logo))
                            <div style="margin-bottom:10px;padding:14px;background:var(--bg-subtle);border-radius:var(--radius-md);border:1px solid var(--border);text-align:center">
                                <img src="{{ asset('tema/uploads/logo/' . $ayarlar->firma_logo) }}"
                                     alt="Logo"
                                     style="max-width:100%;max-height:80px;object-fit:contain"
                                     onerror="this.style.display='none'">
                                <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:11.5px;color:var(--text-secondary);cursor:pointer;justify-content:center">
                                    <input type="checkbox" name="delete_firma_logo" value="1" style="margin:0">
                                    <span>Logoyu sil</span>
                                </label>
                            </div>
                        @endif
                        <div class="image-upload" onclick="document.getElementById('logoInput').click()">
                            <i data-lucide="upload" style="width:24px;height:24px;color:var(--brand-dark)"></i>
                            <div style="font-weight:600;margin-top:4px;font-size:13px">
                                {{ !empty($ayarlar->firma_logo) ? 'Logoyu değiştir' : 'Logo yükle' }}
                            </div>
                            <img id="logoPreview" class="preview" style="display:none">
                            <input type="file" name="firma_logo" id="logoInput" accept="image/*"
                                   onchange="prevImg(this, 'logoPreview')">
                        </div>
                    </div>

                    {{-- Footer Logo --}}
                    <div class="form-group">
                        <label class="form-label">Footer Logosu</label>
                        @if(!empty($ayarlar->firma_footerlogo))
                            <div style="margin-bottom:10px;padding:14px;background:var(--bg-subtle);border-radius:var(--radius-md);border:1px solid var(--border);text-align:center">
                                <img src="{{ asset('tema/uploads/logo/' . $ayarlar->firma_footerlogo) }}"
                                     alt="Footer Logo"
                                     style="max-width:100%;max-height:80px;object-fit:contain"
                                     onerror="this.style.display='none'">
                                <label style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:11.5px;color:var(--text-secondary);cursor:pointer;justify-content:center">
                                    <input type="checkbox" name="delete_firma_footerlogo" value="1" style="margin:0">
                                    <span>Footer logoyu sil</span>
                                </label>
                            </div>
                        @endif
                        <div class="image-upload" onclick="document.getElementById('flogoInput').click()">
                            <i data-lucide="upload" style="width:24px;height:24px;color:var(--brand-dark)"></i>
                            <div style="font-weight:600;margin-top:4px;font-size:13px">
                                {{ !empty($ayarlar->firma_footerlogo) ? 'Değiştir' : 'Yükle' }}
                            </div>
                            <img id="flogoPreview" class="preview" style="display:none">
                            <input type="file" name="firma_footerlogo" id="flogoInput" accept="image/*"
                                   onchange="prevImg(this, 'flogoPreview')">
                        </div>
                    </div>

                    {{-- Favicon --}}
                    <div class="form-group full">
                        <label class="form-label">Favicon (Tarayıcı sekme ikonu)</label>
                        @if(!empty($ayarlar->favicon))
                            <div style="margin-bottom:10px;display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);border:1px solid var(--border)">
                                <img src="{{ asset('tema/uploads/logo/' . $ayarlar->favicon) }}"
                                     alt="Favicon"
                                     style="width:32px;height:32px;object-fit:contain"
                                     onerror="this.style.display='none'">
                                <div style="flex:1;min-width:0">
                                    <div style="font-weight:600;font-size:13px">Mevcut favicon</div>
                                    <div style="font-size:11.5px;color:var(--text-muted);font-family:monospace">{{ $ayarlar->favicon }}</div>
                                </div>
                                <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text-secondary);cursor:pointer">
                                    <input type="checkbox" name="delete_favicon" value="1" style="margin:0">
                                    <span>Sil</span>
                                </label>
                            </div>
                        @endif
                        <input type="file" name="favicon" accept="image/x-icon,image/png,image/jpeg" class="form-input">
                        <small class="form-help">Önerilen: 32x32 PNG veya .ico</small>
                    </div>
                </div>
            </div>

            {{-- SEO + ANALİTİK --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="bar-chart-3"></i>
                    <span>Analitik ve Doğrulama</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Google Analytics Kodu</label>
                        <textarea name="google_analytics" rows="3" class="form-textarea"
                                  placeholder="<!-- Google tag (gtag.js) ile başlayan kod -->"
                                  style="font-family:monospace;font-size:12.5px">{{ old('google_analytics', $ayarlar->google_analytics ?? '') }}</textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Site Doğrulama Kodları (Google/Bing/Yandex meta)</label>
                        <textarea name="dogrulama_kodu" rows="3" class="form-textarea"
                                  placeholder='<meta name="google-site-verification" content="...">'
                                  style="font-family:monospace;font-size:12.5px">{{ old('dogrulama_kodu', $ayarlar->dogrulama_kodu ?? '') }}</textarea>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Canlı Destek Scripti (Tawk.to / Crisp vb.)</label>
                        <textarea name="canli_destek" rows="3" class="form-textarea"
                                  placeholder="<script>...</script>"
                                  style="font-family:monospace;font-size:12.5px">{{ old('canli_destek', $ayarlar->canli_destek ?? '') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">reCAPTCHA Site Key</label>
                        <input type="text" name="rcaptha"
                               value="{{ old('rcaptha', $ayarlar->rcaptha ?? '') }}"
                               class="form-input"
                               placeholder="6Lc..."
                               style="font-family:monospace;font-size:12.5px">
                    </div>
                </div>
            </div>

            {{-- TEMA + DİL + VERGI --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="palette"></i>
                    <span>Tema ve Mali Ayarlar</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Varsayılan Dil</label>
                        <select name="site_dil" class="form-select">
                            <option value="tr" {{ old('site_dil', $ayarlar->site_dil ?? 'tr') == 'tr' ? 'selected' : '' }}>🇹🇷 Türkçe</option>
                            <option value="en" {{ old('site_dil', $ayarlar->site_dil ?? '') == 'en' ? 'selected' : '' }}>🇬🇧 English</option>
                            <option value="ar" {{ old('site_dil', $ayarlar->site_dil ?? '') == 'ar' ? 'selected' : '' }}>🇸🇦 العربية</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Site Teması</label>
                        <input type="text" name="site_tema"
                               value="{{ old('site_tema', $ayarlar->site_tema ?? 'default') }}"
                               class="form-input"
                               placeholder="default">
                    </div>

                    <div class="form-group">
                        <label class="form-label">KDV Oranı (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="kdv"
                               value="{{ old('kdv', $ayarlar->kdv ?? 20) }}"
                               class="form-input">
                    </div>

                    <div class="form-group" style="display:flex;align-items:end">
                        <div class="toggle-card" style="width:100%;margin:0">
                            <div>
                                <div class="lbl-strong">Demo Modu</div>
                                <div class="desc">Test/sandbox modu</div>
                            </div>
                            <label class="ios-toggle">
                                <input type="checkbox" name="demo" value="1"
                                       {{ old('demo', (int)($ayarlar->demo ?? 0)) ? 'checked' : '' }}>
                                <span class="knob"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Tüm ayar bölümleri ayrı kaydedilir
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Genel Ayarları Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function prevImg(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const preview = document.getElementById(previewId);
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
const desc = document.querySelector('[name="site_desc"]');
if (desc) updateCharCounter(desc, 'descCount', 160);
</script>

@endsection