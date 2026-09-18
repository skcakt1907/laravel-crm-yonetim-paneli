@extends('admin._layout')

@section('title', 'Fatura Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.faturalar.index') }}">Faturalar</a>
    <span class="sep">/</span>
    <span class="current">Düzenle</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ Fatura Düzenle</h1>
        <div class="page-subtitle">#{{ $fatura->fatura_no ?? $fatura->id }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.faturalar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.faturalar.duzenlePost', $fatura->id) }}" method="POST">
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
                <label class="form-label">Üye Seç <span class="required">*</span></label>
                <input type="text" id="uyeAra" class="form-input" placeholder="🔍 Üye adı veya email yazmaya başla..." autocomplete="off">
                <select name="uyeid" id="uyeidSelect" required class="form-select" style="margin-top:8px">
                    <option value="">— Üye seçin —</option>
                    @foreach($uyeler ?? [] as $u)
                        <option value="{{ $u->id }}" {{ old('uyeid', $fatura->uyeid) == $u->id ? 'selected' : '' }}>
                            {{ $u->ad }} {{ $u->soyad }} — {{ $u->email }}@if(!empty($u->telefon)) — {{ $u->telefon }}@endif
                        </option>
                    @endforeach
                </select>
                <div class="form-help">Yukarıda arama yap, listeyi filtrele. Üye yoksa önce üye ekle.</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Fatura Detayı</span>
            </div>

            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label">Başlık <span class="required">*</span></label>
                    <input type="text" name="baslik" required class="form-input" placeholder="Örn: Mayıs ayı hizmet bedeli" value="{{ old('baslik', $fatura->baslik) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Hizmet</label>
                    <input type="text" name="hizmet" class="form-input" placeholder="Hizmet adı" value="{{ old('hizmet', $fatura->hizmet) }}">
                </div>

                <div class="form-group">
                    <label class="form-label">Tutar <span class="required">*</span></label>
                    <div style="display:flex;gap:8px">
                        <input type="number" step="0.01" min="0" name="tutar" id="faturaTutar" required class="form-input" placeholder="0.00" value="{{ old('tutar', (!empty($fatura->doviz_tutar ?? null) && ($fatura->para_birimi ?? 'TL') !== 'TL') ? $fatura->doviz_tutar : $fatura->tutar) }}" style="flex:1">
                        <select name="para_birimi" id="faturaParaBirimi" class="form-select" style="width:130px;flex-shrink:0">
                            <option value="TL"  {{ old('para_birimi', $fatura->para_birimi ?? 'TL') == 'TL'  ? 'selected' : '' }}>₺ TL</option>
                            <option value="USD" {{ old('para_birimi', $fatura->para_birimi ?? 'TL') == 'USD' ? 'selected' : '' }}>$ USD</option>
                            <option value="EUR" {{ old('para_birimi', $fatura->para_birimi ?? 'TL') == 'EUR' ? 'selected' : '' }}>€ EUR</option>
                            <option value="AED" {{ old('para_birimi', $fatura->para_birimi ?? 'TL') == 'AED' ? 'selected' : '' }}>د.إ AED</option>
                            <option value="GBP" {{ old('para_birimi', $fatura->para_birimi ?? 'TL') == 'GBP' ? 'selected' : '' }}>£ GBP</option>
                        </select>
                    </div>
                    <div class="form-help">KDV hariç tutar (matrah) — seçilen para biriminde</div>
                    <div id="kurBilgi" style="display:none;margin-top:8px;padding:10px 12px;background:rgba(184,182,46,.08);border:1px solid rgba(184,182,46,.3);border-radius:8px;font-size:12.5px;color:#6b6a1e"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">KDV Oranı</label>
                    <select name="kdv_orani" class="form-select">
                        <option value="20" {{ old('kdv_orani', $fatura->kdv_orani ?? 20) == '20' ? 'selected' : '' }}>%20</option>
                        <option value="10" {{ old('kdv_orani', $fatura->kdv_orani ?? 20) == '10' ? 'selected' : '' }}>%10</option>
                        <option value="1" {{ old('kdv_orani', $fatura->kdv_orani ?? 20) == '1' ? 'selected' : '' }}>%1</option>
                        <option value="0" {{ old('kdv_orani', $fatura->kdv_orani ?? 20) == '0' ? 'selected' : '' }}>%0 (KDV yok)</option>
                    </select>
                    <div class="form-help">Faturaya uygulanacak KDV oranı</div>
                </div>

                <div class="form-group full">
                    <label class="form-label">Açıklama</label>
                    <textarea name="aciklama" rows="4" class="form-textarea" placeholder="Fatura ile ilgili notlar...">{{ old('aciklama', $fatura->aciklama) }}</textarea>
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
                    <option value="0" {{ old('durum', $fatura->durum ?? 0) == '0' ? 'selected' : '' }}>⏳ Bekliyor</option>
                    <option value="1" {{ old('durum', $fatura->durum ?? 0) == '1' ? 'selected' : '' }}>✓ Ödendi</option>
                    <option value="2" {{ old('durum', $fatura->durum ?? 0) == '2' ? 'selected' : '' }}>✗ İptal</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Kesim Tarihi</label>
                <input type="date" name="tarih" class="form-input" value="{{ old('tarih', !empty($fatura->tarih) ? \Carbon\Carbon::parse($fatura->tarih)->format('Y-m-d') : '') }}">
                <div class="form-help">Faturanın kesildiği (düzenlendiği) tarih — raporlarda bu ay esas alınır</div>
            </div>

            <div class="form-group">
                <label class="form-label">Son Ödeme Tarihi</label>
                <input type="date" name="bitis_tarih" class="form-input" value="{{ old('bitis_tarih', !empty($fatura->bitis_tarih) ? \Carbon\Carbon::parse($fatura->bitis_tarih)->format('Y-m-d') : '') }}">
                <div class="form-help">Ödeme son tarihi (opsiyonel)</div>
            </div>

            <div class="form-group">
                <label class="form-label">Tahsilat Tarihi</label>
                <input type="date" name="odenen_tarih" class="form-input" value="{{ old('odenen_tarih', !empty($fatura->odenen_tarih) ? \Carbon\Carbon::parse($fatura->odenen_tarih)->format('Y-m-d') : '') }}">
                <div class="form-help">Ödemenin alındığı tarih — "Ödendi" işaretlenince otomatik dolar, buradan değiştirilebilir</div>
            </div>

        </div>

        <div class="section">
            <button type="submit" class="btn btn-primary" style="width:100%">
                <i data-lucide="save"></i>
                <span>Güncelle</span>
            </button>
            <a href="{{ route('admin.faturalar.detay', $fatura->id) }}" class="btn btn-secondary" style="width:100%;margin-top:8px">
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

// Üye arama (client-side filter)
(function() {
    const input = document.getElementById('uyeAra');
    const select = document.getElementById('uyeidSelect');
    if (!input || !select) return;

    // Tüm seçenekleri sakla
    const allOptions = Array.from(select.options).slice(1).map(o => ({
        value: o.value,
        text: o.textContent,
        selected: o.selected
    }));

    input.addEventListener('input', function() {
        const q = input.value.toLowerCase().trim();
        // İlk option ("Üye seçin") kalsın, diğerleri temizle
        while (select.options.length > 1) select.remove(1);

        const filtered = q ? allOptions.filter(o => o.text.toLowerCase().includes(q)) : allOptions;
        filtered.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            if (o.selected) opt.selected = true;
            select.appendChild(opt);
        });

        // Tek eşleşme varsa otomatik seç
        if (filtered.length === 1) {
            select.value = filtered[0].value;
        }
    });
})();
</script>

@endsection