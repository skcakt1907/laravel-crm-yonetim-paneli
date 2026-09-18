@extends('admin._layout')

@section('title', 'Yeni Fatura')

@section('content')

<style>
.uye-combo { position:relative; }
.uye-combo-list { position:absolute; z-index:50; left:0; right:0; top:100%; margin-top:4px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 12px 30px rgba(0,0,0,.12); max-height:320px; overflow-y:auto; }
.uye-combo-item { padding:10px 12px; font-size:13.5px; cursor:pointer; border-bottom:1px solid #f3f4f6; }
.uye-combo-item:last-child { border-bottom:none; }
.uye-combo-item:hover { background:#fafbf5; }
.uye-combo-empty { padding:12px; font-size:13px; color:#94a3b8; text-align:center; }
body.theme-dark .uye-combo-list { background:#1f1f1f; border-color:#333; }
body.theme-dark .uye-combo-item { border-color:#2a2a2a; }
body.theme-dark .uye-combo-item:hover { background:#2a2a20; }
</style>

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.faturalar.index') }}">Faturalar</a>
    <span class="sep">/</span>
    <span class="current">Yeni</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Fatura</h1>
        <div class="page-subtitle">Manuel fatura oluştur</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.faturalar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.faturalar.eklePost') }}" method="POST">
@csrf

<div class="form-grid" style="grid-template-columns: 2fr 1fr; align-items: start">

    {{-- SOL: Fatura bilgileri --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Müşteri</span>
            </div>

            <div class="form-group">
                <label class="form-label">Müşteri Seç <span class="required">*</span></label>
                <div class="uye-combo" id="uyeCombo">
                    <input type="hidden" name="uyeid" id="uyeidValue" value="{{ $secilenUyeId ?? '' }}" required>
                    <input type="text" id="uyeComboInput" class="form-input" autocomplete="off"
                           placeholder="🔍 Müşteri adı, e-posta veya telefon ara...">
                    <div class="uye-combo-list" id="uyeComboList" style="display:none"></div>
                </div>
                <div class="form-help">Giriş hesabı olan CRM müşterileri listelenir. Yazınca filtrelenir.</div>
            </div>
            @php
                $_comboData = ($uyeler ?? collect())->map(function($u){
                    return [
                        'id'    => $u->secim,
                        'ad'    => trim(($u->ad ?? '').' '.($u->soyad ?? '')),
                        'email' => $u->email ?? '',
                        'tel'   => $u->telefon ?? '',
                        'hesap' => !empty($u->hesap_var) ? 1 : 0,
                    ];
                })->values();
            @endphp
            <script type="application/json" id="uyeComboData">@json($_comboData)</script>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Fatura Detayı</span>
            </div>

            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label">Başlık <span class="required">*</span></label>
                    <input type="text" name="baslik" required class="form-input" placeholder="Örn: Mayıs ayı hizmet bedeli" value="{{ old('baslik') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Hizmet</label>
                    <input type="text" name="hizmet" class="form-input" placeholder="Hizmet adı" value="{{ old('hizmet') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Tutar <span class="required">*</span></label>
                    <div style="display:flex;gap:8px">
                        <input type="number" step="0.01" min="0" name="tutar" id="faturaTutar" required class="form-input" placeholder="0.00" value="{{ old('tutar') }}" style="flex:1">
                        <select name="para_birimi" id="faturaParaBirimi" class="form-select" style="width:130px;flex-shrink:0">
                            <option value="TL"  {{ old('para_birimi', 'TL') == 'TL'  ? 'selected' : '' }}>₺ TL</option>
                            <option value="USD" {{ old('para_birimi') == 'USD' ? 'selected' : '' }}>$ USD</option>
                            <option value="EUR" {{ old('para_birimi') == 'EUR' ? 'selected' : '' }}>€ EUR</option>
                            <option value="AED" {{ old('para_birimi') == 'AED' ? 'selected' : '' }}>د.إ AED</option>
                            <option value="GBP" {{ old('para_birimi') == 'GBP' ? 'selected' : '' }}>£ GBP</option>
                        </select>
                    </div>
                    <div class="form-help">KDV hariç tutar (matrah) — seçilen para biriminde</div>
                    <div id="kurBilgi" style="display:none;margin-top:8px;padding:10px 12px;background:rgba(184,182,46,.08);border:1px solid rgba(184,182,46,.3);border-radius:8px;font-size:12.5px;color:#6b6a1e"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">KDV Oranı</label>
                    <select name="kdv_orani" class="form-select">
                        <option value="20" {{ old('kdv_orani', '20') == '20' ? 'selected' : '' }}>%20</option>
                        <option value="10" {{ old('kdv_orani') == '10' ? 'selected' : '' }}>%10</option>
                        <option value="1" {{ old('kdv_orani') == '1' ? 'selected' : '' }}>%1</option>
                        <option value="0" {{ old('kdv_orani') == '0' ? 'selected' : '' }}>%0 (KDV yok)</option>
                    </select>
                    <div class="form-help">Faturaya uygulanacak KDV oranı</div>
                </div>

                <div class="form-group full">
                    <label class="form-label">Açıklama</label>
                    <textarea name="aciklama" rows="4" class="form-textarea" placeholder="Fatura ile ilgili notlar...">{{ old('aciklama') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- SAĞ: Hesap ayarları --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="settings"></i>
                <span>Hesap Ayarları</span>
            </div>

            <div class="form-group">
                <label class="form-label">Durum</label>
                <select name="durum" class="form-select">
                    <option value="0" {{ old('durum', '0') == '0' ? 'selected' : '' }}>⏳ Bekliyor</option>
                    <option value="1" {{ old('durum') == '1' ? 'selected' : '' }}>✓ Ödendi</option>
                    <option value="2" {{ old('durum') == '2' ? 'selected' : '' }}>✗ İptal</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Son Ödeme Tarihi</label>
                <input type="date" name="bitis_tarih" class="form-input" value="{{ old('bitis_tarih') }}">
                <div class="form-help">Ödeme son tarihi (opsiyonel)</div>
            </div>

            <div class="form-group" style="margin-top:14px;padding:12px;background:var(--bg-subtle);border-radius:8px">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
                    <input type="checkbox" name="mail_gonder" value="1" checked>
                    <span style="font-size:13px"><strong>📧 Müşteriye mail gönder</strong></span>
                </label>
                <div class="form-help" style="margin-top:6px">Fatura bilgisi müşterinin e-postasına otomatik gönderilir.</div>
            </div>
        </div>

        <div class="section">
            <button type="submit" class="btn btn-primary" style="width:100%">
                <i data-lucide="save"></i>
                <span>Fatura Oluştur</span>
            </button>
            <a href="{{ route('admin.faturalar.index') }}" class="btn btn-secondary" style="width:100%;margin-top:8px">
                İptal
            </a>
        </div>

        <div class="alert alert-info">
            <i data-lucide="info"></i>
            <div style="font-size:12px">
                <strong>İpucu:</strong> Fatura oluşturulduktan sonra <i data-lucide="printer" style="display:inline;vertical-align:middle"></i> ile yazdırabilir, müşteriye gönderebilirsin.
            </div>
        </div>
    </div>

</div>

</form>

<script>
// 💱 Para birimi → canlı TCMB kur bilgisi
(function() {
    const KURLAR = @json(\App\Helpers\DovizKuruHelper::tcmbKurlariCek());
    const sec = document.getElementById('faturaParaBirimi');
    const tutarInp = document.getElementById('faturaTutar');
    const kutu = document.getElementById('kurBilgi');
    if (!sec || !tutarInp || !kutu) return;

    function tlFormat(n) {
        return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function guncelle() {
        const pb = sec.value;
        if (pb === 'TL') { kutu.style.display = 'none'; return; }
        const kur = parseFloat(KURLAR[pb] || 0);
        if (!kur) { kutu.style.display = 'none'; return; }
        const tutar = parseFloat(tutarInp.value || 0);
        let html = '💱 Güncel TCMB kuru: <strong>1 ' + pb + ' = ' + kur.toLocaleString('tr-TR', { minimumFractionDigits: 4 }) + ' ₺</strong>';
        if (tutar > 0) {
            html += '<br>TL karşılığı: <strong>' + tlFormat(tutar * kur) + ' ₺</strong> (fatura bu TL tutarla kaydedilir)';
        }
        kutu.innerHTML = html;
        kutu.style.display = '';
    }

    sec.addEventListener('change', guncelle);
    tutarInp.addEventListener('input', guncelle);
    guncelle();
})();

// Aranabilir müşteri combobox'ı (arama + liste tek kutuda)
(function() {
    const wrap  = document.getElementById('uyeCombo');
    const input = document.getElementById('uyeComboInput');
    const list  = document.getElementById('uyeComboList');
    const hidden= document.getElementById('uyeidValue');
    const dataEl= document.getElementById('uyeComboData');
    if (!wrap || !input || !list || !hidden || !dataEl) return;

    let veri = [];
    try { veri = JSON.parse(dataEl.textContent) || []; } catch(e) { veri = []; }

    function esc(s){ return (s||'').toString().replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
    function etiket(o){ return o.ad + (o.email? ' — '+o.email : '') + (o.tel? ' — '+o.tel : ''); }
    function etiketListe(o){ return etiket(o) + (o.hesap? '' : '  <span style="color:#b45309;font-size:11px">(hesap açılacak)</span>'); }

    // Başlangıçta seçili varsa input'a yaz
    if (hidden.value) {
        const s = veri.find(o => String(o.id) === String(hidden.value));
        if (s) input.value = etiket(s);
    }

    function ciz(q){
        q = (q||'').toLocaleLowerCase('tr').trim();
        const suz = q ? veri.filter(o => etiket(o).toLocaleLowerCase('tr').includes(q)) : veri;
        if (!suz.length){ list.innerHTML = '<div class="uye-combo-empty">Eşleşen müşteri yok</div>'; list.style.display='block'; return; }
        list.innerHTML = suz.slice(0, 200).map(o =>
            `<div class="uye-combo-item" data-id="${o.id}">${etiketListe(o)}</div>`
        ).join('');
        list.style.display = 'block';
    }

    input.addEventListener('focus', () => ciz(input.value));
    input.addEventListener('input', () => { hidden.value=''; ciz(input.value); });
    list.addEventListener('click', (e) => {
        const it = e.target.closest('.uye-combo-item');
        if (!it) return;
        const id = it.getAttribute('data-id');
        const o = veri.find(x => String(x.id) === String(id));
        if (o){ hidden.value = o.id; input.value = etiket(o); }
        list.style.display = 'none';
    });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) list.style.display='none'; });
})();
</script>

@endsection