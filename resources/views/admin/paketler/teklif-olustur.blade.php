@extends('admin._layout')

@section('title', 'Teklif Oluştur')

@push('head')
<style>
    /* Müşteri arama + filtreli liste */
    .musteri-search-wrap { position: relative; }
    .musteri-liste {
        margin-top: 6px;
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        background: var(--surface);
        position: absolute;
        left: 0; right: 0;
        z-index: 40;
        display: none;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
    }
    .musteri-liste.open { display: block; }
    .musteri-liste-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--border);
        font-size: 13.5px;
        transition: background .12s;
    }
    .musteri-liste-item:last-child { border-bottom: none; }
    .musteri-liste-item:hover { background: var(--brand-soft); }
    .musteri-liste-item.selected {
        background: var(--brand-soft);
        border-left: 3px solid var(--brand);
        font-weight: 600;
    }
    .musteri-liste-item .mail { font-size: 11.5px; color: var(--text-muted); }
    .musteri-secili-bilgi {
        margin-top: 8px;
        padding: 10px 12px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.3);
        border-radius: var(--radius-md);
        font-size: 13px;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .musteri-secili-bilgi.show { display: flex; }
    .musteri-secili-bilgi .clear-btn {
        background: none; border: none; cursor: pointer;
        color: var(--danger); font-size: 12px; font-weight: 600;
    }

    /* Paketler collapse */
    .paket-collapse-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 14px 16px; cursor: pointer;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-md); transition: background .15s;
    }
    .paket-collapse-head:hover { background: var(--brand-soft); }
    .paket-collapse-head .chev { transition: transform .2s; color: var(--text-muted); }
    .paket-collapse-head.is-open .chev { transform: rotate(180deg); }
    .paket-collapse-body { display: none; margin-top: 12px; }
    .paket-collapse-body.is-open { display: block; }

    /* Paket arama (liste içinde) */
    .paket-ara-input { margin-bottom: 12px; }

    .pkt-secim-item {
        display: flex; align-items: center; gap: 12px; padding: 12px;
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-md); cursor: pointer; transition: all 0.15s;
    }
    .pkt-secim-item:hover { border-color: var(--brand-medium); }
    .pkt-secim-item.selected { background: var(--brand-soft); border-color: var(--brand); }
    .pkt-secim-item input[type="checkbox"] {
        width: 18px; height: 18px; accent-color: var(--brand); cursor: pointer; flex-shrink: 0;
    }
    .pkt-secim-body { flex: 1; min-width: 0; }
    .pkt-secim-name { font-weight: 600; font-size: 13.5px; line-height: 1.4; }
    .pkt-secim-price { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .ozel-fiyat-input {
        width: 100px !important; padding: 6px 10px !important;
        font-size: 13px !important; text-align: right !important;
    }
    .pkt-fiyat-ay-grup {
        display: flex; flex-direction: column; gap: 6px; flex-shrink: 0;
    }
    .pkt-ay-select {
        width: 100px !important; padding: 6px 8px !important;
        font-size: 12.5px !important;
    }

    /* Çoklu kategori seçim grid */
    .kat-coklu-grid {
        display: flex; flex-wrap: wrap; gap: 8px;
        max-height: 320px; overflow-y: auto;
        padding: 12px; border: 1px solid var(--border);
        border-radius: var(--radius-md); background: var(--surface);
    }
    .kat-coklu-item {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px; border: 1px solid var(--border);
        border-radius: 20px; cursor: pointer; font-size: 12.5px;
        user-select: none; transition: all 0.12s;
    }
    .kat-coklu-item:hover { border-color: var(--brand-medium); }
    .kat-coklu-item.selected { background: var(--brand-soft); border-color: var(--brand); color: var(--brand-dark); font-weight: 600; }
    .kat-coklu-item input { margin: 0; cursor: pointer; }

    .toplam-box {
        padding: 14px 16px; background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.3); border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: space-between; margin-top: 14px;
    }
    .toplam-box .lbl { font-size: 13px; color: var(--text-secondary); }
    .toplam-box .val { font-size: 20px; font-weight: 700; color: var(--brand-dark); }

    .sticky-save {
        position: sticky; bottom: 16px; background: var(--surface);
        border: 1px solid var(--border); border-radius: var(--radius-md);
        padding: 12px 16px; margin-top: 20px; display: flex;
        align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08); z-index: 10;
    }
</style>
@endpush

@section('content')

@php
    $duz = $duzenleMod ?? false;
    $t = $teklif ?? null;
    $seciliPkt = $seciliPaketler ?? [];  // [paket_id => birim_fiyat]
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.paketler.index') }}">Paketler</a>
    <span class="sep">/</span>
    <span class="current">{{ $duz ? 'Teklif Düzenle' : 'Yeni Teklif' }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="{{ $duz ? 'file-pen-line' : 'file-plus' }}"></i>
            {{ $duz ? 'Teklif Düzenle' : 'Yeni Teklif Oluştur' }}
        </h1>
        <div class="page-subtitle">{{ $duz ? 'Mevcut paket teklifini güncelle' : 'Müşteriye özel paket teklifi hazırla' }}</div>
    </div>
</div>

<form action="{{ $duz ? route('admin.paketler.teklif.guncelle', $t->id) : route('admin.paketler.teklif.store') }}" method="POST" enctype="multipart/form-data" id="teklifForm" onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf

    <div class="form-grid">
        {{-- SOL KOLON --}}
        <div>
            {{-- TEKLİF BİLGİLERİ --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="clipboard-list"></i>
                    <span>Teklif Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Teklif Başlığı <span class="required">*</span></label>
                        <input type="text" name="baslik" value="{{ old('baslik', $t->baslik ?? '') }}"
                               placeholder="Örn: Kurumsal Web Paketi Teklifi" class="form-input" required>
                    </div>

                    {{-- MÜŞTERİ: arama kutusu + filtreli liste --}}
                    <div class="form-group full">
                        <label class="form-label">Müşteri <span class="required">*</span></label>
                        <div class="musteri-search-wrap">
                            <input type="text" id="musteriArama" class="form-input"
                                   placeholder="Müşteri adı veya e-posta ile ara..." autocomplete="off">

                            {{-- Seçilen müşterinin gerçek değeri --}}
                            <input type="hidden" name="uye_id" id="uyeIdInput" value="{{ old('uye_id', $t->uye_id ?? '') }}" required>

                            <div class="musteri-secili-bilgi" id="musteriSeciliBilgi">
                                <span id="musteriSeciliText"></span>
                                <button type="button" class="clear-btn" id="musteriTemizle">✕ Değiştir</button>
                            </div>

                            <div class="musteri-liste" id="musteriListe">
                                @foreach($uyeler ?? [] as $u)
                                    @php
                                        $adSoyad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
                                        $arama = mb_strtolower($adSoyad . ' ' . ($u->email ?? ''), 'UTF-8');
                                    @endphp
                                    <div class="musteri-liste-item {{ old('uye_id', $t->uye_id ?? '') == $u->id ? 'selected' : '' }}"
                                         data-id="{{ $u->id }}"
                                         data-ad="{{ $adSoyad }}"
                                         data-email="{{ $u->email }}"
                                         data-arama="{{ $arama }}">
                                        {{ $adSoyad ?: 'İsimsiz' }}
                                        <span class="mail">{{ $u->email }}</span>
                                    </div>
                                @endforeach
                                <div class="musteri-liste-item" id="musteriBulunamadi" style="display:none;color:var(--text-muted);cursor:default">
                                    Eşleşen müşteri bulunamadı.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Para Birimi</label>
                        <select name="para_birimi" class="form-select">
                            @foreach(['TL','USD','EUR','AED'] as $pb)
                                <option value="{{ $pb }}" @if(old('para_birimi', $t->para_birimi ?? 'TL') == $pb) selected @endif>{{ $pb }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- MANUEL TUTAR --}}
                    <div class="form-group">
                        <label class="form-label">Tutar (₺)</label>
                        <input type="number" step="0.01" min="0" name="manuel_tutar" id="manuelTutar"
                               value="{{ old('manuel_tutar', ($duz && empty($seciliPkt)) ? ($t->toplam_tl ?? '') : '') }}" placeholder="0.00" class="form-input">
                        <small class="form-help">Paket seçmeden teklif için tutarı buraya girin. Paket de seçerseniz toplama eklenir.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teklif Dosyası</label>
                        <input type="file" name="dosya" accept=".pdf,.doc,.docx,.xls,.xlsx" class="form-input">
                        <small class="form-help">PDF, DOC, XLS — opsiyonel</small>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" rows="8" class="form-textarea rich-full"
                                  placeholder="Müşteriye özel notlar, indirim koşulları, geçerlilik süresi vb.">{{ old('aciklama', $t->aciklama ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- TEKLİF İÇERİĞİ (kapak / kategori / özellikler / galeri) --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Teklif İçeriği</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kategori <span style="font-weight:400;color:var(--text-muted);font-size:11px">(birden fazla seçilebilir)</span></label>
                        @php $seciliKat = $seciliKategoriler ?? (is_array(old('kategori')) ? array_map('intval', old('kategori')) : []); @endphp
                        <div class="kat-coklu-grid">
                            @forelse($kategoriler ?? [] as $k)
                                <label class="kat-coklu-item {{ in_array($k->id, $seciliKat) ? 'selected' : '' }}">
                                    <input type="checkbox" name="kategori[]" value="{{ $k->id }}" {{ in_array($k->id, $seciliKat) ? 'checked' : '' }}>
                                    <span>{{ $k->adi }}</span>
                                </label>
                            @empty
                                <div style="font-size:12px;color:var(--text-muted)">Kategori bulunamadı.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kapak Görseli</label>
                        <input type="file" name="resim" accept="image/*" class="form-input">
                        <small class="form-help">PNG / JPG / WebP — detay sayfasında üstte gösterilir</small>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Kısa Açıklama</label>
                        <input type="text" name="kisa" value="{{ old('kisa', $t->kisa ?? '') }}"
                               placeholder="Tek satırlık özet" class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Özellikler</label>
                        <textarea name="ozellik" rows="5" class="form-textarea"
                                  placeholder="Her satıra bir özellik yazın:&#10;Modern ve Şık Tasarım&#10;Full Responsive&#10;Gelişmiş Müşteri Paneli">{{ old('ozellik', $t->ozellik ?? '') }}</textarea>
                        <small class="form-help">Her satır, detay sayfasında bir madde olarak listelenir</small>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Galeri Görselleri</label>
                        <input type="file" name="galeri[]" accept="image/*" multiple class="form-input" id="galeriInput">
                        <small class="form-help">Birden fazla görsel seçebilirsiniz (en fazla 10). Detay sayfasında galeri olarak gösterilir.</small>
                        <div id="galeriOnizleme" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px"></div>
                    </div>
                </div>
            </div>

            {{-- PAKET SEÇİMİ (collapse — opsiyonel) --}}
            <div class="section" style="margin-top:16px">
                <div class="paket-collapse-head" id="paketCollapseHead" data-paket-toggle>
                    <div style="display:flex;align-items:center;gap:8px">
                        <i data-lucide="package"></i>
                        <span style="font-weight:600">Paket Ekle (opsiyonel)</span>
                        <span class="badge badge-neutral" style="font-size:11px">
                            <span id="secilenPaketSayisi">0</span> seçili
                        </span>
                    </div>
                    <i data-lucide="chevron-down" class="chev"></i>
                </div>

                <div class="paket-collapse-body" id="paketCollapseBody">
                    @if(empty($paketler) || count($paketler) === 0)
                        <div class="empty-state">
                            <i data-lucide="package-x" class="empty-state-icon"></i>
                            <h4>Paket yok</h4>
                            <p>Sistemde tanımlı aktif paket bulunmuyor.</p>
                        </div>
                    @else
                        <input type="text" id="paketArama" class="form-input paket-ara-input"
                               placeholder="Paket adı ile ara..." autocomplete="off">

                        <div id="paketGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:10px">
                            @foreach($paketler ?? [] as $p)
                                @php
                                    $oldPaketler = old('paketler');
                                    if (is_array($oldPaketler)) {
                                        // Form geri geldi (validation hatası) → old kullan
                                        $isSel = in_array($p->id, $oldPaketler);
                                        $oldOzelFiyat = old('paket_fiyat.' . $p->id);
                                        $oldAy = old('paket_ay.' . $p->id, 1);
                                    } else {
                                        // İlk açılış: düzenleme modunda seçili paketlerden, değilse boş
                                        $isSel = array_key_exists($p->id, $seciliPkt);
                                        $oldOzelFiyat = $isSel ? ($seciliPkt[$p->id]['fiyat'] ?? '') : null;
                                        $oldAy = $isSel ? ($seciliPkt[$p->id]['ay'] ?? 1) : 1;
                                    }
                                @endphp
                                <label class="pkt-secim-item {{ $isSel ? 'selected' : '' }}" data-pkt-item
                                       data-pkt-ad="{{ mb_strtolower($p->adi ?? '', 'UTF-8') }}">
                                    <input type="checkbox" name="paketler[]" value="{{ $p->id }}"
                                           {{ $isSel ? 'checked' : '' }}
                                           data-base-price="{{ $p->tutar ?? 0 }}">
                                    <div class="pkt-secim-body">
                                        <div class="pkt-secim-name">{{ $p->adi ?? '—' }}</div>
                                        <div class="pkt-secim-price">
                                            Standart: ₺{{ number_format((float) ($p->tutar ?? 0), 2, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="pkt-fiyat-ay-grup">
                                        <input type="number" step="0.01" min="0"
                                               name="paket_fiyat[{{ $p->id }}]"
                                               value="{{ $oldOzelFiyat }}"
                                               placeholder="{{ number_format((float) ($p->tutar ?? 0), 0, '', '') }}"
                                               class="form-input ozel-fiyat-input"
                                               title="Özel fiyat (boş bırakılırsa standart kullanılır)"
                                               onclick="event.preventDefault();event.stopPropagation();"
                                               data-ozel-fiyat>
                                        <select name="paket_ay[{{ $p->id }}]" class="form-select pkt-ay-select"
                                                title="Süre (ay) — fiyat bu kadar ay ile çarpılır"
                                                onclick="event.stopPropagation();"
                                                data-pkt-ay>
                                            @for($ay = 1; $ay <= 12; $ay++)
                                                <option value="{{ $ay }}" {{ (int)$oldAy === $ay ? 'selected' : '' }}>{{ $ay }} ay</option>
                                            @endfor
                                        </select>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    {{-- TOPLAM --}}
                    <div class="toplam-box">
                        <div>
                            <div class="lbl">Tahmini Toplam</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                Manuel tutar + <span id="seciliAdetText">0</span> paket
                            </div>
                        </div>
                        <div class="val">
                            ₺<span id="toplamFiyat">0,00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="help-circle"></i>
                    <span>Nasıl Çalışır?</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.7">
                    <p style="margin:0 0 10px">
                        <strong style="color:var(--text)">1.</strong>
                        Müşteriyi arayıp seç, başlık ve açıklama gir.
                    </p>
                    <p style="margin:0 0 10px">
                        <strong style="color:var(--text)">2.</strong>
                        <strong>Paket seçmek zorunda değilsin.</strong> Sadece tutar girip teklif oluşturabilirsin.
                    </p>
                    <p style="margin:0 0 10px">
                        <strong style="color:var(--text)">3.</strong>
                        Hazır paket eklemek istersen <strong>Paket Ekle</strong> bölümünü aç, işaretle. Her pakete özel fiyat girilebilir.
                    </p>
                    <p style="margin:0">
                        <strong style="color:var(--text)">4.</strong>
                        Teklif oluşturulunca müşteri panelinde görünür ve onayına sunulur.
                    </p>
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.6">
                    <p style="margin:0 0 8px">
                        <span class="required">*</span> ile işaretli alanlar zorunludur.
                    </p>
                    <p style="margin:0 0 8px">
                        Para birimi <strong>USD/EUR/AED</strong> seçilirse fiyatlar sisteme TL olarak kaydedilir.
                    </p>
                    <p style="margin:0">
                        Teklif dosyası opsiyoneldir. Sözleşme veya detaylı sunum eklemek için kullanın.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- STICKY ALT BAR --}}
    <div class="sticky-save">
        <a href="{{ route('admin.paketler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="{{ $duz ? 'save' : 'send' }}"></i>
            <span>{{ $duz ? 'Değişiklikleri Kaydet' : 'Teklif Oluştur' }}</span>
        </button>
    </div>
</form>

<script>
(function() {
    /* ---------- MÜŞTERİ ARAMA + SEÇİM ---------- */
    const arama       = document.getElementById('musteriArama');
    const liste       = document.getElementById('musteriListe');
    const items       = Array.from(liste.querySelectorAll('.musteri-liste-item[data-id]'));
    const bulunamadi  = document.getElementById('musteriBulunamadi');
    const uyeIdInput  = document.getElementById('uyeIdInput');
    const seciliBox   = document.getElementById('musteriSeciliBilgi');
    const seciliText  = document.getElementById('musteriSeciliText');
    const temizleBtn  = document.getElementById('musteriTemizle');

    function listeAc() { liste.classList.add('open'); }
    function listeKapat() { liste.classList.remove('open'); }

    function filtrele() {
        const q = (arama.value || '').toLocaleLowerCase('tr');
        let gorunen = 0;
        items.forEach(it => {
            const hay = it.getAttribute('data-arama') || '';
            const goster = q === '' || hay.indexOf(q) !== -1;
            it.style.display = goster ? '' : 'none';
            if (goster) gorunen++;
        });
        bulunamadi.style.display = gorunen === 0 ? '' : 'none';
    }

    function sec(item) {
        items.forEach(i => i.classList.remove('selected'));
        item.classList.add('selected');
        uyeIdInput.value = item.getAttribute('data-id');
        seciliText.textContent = item.getAttribute('data-ad') + ' (' + item.getAttribute('data-email') + ')';
        seciliBox.classList.add('show');
        listeKapat();
        arama.style.display = 'none';
    }

    function secimiTemizle() {
        uyeIdInput.value = '';
        items.forEach(i => i.classList.remove('selected'));
        seciliBox.classList.remove('show');
        arama.style.display = '';
        arama.value = '';
        filtrele();
        listeAc();
        arama.focus();
    }

    // Kutuya tıkla/odaklan -> tam liste açılır
    arama.addEventListener('focus', () => { filtrele(); listeAc(); });
    arama.addEventListener('click', () => { listeAc(); });
    arama.addEventListener('input', () => { filtrele(); listeAc(); });
    items.forEach(it => it.addEventListener('click', () => sec(it)));
    temizleBtn.addEventListener('click', secimiTemizle);

    // Dışarı tıklayınca kapat
    document.addEventListener('click', (e) => {
        const wrap = arama.closest('.musteri-search-wrap');
        if (wrap && !wrap.contains(e.target)) { listeKapat(); }
    });

    // Eğer old('uye_id') ile bir seçim varsa onu göster
    const onceSecili = items.find(i => i.classList.contains('selected'));
    if (onceSecili) sec(onceSecili);

    /* ---------- PAKET COLLAPSE ---------- */
    const collapseHead = document.getElementById('paketCollapseHead');
    const collapseBody = document.getElementById('paketCollapseBody');
    if (collapseHead && collapseBody) {
        collapseHead.addEventListener('click', () => {
            collapseHead.classList.toggle('is-open');
            collapseBody.classList.toggle('is-open');
        });
    }

    /* ---------- PAKET ARAMA (liste içi) ---------- */
    const paketArama = document.getElementById('paketArama');
    const pktItems = Array.from(document.querySelectorAll('[data-pkt-item]'));
    if (paketArama) {
        paketArama.addEventListener('input', () => {
            const q = (paketArama.value || '').toLocaleLowerCase('tr');
            pktItems.forEach(it => {
                const ad = it.getAttribute('data-pkt-ad') || '';
                it.style.display = (q === '' || ad.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    /* ---------- TOPLAM HESAP ---------- */
    const manuelTutar = document.getElementById('manuelTutar');
    const secilenSayisi = document.getElementById('secilenPaketSayisi');
    const seciliAdetText = document.getElementById('seciliAdetText');
    const toplamFiyat = document.getElementById('toplamFiyat');

    function paraFormat(n) {
        return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function hesapla() {
        let toplam = parseFloat(manuelTutar && manuelTutar.value) || 0;
        let adet = 0;
        pktItems.forEach(item => {
            const cb = item.querySelector('input[type="checkbox"]');
            if (cb && cb.checked) {
                adet++;
                const ozel = item.querySelector('[data-ozel-fiyat]');
                const ozelVal = parseFloat(ozel && ozel.value);
                const baseVal = parseFloat(cb.dataset.basePrice) || 0;
                const birim = (!isNaN(ozelVal) && ozelVal > 0) ? ozelVal : baseVal;
                const aySel = item.querySelector('[data-pkt-ay]');
                const ay = aySel ? (parseInt(aySel.value) || 1) : 1;
                toplam += birim * ay;
            }
        });
        if (secilenSayisi) secilenSayisi.textContent = adet;
        if (seciliAdetText) seciliAdetText.textContent = adet;
        if (toplamFiyat) toplamFiyat.textContent = paraFormat(toplam);
    }

    pktItems.forEach(item => {
        const cb = item.querySelector('input[type="checkbox"]');
        const ozel = item.querySelector('[data-ozel-fiyat]');
        const aySel = item.querySelector('[data-pkt-ay]');
        if (cb) cb.addEventListener('change', () => {
            if (cb.checked) item.classList.add('selected');
            else item.classList.remove('selected');
            hesapla();
        });
        if (ozel) {
            ozel.addEventListener('input', hesapla);
            ozel.addEventListener('click', e => e.stopPropagation());
        }
        if (aySel) {
            aySel.addEventListener('change', hesapla);
            aySel.addEventListener('click', e => e.stopPropagation());
        }
    });
    if (manuelTutar) manuelTutar.addEventListener('input', hesapla);

    /* ---------- ÇOKLU KATEGORİ TOGGLE ---------- */
    document.querySelectorAll('.kat-coklu-item input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', () => {
            const lbl = cb.closest('.kat-coklu-item');
            if (lbl) lbl.classList.toggle('selected', cb.checked);
        });
    });

    /* ---------- GALERİ ÖNİZLEME ---------- */
    const galeriInput = document.getElementById('galeriInput');
    const galeriOnizleme = document.getElementById('galeriOnizleme');
    if (galeriInput && galeriOnizleme) {
        galeriInput.addEventListener('change', () => {
            galeriOnizleme.innerHTML = '';
            Array.from(galeriInput.files).slice(0, 10).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const url = URL.createObjectURL(file);
                const img = document.createElement('img');
                img.src = url;
                img.style.cssText = 'width:70px;height:54px;object-fit:cover;border-radius:8px;border:1px solid var(--border)';
                galeriOnizleme.appendChild(img);
            });
        });
    }

    hesapla();
})();
</script>

{{-- TinyMCE — Açıklama alanı zengin metin editörü (bold, font, boyut, başlık) --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    if (!window.tinymce) return;
    const isDark = document.body.classList.contains('theme-dark');
    const contentBg = isDark ? '#0a0a0a' : '#ffffff';
    const contentColor = isDark ? '#f1f5f9' : '#0f172a';

    function initRich() {
        tinymce.init({
            selector: 'textarea.rich-full',
            license_key: 'gpl',
            promotion: false,
            branding: false,
            height: 360,
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
    initRich();

    // Tema değişirse editörü yeniden başlat (renkler uysun)
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            setTimeout(() => { if (window.tinymce) { tinymce.remove(); initRich(); } }, 100);
        });
    }
})();
</script>

@endsection