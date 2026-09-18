@extends('admin._layout')

@section('title', 'Yeni Bayi Ekle')

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

    /* Toggle switch */
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
        left: 3px; bottom: 3px;
        background: #fff;
        border-radius: 50%;
        transition: 0.25s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .ios-toggle input:checked + .knob {
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        border-color: var(--brand);
    }
    .ios-toggle input:checked + .knob:before { transform: translateX(22px); }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.bayiler.index') }}">Bayiler</a>
    <span class="sep">/</span>
    <span class="current">Yeni Bayi</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="user-plus"></i>
            Yeni Bayi Ekle
        </h1>
        <div class="page-subtitle">
            @if(!empty($crmMusteri))
                CRM müşterisi <strong style="color:var(--brand-dark)">{{ $crmMusteri->adi ?? '' }}</strong> için bayi oluştur
            @else
                Mevcut bir üyeyi bayi olarak yetkilendir veya yeni hesap oluştur
            @endif
        </div>
    </div>
</div>

<form action="{{ route('admin.bayiler.eklePost') }}" method="POST" id="ekleForm">
    @csrf
    @if(!empty($crmId))<input type="hidden" name="crm_id" value="{{ $crmId }}">@endif

    {{-- KAYIT TİPİ SEÇİMİ (sekme) --}}
    @php
        $kayitTipiOld = old('kayit_tipi', !empty($presecilenUyeId) ? 'mevcut' : 'mevcut');
    @endphp
    <input type="hidden" name="kayit_tipi" id="kayit_tipi" value="{{ $kayitTipiOld }}">

    <div class="section" style="margin-bottom:20px">
        <div class="tab-nav">
            <a href="javascript:void(0)" class="tab-nav-link {{ $kayitTipiOld === 'mevcut' ? 'active' : '' }}"
               data-tab="mevcut" onclick="setTab('mevcut')">
                <i data-lucide="user-check" style="width:16px;height:16px"></i>
                Mevcut Üyeyi Bayi Yap
            </a>
            <a href="javascript:void(0)" class="tab-nav-link {{ $kayitTipiOld === 'yeni' ? 'active' : '' }}"
               data-tab="yeni" onclick="setTab('yeni')">
                <i data-lucide="user-plus" style="width:16px;height:16px"></i>
                Yeni Hesap + Bayi Oluştur
            </a>
        </div>
    </div>

    <div class="form-grid">
        <div>
            {{-- TAB İÇERİĞİ: MEVCUT — AJAX Live Search --}}
            <div id="tab-mevcut" class="tab-content" style="{{ $kayitTipiOld === 'mevcut' ? '' : 'display:none' }}">
                <div class="section">
                    <div class="section-title">
                        <i data-lucide="user-check"></i>
                        <span>Mevcut Üye Seçimi</span>
                    </div>

                    {{-- Hidden field: gerçek uye_id (controller bunu okuyacak) --}}
                    @php
                        // Ön seçili üye varsa (CRM'den geliş vb.) ad/email göstermek için
                        $presecilenUye = null;
                        if (!empty($presecilenUyeId)) {
                            try {
                                $presecilenUye = \DB::table('uyeler')->where('id', $presecilenUyeId)->first();
                            } catch (\Throwable $e) {}
                        }
                    @endphp
                    <input type="hidden" name="uye_id" id="uye_id"
                           value="{{ old('uye_id', $presecilenUyeId ?? '') }}">

                    <div class="form-group" style="position:relative">
                        <label class="form-label">Üye Ara <span class="required">*</span></label>

                        {{-- SEÇİLİ ÜYE KARTI --}}
                        <div id="uye-secili-card"
                             style="display:{{ $presecilenUye ? 'flex' : 'none' }};
                                    align-items:center;gap:12px;
                                    padding:12px 14px;
                                    background:var(--brand-soft);
                                    border:1px solid rgba(184,182,46,0.3);
                                    border-radius:var(--radius-md);
                                    margin-bottom:10px">
                            <div id="uye-secili-avatar"
                                 style="width:36px;height:36px;border-radius:50%;
                                        background:linear-gradient(135deg,#b8b62e,#8a8a1f);
                                        color:#000;font-weight:700;font-size:14px;
                                        display:inline-flex;align-items:center;justify-content:center;
                                        flex-shrink:0">
                                @if($presecilenUye)
                                    {{ mb_strtoupper(mb_substr($presecilenUye->ad ?? '?', 0, 1, 'UTF-8'), 'UTF-8') }}
                                @endif
                            </div>
                            <div style="flex:1;min-width:0">
                                <div id="uye-secili-ad" style="font-weight:600;font-size:14px">
                                    @if($presecilenUye){{ trim(($presecilenUye->ad ?? '') . ' ' . ($presecilenUye->soyad ?? '')) }}@endif
                                </div>
                                <div id="uye-secili-email"
                                     style="font-size:12px;color:var(--text-muted);margin-top:2px">
                                    @if($presecilenUye){{ $presecilenUye->email ?? '' }}@endif
                                </div>
                            </div>
                            <button type="button"
                                    onclick="uyeTemizle()"
                                    class="table-action" title="Seçimi temizle"
                                    style="color:var(--danger)">
                                <i data-lucide="x"></i>
                            </button>
                        </div>

                        {{-- ARAMA KUTUSU --}}
                        <div id="uye-arama-wrap" style="position:relative;{{ $presecilenUye ? 'display:none' : '' }}">
                            <input type="text" id="uye-arama-input"
                                   class="form-input"
                                   placeholder="İsim, e-posta veya telefon ile ara (en az 2 karakter)"
                                   autocomplete="off"
                                   style="padding-right:40px">
                            <div id="uye-arama-status"
                                 style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                        font-size:12px;color:var(--text-muted);pointer-events:none">
                                <i data-lucide="search" style="width:16px;height:16px"></i>
                            </div>

                            {{-- DROPDOWN SONUÇLARI --}}
                            <div id="uye-arama-dropdown"
                                 style="display:none;position:absolute;top:100%;left:0;right:0;
                                        margin-top:4px;max-height:320px;overflow-y:auto;
                                        background:var(--surface);
                                        border:1px solid var(--border);
                                        border-radius:var(--radius-md);
                                        box-shadow:0 10px 30px rgba(0,0,0,0.2);
                                        z-index:100">
                            </div>
                        </div>

                        <small class="form-help" style="margin-top:8px;display:block">
                            Sadece henüz bayi olmamış üyeler aranır. Sistemde yok ise "Yeni Hesap + Bayi Oluştur" sekmesini kullanın.
                        </small>
                    </div>
                </div>
            </div>

            {{-- TAB İÇERİĞİ: YENİ --}}
            <div id="tab-yeni" class="tab-content" style="{{ $kayitTipiOld === 'yeni' ? '' : 'display:none' }}">
                <div class="section">
                    <div class="section-title">
                        <i data-lucide="user-plus"></i>
                        <span>Yeni Üye Bilgileri</span>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Ad <span class="required">*</span></label>
                            <input type="text" name="yeni_ad" value="{{ old('yeni_ad') }}"
                                   class="form-input" placeholder="Ahmet">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Soyad</label>
                            <input type="text" name="yeni_soyad" value="{{ old('yeni_soyad') }}"
                                   class="form-input" placeholder="Yılmaz">
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-posta <span class="required">*</span></label>
                            <input type="email" name="yeni_email" value="{{ old('yeni_email') }}"
                                   class="form-input" placeholder="ornek@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="yeni_telefon" value="{{ old('yeni_telefon') }}"
                                   class="form-input" placeholder="0500 000 00 00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Şifre <span class="required">*</span></label>
                            <input type="text" name="yeni_sifre" value="{{ old('yeni_sifre') }}"
                                   class="form-input" placeholder="En az 6 karakter">
                            <small class="form-help">Bayinin giriş yapacağı şifre (en az 6 karakter)</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="yeni_kullanici_adi" value="{{ old('yeni_kullanici_adi') }}"
                                   class="form-input" placeholder="(opsiyonel)">
                            <small class="form-help">Boş bırakılabilir</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ORTAK BAYİ BİLGİLERİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="store"></i>
                    <span>Bayi Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Bayi Kodu</label>
                        <input type="text" name="bayi_kodu" value="{{ old('bayi_kodu') }}"
                               class="form-input" placeholder="BYI-2026-XXXX">
                        <small class="form-help">Boş bırakırsanız otomatik oluşturulur</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Komisyon Oranı (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="komisyon_orani"
                               value="{{ old('komisyon_orani', '10') }}"
                               min="0" max="100" required class="form-input">
                        <small class="form-help">Genel komisyon oranı</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Peşin Satış Komisyon (%)</label>
                        <input type="number" step="0.01" name="pesin_komisyon_orani"
                               value="{{ old('pesin_komisyon_orani', '0') }}"
                               min="0" max="100" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vadeli Satış Komisyon (%)</label>
                        <input type="number" step="0.01" name="vadeli_komisyon_orani"
                               value="{{ old('vadeli_komisyon_orani', '0') }}"
                               min="0" max="100" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Müşteri İndirim Oranı (%)</label>
                        <input type="number" step="0.01" name="musteri_indirim_orani"
                               value="{{ old('musteri_indirim_orani', '0') }}"
                               min="0" max="100" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min. Sepet Tutarı (₺)</label>
                        <input type="number" step="0.01" name="min_sepet_tutari"
                               value="{{ old('min_sepet_tutari', '0') }}"
                               min="0" class="form-input">
                    </div>
                </div>
            </div>

            {{-- ADRES BİLGİLERİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="map-pin"></i>
                    <span>Adres ve Vergi Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">İl</label>
                        <input type="text" name="il" value="{{ old('il', $crmMusteri->il ?? '') }}"
                               class="form-input" placeholder="İstanbul">
                    </div>
                    <div class="form-group">
                        <label class="form-label">İlçe</label>
                        <input type="text" name="ilce" value="{{ old('ilce', $crmMusteri->ilce ?? '') }}"
                               class="form-input" placeholder="Kadıköy">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vergi No</label>
                        <input type="text" name="vergi_no"
                               value="{{ old('vergi_no', $crmMusteri->vergi_no ?? '') }}"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="vergi_dairesi"
                               value="{{ old('vergi_dairesi', $crmMusteri->vergi_dairesi ?? '') }}"
                               class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Adres</label>
                        <textarea name="adres" rows="3" class="form-textarea"
                                  placeholder="Açık adres">{{ old('adres', $crmMusteri->adres ?? '') }}</textarea>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Adres Tarifi</label>
                        <textarea name="adres_tarifi" rows="2" class="form-textarea"
                                  placeholder="Yol tarifi, bina bilgileri, vb.">{{ old('adres_tarifi') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- BANKA BİLGİLERİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="landmark"></i>
                    <span>Banka Hesap Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Banka Adı</label>
                        <input type="text" name="banka_adi" value="{{ old('banka_adi') }}"
                               class="form-input" placeholder="Garanti BBVA">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hesap Sahibi</label>
                        <input type="text" name="hesap_sahibi" value="{{ old('hesap_sahibi') }}"
                               class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" value="{{ old('iban') }}"
                               class="form-input"
                               placeholder="TR00 0000 0000 0000 0000 0000 00"
                               maxlength="32"
                               style="font-family:'SF Mono','Monaco','Consolas',monospace;letter-spacing:0.5px">
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="shield-check"></i>
                    <span>Onay ve Durum</span>
                </div>

                <div class="toggle-card" style="margin-bottom:14px">
                    <div>
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Bayi sisteme giriş yapabilsin</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1" {{ old('durum', 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Onay Durumu</label>
                    <select name="onay_durumu" class="form-select">
                        <option value="1" {{ old('onay_durumu', '1') == '1' ? 'selected' : '' }}>Onaylı</option>
                        <option value="0" {{ old('onay_durumu') === '0' ? 'selected' : '' }}>Beklemede</option>
                    </select>
                    <small class="form-help">"Beklemede" seçilirse bayi sisteme giremez</small>
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.6">
                    <p style="margin:0 0 8px"><span class="required">*</span> ile işaretli alanlar zorunludur.</p>
                    <p style="margin:0 0 8px">Bayi eklenince sistem otomatik olarak bir bayi kodu üretebilir.</p>
                    <p style="margin:0">Komisyon oranları bayi başına özelleştirilebilir.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ALT AKSİYON --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:24px;flex-wrap:wrap">
        <a href="{{ route('admin.bayiler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Bayiyi Kaydet</span>
        </button>
    </div>
</form>

<script>
function setTab(tab) {
    document.getElementById('kayit_tipi').value = tab;

    // Tab linklerini güncelle
    document.querySelectorAll('.tab-nav-link[data-tab]').forEach(el => {
        if (el.dataset.tab === tab) el.classList.add('active');
        else el.classList.remove('active');
    });

    // Tab içeriklerini değiştir
    document.getElementById('tab-mevcut').style.display = (tab === 'mevcut') ? '' : 'none';
    document.getElementById('tab-yeni').style.display   = (tab === 'yeni')   ? '' : 'none';

    // Karşı taraftaki required field'ları geçici dummy yapmaya gerek yok — controller fallback yapıyor
}

/* ============================================================
   ÜYE AJAX LIVE SEARCH
   ============================================================ */
(function() {
   const SEARCH_URL = '{{ Route::has("admin.bayiler.ara-uye") ? route("admin.bayiler.ara-uye") : "" }}';
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const input    = document.getElementById('uye-arama-input');
    const dropdown = document.getElementById('uye-arama-dropdown');
    const status   = document.getElementById('uye-arama-status');
    const wrap     = document.getElementById('uye-arama-wrap');
    const card     = document.getElementById('uye-secili-card');
    const hidden   = document.getElementById('uye_id');

    if (!input) return; // sayfa farklı sekmedeyse JS hata vermesin

    let debounceTimer = null;
    let activeIdx = -1;
    let currentItems = [];

    function setStatus(html) {
        if (status) status.innerHTML = html;
    }

    function renderEmpty(msg) {
        dropdown.innerHTML = `
            <div style="padding:18px;text-align:center;color:var(--text-muted);font-size:13px">
                ${msg}
            </div>
        `;
        dropdown.style.display = 'block';
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function renderResults(items) {
        currentItems = items;
        activeIdx = -1;

        if (!items.length) {
            renderEmpty('Eşleşen üye bulunamadı. (Sadece bayi olmayan üyeler aranır.)');
            return;
        }

        dropdown.innerHTML = items.map((u, i) => `
            <div class="uye-arama-item"
                 data-idx="${i}"
                 style="display:flex;align-items:center;gap:12px;padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:background 0.12s">
                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#b8b62e,#8a8a1f);color:#000;font-weight:700;font-size:13px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                    ${escapeHtml(u.harf)}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;font-size:13.5px">${escapeHtml(u.ad)}</div>
                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">
                        ${escapeHtml(u.email)}
                        ${u.telefon ? ' · ' + escapeHtml(u.telefon) : ''}
                    </div>
                </div>
                <div style="font-size:11px;color:var(--text-muted);font-family:monospace">
                    #${u.id}
                </div>
            </div>
        `).join('');

        dropdown.style.display = 'block';

        // Click handler'ları bağla
        dropdown.querySelectorAll('.uye-arama-item').forEach(el => {
            el.addEventListener('click', () => selectItem(parseInt(el.dataset.idx)));
            el.addEventListener('mouseenter', () => {
                activeIdx = parseInt(el.dataset.idx);
                highlightActive();
            });
        });
    }

    function highlightActive() {
        dropdown.querySelectorAll('.uye-arama-item').forEach((el, i) => {
            if (i === activeIdx) {
                el.style.background = 'var(--brand-soft)';
            } else {
                el.style.background = '';
            }
        });
    }

    function selectItem(idx) {
        const u = currentItems[idx];
        if (!u) return;

        // Hidden uye_id doldur
        hidden.value = u.id;

        // Seçili kartı göster
        document.getElementById('uye-secili-avatar').textContent = u.harf;
        document.getElementById('uye-secili-ad').textContent = u.ad;
        document.getElementById('uye-secili-email').textContent = u.email + (u.telefon ? ' · ' + u.telefon : '');
        card.style.display = 'flex';

        // Arama kutusunu gizle
        wrap.style.display = 'none';
        dropdown.style.display = 'none';
        input.value = '';
    }

    function doSearch(q) {
        if (q.length < 2) {
            dropdown.style.display = 'none';
            setStatus('<i data-lucide="search" style="width:16px;height:16px"></i>');
            if (window.lucide) lucide.createIcons();
            return;
        }

        setStatus('<i data-lucide="loader-2" style="width:16px;height:16px;animation:spin 1s linear infinite"></i>');
        if (window.lucide) lucide.createIcons();

        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF
            }
        })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(data => {
            renderResults(data.items || []);
            setStatus('<i data-lucide="search" style="width:16px;height:16px"></i>');
            if (window.lucide) lucide.createIcons();
        })
        .catch(err => {
            console.error('Üye arama hatası:', err);
            renderEmpty('Arama sırasında hata oluştu. Sayfayı yenileyip tekrar deneyin.');
            setStatus('<i data-lucide="alert-triangle" style="width:16px;height:16px;color:var(--danger)"></i>');
            if (window.lucide) lucide.createIcons();
        });
    }

    // Input event — debounce
    input.addEventListener('input', e => {
        const q = e.target.value.trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => doSearch(q), 250);
    });

    // Klavye navigasyonu (↑ ↓ Enter Esc)
    input.addEventListener('keydown', e => {
        if (dropdown.style.display === 'none' || !currentItems.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, currentItems.length - 1);
            highlightActive();
            const el = dropdown.querySelector(`[data-idx="${activeIdx}"]`);
            if (el) el.scrollIntoView({block: 'nearest'});
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            highlightActive();
            const el = dropdown.querySelector(`[data-idx="${activeIdx}"]`);
            if (el) el.scrollIntoView({block: 'nearest'});
        } else if (e.key === 'Enter') {
            if (activeIdx >= 0) {
                e.preventDefault();
                selectItem(activeIdx);
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });

    // Focus → daha önce arama varsa tekrar göster
    input.addEventListener('focus', () => {
        if (currentItems.length && input.value.trim().length >= 2) {
            dropdown.style.display = 'block';
        }
    });

    // Dışına tıklayınca kapat
    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // "Seçimi temizle" globalde tanımlı
    window.uyeTemizle = function() {
        hidden.value = '';
        card.style.display = 'none';
        wrap.style.display = '';
        input.value = '';
        currentItems = [];
        dropdown.style.display = 'none';
        setTimeout(() => input.focus(), 50);
    };
})();
</script>

<style>
@keyframes spin { from { transform: translateY(-50%) rotate(0deg); } to { transform: translateY(-50%) rotate(360deg); } }
#uye-arama-status i[data-lucide="loader-2"] { animation: spin 1s linear infinite; }
.uye-arama-item:last-child { border-bottom: none !important; }
</style>

@endsection