{{-- Tab: Hostingler --}}

<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button type="button" class="btn btn-primary btn-sm" onclick="crmToggleForm('hostingEkleForm')">
        <i data-lucide="plus"></i>
        <span>Yeni Hosting Ekle</span>
    </button>
</div>

<div class="section" id="hostingEkleForm" style="{{ $errors->any() ? '' : 'display:none' }}">
    <div class="section-title">
        <i data-lucide="server"></i>
        <span>Yeni Hosting Satışı</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.hosting.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Paket Seç <span class="required">*</span></label>
                @if(!empty($hostingPaketleri) && count($hostingPaketleri) > 0)
                    <select id="hosting_paket_select" class="form-select" onchange="hostingPaketSec(this)">
                        <option value="">— Paket seçin —</option>
                        @foreach($hostingPaketleri as $hp)
                        <option value="{{ $hp->id }}"
                                data-adi="{{ $hp->adi ?? $hp->baslik ?? '' }}"
                                data-tutar="{{ $hp->tutar ?? $hp->fiyat ?? 0 }}">
                            {{ $hp->adi ?? $hp->baslik ?? 'Paket' }} — ₺{{ number_format((float)($hp->tutar ?? $hp->fiyat ?? 0), 2, ',', '.') }}
                        </option>
                        @endforeach
                        <option value="manuel">✏️ Manuel Giriş (özel paket)</option>
                    </select>
                @else
                    <input type="text" name="baslik" required class="form-input" placeholder="Örn: Pro Hosting">
                @endif
                <input type="hidden" name="baslik" id="hosting_baslik_input" value="">
            </div>

            <div class="form-group">
                <label class="form-label">Domain</label>
                <input type="text" name="domain" class="form-input" placeholder="example.com">
            </div>

            <div class="form-group">
                <label class="form-label">Tutar (₺) <span class="required">*</span></label>
                <input type="number" step="0.01" min="0" name="tutar" id="hosting_tutar_input" required class="form-input" placeholder="0.00">
            </div>

            <div class="form-group" id="hosting_manuel_alan" style="display:none">
                <label class="form-label">Özel Paket Adı</label>
                <input type="text" id="hosting_manuel_adi" class="form-input" placeholder="Özel paket adı">
            </div>

            <div class="form-group">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="baslangic_tarih" value="{{ date('Y-m-d') }}" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" name="bitis_tarih" value="{{ date('Y-m-d', strtotime('+1 year')) }}" class="form-input">
            </div>

            <div class="form-group full">
                <label class="form-label">Dosya Ekle</label>
                <input type="file" name="dosya" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="form-input">
            </div>
        </div>

        <div style="margin:12px 0;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;font-size:14px">
                <input type="checkbox" name="mail_gonder" value="1">
                <span>📧 Müşteriye bu işlem için bilgilendirme maili gönder</span>
            </label>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px">
            <button type="reset" class="btn btn-ghost btn-sm">Temizle</button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="save"></i>
                <span>Hosting Kaydet</span>
            </button>
        </div>
    </form>
</div>

@php $_hostingler = $hostingler ?? $customer->hostingler ?? collect(); @endphp

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <h4 style="font-size:14px;font-weight:600">
            🖥️ Hostingler
            <span class="badge badge-brand" style="margin-left:6px">{{ count($_hostingler) }}</span>
        </h4>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Paket</th>
                    <th>Domain</th>
                    <th>Bitiş</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Dosya</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($_hostingler as $h)
                    @php
                        $bitisTarih = $h->bitis_tarih ?? $h->bitis_tarihi ?? null;
                        $kalanGun = $bitisTarih ? (int) ((strtotime($bitisTarih) - time()) / 86400) : null;
                    @endphp
                    <tr>
                        <td><strong>{{ $h->hosting_baslik ?? $h->paket_adi ?? '—' }}</strong></td>
                        <td style="font-family:monospace;font-size:12px">{{ $h->domain ?? '—' }}</td>
                        <td style="font-size:12px">
                            {{ $bitisTarih ?? '—' }}
                            @if($kalanGun !== null)
                                @if($kalanGun < 0)<span class="badge badge-danger" style="font-size:10px">⚠️ {{ abs($kalanGun) }}g geçti</span>
                                @elseif($kalanGun <= 30)<span class="badge badge-warning" style="font-size:10px">⏰ {{ $kalanGun }}g</span>@endif
                            @endif
                        </td>
                        <td><strong>₺{{ number_format((float) ($h->tutar ?? 0), 2, ',', '.') }}</strong></td>
                        <td>
                            @if($h->durum ?? 1)<span class="badge badge-success">✓</span>
                            @else<span class="badge badge-neutral">⏸</span>@endif
                        </td>
                        <td>
                            @if(!empty($h->dosya))
                                <a href="{{ asset('storage/' . $h->dosya) }}" target="_blank" class="badge badge-brand" style="text-decoration:none">İndir</a>
                            @else <span style="color:var(--text-muted)">—</span> @endif
                        </td>
                        <td class="text-right">
                            <button type="button" onclick="printHosting({{ json_encode($h->hosting_baslik ?? $h->paket_adi ?? '') }},{{ json_encode($h->domain ?? '') }},'{{ $bitisTarih ?? '' }}','{{ number_format((float)($h->tutar??0),2,',','.') }}','{{ ($h->durum ?? 1) ? 'Aktif' : 'Pasif' }}','{{ !empty($h->dosya) ? asset('storage/'.$h->dosya) : '' }}')" class="table-action" title="Yazdır">
                                <i data-lucide="printer"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i data-lucide="server" class="empty-state-icon"></i>
                        <h4>Hosting yok</h4>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function hostingPaketSec(sel) {
    var opt = sel.options[sel.selectedIndex];
    var manuelAlan = document.getElementById('hosting_manuel_alan');
    var manuelInput = document.getElementById('hosting_manuel_adi');
    var baslikInput = document.getElementById('hosting_baslik_input');
    var tutarInput = document.getElementById('hosting_tutar_input');
    if (sel.value === 'manuel') {
        manuelAlan.style.display = '';
        manuelInput.oninput = function() { baslikInput.value = this.value; };
        baslikInput.value = '';
        tutarInput.value = '';
    } else if (sel.value) {
        manuelAlan.style.display = 'none';
        baslikInput.value = opt.dataset.adi || '';
        tutarInput.value = opt.dataset.tutar || '';
    } else {
        manuelAlan.style.display = 'none';
        baslikInput.value = '';
    }
}

function printHosting(paket, domain, bitis, tutar, durum, dosyaUrl) {
    var musteriAd = @json(($customer->adi ?? '') . ' ' . ($customer->soyad ?? ''));
    var dosyaHtml = dosyaUrl ? '<p><a href="' + dosyaUrl + '" target="_blank">📎 Dosyayı Görüntüle</a></p>' : '';
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Hosting Detayı</title><style>body{font-family:Arial,sans-serif;margin:40px;color:#333}h2{color:#333;border-bottom:2px solid #b8b62e;padding-bottom:8px}.row{display:flex;gap:40px;margin:10px 0}.lbl{font-weight:bold;min-width:140px;color:#555}</style></head><body>');
    w.document.write('<h2>🖥️ Hosting Detayı</h2>');
    w.document.write('<div class="row"><span class="lbl">Müşteri:</span><span>' + musteriAd + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Paket:</span><span>' + paket + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Domain:</span><span>' + domain + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Bitiş:</span><span>' + bitis + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Tutar:</span><span>₺' + tutar + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Durum:</span><span>' + durum + '</span></div>');
    w.document.write(dosyaHtml);
    w.document.write('</body></html>');
    w.document.close(); w.focus();
    setTimeout(function(){ w.print(); }, 300);
}
</script>