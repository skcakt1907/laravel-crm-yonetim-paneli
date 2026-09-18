{{-- Tab: Alan Adları --}}

<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button type="button" class="btn btn-primary btn-sm" onclick="crmToggleForm('domainEkleForm')">
        <i data-lucide="plus"></i>
        <span>Yeni Alan Adı Ekle</span>
    </button>
</div>

<div class="section" id="domainEkleForm" style="{{ $errors->any() ? '' : 'display:none' }}">
    <div class="section-title">
        <i data-lucide="globe"></i>
        <span>Yeni Domain Ekle</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.domain.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Domain <span class="required">*</span></label>
                <input type="text" name="domain" required class="form-input" placeholder="example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Tutar (₺)</label>
                <input type="number" step="0.01" min="0" name="tutar" class="form-input" placeholder="100.00">
            </div>
            <div class="form-group">
                <label class="form-label">Sağlayıcı</label>
                <input type="text" name="saglayici" class="form-input" placeholder="Örn: GoDaddy, Natro">
            </div>
            <div class="form-group">
                <label class="form-label">Başlangıç</label>
                <input type="date" name="baslangic_tarih" value="{{ date('Y-m-d') }}" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Bitiş</label>
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
                <span>Domain Ekle</span>
            </button>
        </div>
    </form>
</div>

@php $_domainler = $alanAdlari ?? $customer->domainler ?? collect(); @endphp

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <h4 style="font-size:14px;font-weight:600">
            🌐 Alan Adları
            <span class="badge badge-brand" style="margin-left:6px">{{ count($_domainler) }}</span>
        </h4>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Başlangıç</th>
                    <th>Bitiş</th>
                    <th>Kalan</th>
                    <th>Durum</th>
                    <th>Dosya</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($_domainler as $d)
                    @php
                        $bitis = $d->bitis_tarih ?? $d->bitis_tarihi ?? null;
                        $domainName = $d->domain ?? $d->domain_name ?? '—';
                        $kalanGun = $bitis ? (int) ((strtotime($bitis) - time()) / 86400) : null;
                        $durumStr = (($d->durum ?? '') == 'aktif' || ($d->durum ?? 0) == 1) ? 'Aktif' : 'Bekliyor';
                    @endphp
                    <tr>
                        <td><strong>🌐 {{ $domainName }}</strong></td>
                        <td style="font-size:12px;color:var(--text-muted)">{{ $d->baslangic_tarih ?? $d->kayit_tarihi ?? '—' }}</td>
                        <td style="font-size:12px">{{ $bitis ?? '—' }}</td>
                        <td style="font-size:12px">
                            @if($kalanGun === null)<span style="color:var(--text-muted)">—</span>
                            @elseif($kalanGun < 0)<span class="badge badge-danger">⚠️ {{ abs($kalanGun) }}g geçti</span>
                            @elseif($kalanGun <= 30)<span class="badge badge-warning">⏰ {{ $kalanGun }}g</span>
                            @else<span style="color:var(--text-secondary)">{{ $kalanGun }}g</span>@endif
                        </td>
                        <td>
                            @if(($d->durum ?? '') == 'aktif' || ($d->durum ?? 0) == 1)
                                <span class="badge badge-success">✓ Aktif</span>
                            @else
                                <span class="badge badge-warning">⏳ Bekliyor</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($d->dosya))
                                <a href="{{ asset('storage/' . $d->dosya) }}" target="_blank" class="badge badge-brand" style="text-decoration:none">İndir</a>
                            @else <span style="color:var(--text-muted)">—</span> @endif
                        </td>
                        <td class="text-right">
                            <button type="button" onclick="printDomain({{ json_encode($domainName) }},'{{ $d->baslangic_tarih ?? $d->kayit_tarihi ?? '' }}','{{ $bitis ?? '' }}',{{ json_encode($d->saglayici ?? '') }},'{{ $durumStr }}','{{ !empty($d->dosya) ? asset('storage/'.$d->dosya) : '' }}')" class="table-action" title="Yazdır">
                                <i data-lucide="printer"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i data-lucide="globe" class="empty-state-icon"></i>
                        <h4>Alan adı yok</h4>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function printDomain(domain, baslangic, bitis, saglayici, durum, dosyaUrl) {
    var musteriAd = @json(($customer->adi ?? '') . ' ' . ($customer->soyad ?? ''));
    var dosyaHtml = dosyaUrl ? '<p><a href="' + dosyaUrl + '" target="_blank">📎 Dosyayı Görüntüle</a></p>' : '';
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Domain Detayı</title><style>body{font-family:Arial,sans-serif;margin:40px;color:#333}h2{color:#333;border-bottom:2px solid #b8b62e;padding-bottom:8px}.row{display:flex;gap:40px;margin:10px 0}.lbl{font-weight:bold;min-width:140px;color:#555}</style></head><body>');
    w.document.write('<h2>🌐 Domain Detayı</h2>');
    w.document.write('<div class="row"><span class="lbl">Müşteri:</span><span>' + musteriAd + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Domain:</span><span>' + domain + '</span></div>');
    if (saglayici) w.document.write('<div class="row"><span class="lbl">Sağlayıcı:</span><span>' + saglayici + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Başlangıç:</span><span>' + baslangic + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Bitiş:</span><span>' + bitis + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Durum:</span><span>' + durum + '</span></div>');
    w.document.write(dosyaHtml);
    w.document.write('</body></html>');
    w.document.close(); w.focus();
    setTimeout(function(){ w.print(); }, 300);
}
</script>