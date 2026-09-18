{{-- Ortak hedef-kitle seçim bloğu. Değişkenler: $pfx, $reklamMi, $crmSektorler, $trIller, $istatistik --}}
<div class="form-group">
    <label class="form-label">Hedef Kitle <span class="required">*</span></label>
    <div class="tm-kitle">
        {{-- Her form kendi bagimsiz radio grubuna sahip olmali (name pfx'e bagli),
             yoksa 3 form ayni "kitle" grubunu paylasip birbirini eziyor. --}}
        <input type="hidden" name="kitle" id="{{ $pfx }}_kitle_val" value="uye">
        <label>
            <input type="radio" name="kitle_{{ $pfx }}" value="uye" checked onchange="tmKitleDegis('{{ $pfx }}')">
            <span>👤 Üyeler</span>
        </label>
        <label>
            <input type="radio" name="kitle_{{ $pfx }}" value="crm" onchange="tmKitleDegis('{{ $pfx }}')">
            <span>🏢 CRM Müşterileri</span>
        </label>
    </div>
</div>

<div class="form-group">
    <label class="form-label">Alıcı Grubu <span class="required">*</span></label>
    <select name="hedef" id="{{ $pfx }}_hedef" required class="form-input" onchange="tmHedefDegis('{{ $pfx }}')">
        {{-- Üye seçenekleri --}}
        <option value="tum_aktif" data-kitle="uye">Tüm Aktif Üyeler{{ $reklamMi ? ' (izinli)' : '' }}</option>
        <option value="son_30_gun" data-kitle="uye">Son 30 Gün Kayıt Olanlar</option>
        <option value="filtre" data-kitle="uye">İl / İlçe / Durum Filtreli</option>
        <option value="manuel" data-kitle="uye">Manuel Kişi Seçimi</option>
        {{-- CRM seçenekleri --}}
        <option value="filtre" data-kitle="crm">Tüm CRM / İl-İlçe-Sektör Filtreli</option>
        <option value="manuel" data-kitle="crm">Manuel Kişi Seçimi</option>
    </select>
</div>

{{-- Konum (il/ilçe/sektör) filtreleri — CRM filtre modunda ve üye filtre modunda görünür --}}
<div class="form-group" id="{{ $pfx }}_crm_filtre" style="display:none">
    <label class="form-label">Konum Filtreleri (boş bırakılan = hepsi)</label>
    <div class="tm-crm-filtre">
        <select name="crm_il" id="{{ $pfx }}_crm_il" class="form-input" onchange="trGeoIlceYukle('{{ $pfx }}')">
            <option value="">🏙️ Tüm İller</option>
            @foreach($trIller as $il)<option value="{{ $il->ad }}" data-il-id="{{ $il->id }}">{{ $il->ad }}</option>@endforeach
        </select>
        <select name="crm_ilce" id="{{ $pfx }}_crm_ilce" class="form-input" disabled>
            <option value="">📍 Önce il seçin</option>
        </select>
        {{-- Sektör sadece CRM'de var; üyede sektör kolonu yok, JS gizler --}}
        <select name="crm_sektor" id="{{ $pfx }}_crm_sektor" class="form-input" data-kitle="crm">
            <option value="">🏭 Tüm Sektörler</option>
            @foreach($crmSektorler as $sektor)<option value="{{ $sektor }}">{{ $sektor }}</option>@endforeach
        </select>
    </div>
</div>

{{-- Manuel kişi seçici (sadece "Manuel" hedefinde görünür) — filtreli çoklu seçim listesi --}}
<div class="form-group" id="{{ $pfx }}_picker" style="display:none">
    <label class="form-label">Kişi Seç <small style="color:var(--text-muted);font-weight:400">— listeden tıkla, birden fazla seçilebilir</small></label>
    <div class="tm-picker">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="text" id="{{ $pfx }}_picker_q" class="form-input" placeholder="Listede filtrele (isim, e-posta, telefon)…"
                   style="flex:1;min-width:220px" oninput="tmFiltre('{{ $pfx }}')"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();}">
            <button type="button" class="btn btn-secondary btn-sm" onclick="tmGorunenleriSec('{{ $pfx }}')" title="Filtrede görünen herkesi seç">✓ Görünenleri Seç</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="tmSecimTemizle('{{ $pfx }}')">✕ Temizle</button>
        </div>
        <div id="{{ $pfx }}_picker_list"
             style="margin-top:8px;max-height:260px;overflow:auto;border:1px solid var(--border);border-radius:10px;background:var(--bg, #fff)"></div>
        <div id="{{ $pfx }}_picker_sayi" style="font-size:12px;color:var(--text-muted);margin-top:4px"></div>
        <div class="tm-chips" id="{{ $pfx }}_chips"></div>
        <div id="{{ $pfx }}_manuel_hidden"></div>
    </div>
</div>