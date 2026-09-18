{{-- Tab: Hizmetler (alt sekmeler: Hizmetler | Hostingler | Alan Adları) --}}
@php $altSekme = $altSekme ?? 'hizmetler-alt'; @endphp
<div style="display:flex;gap:8px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:0">
    <button type="button" class="alt-tab-btn" data-alt="alt-hizmetler" onclick="altSekmeAc('alt-hizmetler', this)"
            style="border:0;background:none;padding:10px 14px;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2px solid var(--brand);color:var(--brand)">
        ⚒️ Hizmetler <span class="badge badge-brand" style="margin-left:4px">{{ ($hizmetler ?? collect())->count() }}</span>
    </button>
    <button type="button" class="alt-tab-btn" data-alt="alt-hostingler" onclick="altSekmeAc('alt-hostingler', this)"
            style="border:0;background:none;padding:10px 14px;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2px solid transparent;color:var(--text-secondary)">
        🖥️ Hostingler <span class="badge badge-neutral" style="margin-left:4px">{{ ($hostingler ?? collect())->count() }}</span>
    </button>
    <button type="button" class="alt-tab-btn" data-alt="alt-alan-adlari" onclick="altSekmeAc('alt-alan-adlari', this)"
            style="border:0;background:none;padding:10px 14px;font-size:13px;font-weight:700;cursor:pointer;border-bottom:2px solid transparent;color:var(--text-secondary)">
        🌐 Alan Adları <span class="badge badge-neutral" style="margin-left:4px">{{ ($alanAdlari ?? collect())->count() }}</span>
    </button>
</div>

<script>
function altSekmeAc(id, btn) {
    ['alt-hizmetler', 'alt-hostingler', 'alt-alan-adlari'].forEach(function(k) {
        var el = document.getElementById(k);
        if (el) el.style.display = (k === id) ? '' : 'none';
    });
    document.querySelectorAll('.alt-tab-btn').forEach(function(b) {
        var aktif = b === btn;
        b.style.borderBottomColor = aktif ? 'var(--brand)' : 'transparent';
        b.style.color = aktif ? 'var(--brand)' : 'var(--text-secondary)';
    });
}
document.addEventListener('DOMContentLoaded', function() {
    @if(($altSekme ?? null) === 'hostingler')
        altSekmeAc('alt-hostingler', document.querySelector('[data-alt="alt-hostingler"]'));
    @elseif(($altSekme ?? null) === 'alan-adlari')
        altSekmeAc('alt-alan-adlari', document.querySelector('[data-alt="alt-alan-adlari"]'));
    @endif
});
</script>

<div id="alt-hostingler" style="display:none">
    @include('admin.crm.customers.tabs.hostingler')
</div>
<div id="alt-alan-adlari" style="display:none">
    @include('admin.crm.customers.tabs.alan-adlari')
</div>

<div id="alt-hizmetler">
@php
    $hizmetSecenekleri = \Illuminate\Support\Facades\DB::table('hizmetler')->where('durum', 1)->orderBy('adi')->get(['id', 'adi']);
@endphp

<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button type="button" class="btn btn-primary btn-sm" onclick="crmToggleForm('hizmetEkleForm')">
        <i data-lucide="plus"></i>
        <span>Yeni Hizmet Ekle</span>
    </button>
</div>

<div class="section" id="hizmetEkleForm" style="{{ $errors->any() ? '' : 'display:none' }}">
    <div class="section-title">
        <i data-lucide="plus-circle"></i>
        <span>Manuel Hizmet Satışı Ekle</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.hizmet.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Hizmet Seç (opsiyonel)</label>
                <select class="form-select" onchange="if(this.value){const o=this.options[this.selectedIndex];document.querySelector('input[name=baslik]').value=o.text;}">
                    <option value="">— Manuel gir —</option>
                    @foreach($hizmetSecenekleri as $h)<option value="{{ $h->id }}">{{ $h->adi }}</option>@endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Hizmet Adı <span class="required">*</span></label>
                <input type="text" name="baslik" required class="form-input" placeholder="Örn: SEO Hizmeti">
            </div>
            <div class="form-group">
                <label class="form-label">Tutar (₺) <span class="required">*</span></label>
                <input type="number" step="0.01" min="0" name="tutar" required class="form-input" placeholder="1500.00">
            </div>
            <div class="form-group">
                <label class="form-label">Başlangıç</label>
                <input type="date" name="baslangic_tarih" value="{{ date('Y-m-d') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Bitiş</label>
                <input type="date" name="bitis_tarih" class="form-input">
            </div>
            <div class="form-group full">
                <label class="form-label">Açıklama</label>
                <textarea name="aciklama" rows="3" class="form-textarea"></textarea>
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
                <span>Hizmet Sat</span>
            </button>
        </div>
    </form>
</div>

@php $_hizmetler = $hizmetler ?? $customer->hizmetler ?? collect(); @endphp

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <h4 style="font-size:14px;font-weight:600">
            ⚒️ Hizmetler
            <span class="badge badge-brand" style="margin-left:6px">{{ count($_hizmetler) }}</span>
        </h4>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hizmet</th>
                    <th>Tutar</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th>Dosya</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($_hizmetler as $h)
                <tr>
                    <td><strong>{{ $h->adi ?? $h->paket_baslik ?? '—' }}</strong></td>
                    <td><strong>₺{{ number_format((float) ($h->tutar ?? 0), 2, ',', '.') }}</strong></td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $h->tarih ?? '—' }}</td>
                    <td>
                        @if($h->durum ?? 1)
                            <span class="badge badge-success">✓ Aktif</span>
                        @else
                            <span class="badge badge-neutral">⏸ Pasif</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($h->dosya))
                            <a href="{{ asset('storage/' . $h->dosya) }}" target="_blank" class="badge badge-brand" style="text-decoration:none">İndir</a>
                        @else <span style="color:var(--text-muted)">—</span> @endif
                    </td>
                    <td class="text-right">
                        <button type="button" onclick="printHizmet({{ json_encode($h->adi ?? $h->paket_baslik ?? '') }},'{{ number_format((float)($h->tutar??0),2,',','.') }}','{{ $h->tarih ?? '' }}','{{ ($h->durum ?? 1) ? 'Aktif' : 'Pasif' }}','{{ !empty($h->dosya) ? asset('storage/'.$h->dosya) : '' }}')" class="table-action" title="Yazdır">
                            <i data-lucide="printer"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-state">
                        <i data-lucide="wrench" class="empty-state-icon"></i>
                        <h4>Hizmet yok</h4>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function printHizmet(adi, tutar, tarih, durum, dosyaUrl) {
    var musteriAd = @json(($customer->adi ?? '') . ' ' . ($customer->soyad ?? ''));
    var dosyaHtml = dosyaUrl ? '<p><a href="' + dosyaUrl + '" target="_blank">📎 Dosyayı Görüntüle</a></p>' : '';
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Hizmet Detayı</title><style>body{font-family:Arial,sans-serif;margin:40px;color:#333}h2{color:#333;border-bottom:2px solid #b8b62e;padding-bottom:8px}.row{display:flex;gap:40px;margin:10px 0}.lbl{font-weight:bold;min-width:140px;color:#555}</style></head><body>');
    w.document.write('<h2>⚒️ Hizmet Detayı</h2>');
    w.document.write('<div class="row"><span class="lbl">Müşteri:</span><span>' + musteriAd + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Hizmet:</span><span>' + adi + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Tutar:</span><span>₺' + tutar + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Tarih:</span><span>' + tarih + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Durum:</span><span>' + durum + '</span></div>');
    w.document.write(dosyaHtml);
    w.document.write('</body></html>');
    w.document.close(); w.focus();
    setTimeout(function(){ w.print(); }, 300);
}
</script>
</div>{{-- /alt-hizmetler --}}
