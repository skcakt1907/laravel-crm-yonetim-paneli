{{-- Tab: E-Faturalar --}}
@php
    $musteriUyeId = \Illuminate\Support\Facades\DB::table('uyeler')->where('email', $customer->email)->value('id');
    $efaturalar = collect();
    try {
        $efaturalar = \Illuminate\Support\Facades\DB::table('musteri_efaturalar')
            ->where(function($q) use ($musteriUyeId, $customer) {
                if ($musteriUyeId) $q->where('uyeid', $musteriUyeId);
                $q->orWhere('crm_musteri_id', $customer->id);
            })
            ->orderByDesc('id')->get();
    } catch (\Throwable $e) {}
@endphp

<div class="section">
    <div class="section-title">
        <i data-lucide="plus-circle"></i>
        <span>Yeni E-Fatura Kaydı</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.efatura.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Başlık <span class="required">*</span></label>
                <input type="text" name="baslik" required class="form-input" placeholder="Örn: Mayıs 2026 E-Fatura">
            </div>
            <div class="form-group">
                <label class="form-label">Tutar (₺) <span class="required">*</span></label>
                <input type="number" step="0.01" min="0" name="tutar" required class="form-input">
            </div>
            <div class="form-group full">
                <label class="form-label">İçerik / Detay</label>
                <textarea name="icerik" rows="4" class="form-textarea" placeholder="E-Fatura detayları..."></textarea>
            </div>
            <div class="form-group full">
                <label class="form-label">Dosya Ekle <span style="color:var(--text-muted);font-size:11px">(PDF, JPG, PNG, DOCX, XLSX)</span></label>
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
                <span>E-Fatura Oluştur</span>
            </button>
        </div>
    </form>
</div>

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <h4 style="font-size:14px;font-weight:600">
            📑 E-Faturalar
            <span class="badge badge-brand" style="margin-left:6px">{{ $efaturalar->count() }}</span>
        </h4>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Başlık</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>Dosya</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($efaturalar as $e)
                <tr>
                    <td style="font-family:monospace;font-size:12px">#{{ $e->id }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($e->baslik ?? '—', 50) }}</td>
                    <td><strong>₺{{ number_format((float) ($e->tutar ?? 0), 2, ',', '.') }}</strong></td>
                    <td><span class="badge badge-warning">{{ $e->durum ?? 'beklemede' }}</span></td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $e->tarih ?? '—' }}</td>
                    <td>
                        @if(!empty($e->dosya))
                            <a href="{{ asset('storage/' . $e->dosya) }}" target="_blank" class="badge badge-brand" style="text-decoration:none">İndir</a>
                        @else <span style="color:var(--text-muted)">—</span> @endif
                    </td>
                    <td class="text-right">
                        <button type="button" onclick="printEfatura({{ $e->id }},{{ json_encode($e->baslik ?? '') }},'{{ number_format((float)($e->tutar??0),2,',','.') }}',{{ json_encode($e->durum ?? '') }},'{{ $e->tarih ?? '' }}',{{ json_encode($e->icerik ?? '') }},'{{ !empty($e->dosya) ? asset('storage/'.$e->dosya) : '' }}')" class="table-action" title="Yazdır">
                            <i data-lucide="printer"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i data-lucide="file-text" class="empty-state-icon"></i>
                        <h4>E-Fatura yok</h4>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function printEfatura(id, baslik, tutar, durum, tarih, icerik, dosyaUrl) {
    var musteriAd = @json(($customer->adi ?? '') . ' ' . ($customer->soyad ?? ''));
    var dosyaHtml = dosyaUrl ? '<p><a href="' + dosyaUrl + '" target="_blank">📎 Dosyayı Görüntüle</a></p>' : '';
    var icerikHtml = icerik ? '<hr><p style="white-space:pre-wrap">' + icerik + '</p>' : '';
    var w = window.open('', '_blank', 'width=800,height=700');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>E-Fatura #' + id + '</title><style>body{font-family:Arial,sans-serif;margin:40px;color:#333}h2{color:#333;border-bottom:2px solid #b8b62e;padding-bottom:8px}.row{display:flex;gap:40px;margin:10px 0}.lbl{font-weight:bold;min-width:120px;color:#555}</style></head><body>');
    w.document.write('<h2>📑 E-Fatura #' + id + '</h2>');
    w.document.write('<div class="row"><span class="lbl">Müşteri:</span><span>' + musteriAd + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Başlık:</span><span>' + baslik + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Tutar:</span><span>₺' + tutar + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Durum:</span><span>' + durum + '</span></div>');
    w.document.write('<div class="row"><span class="lbl">Tarih:</span><span>' + tarih + '</span></div>');
    w.document.write(icerikHtml + dosyaHtml);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function(){ w.print(); }, 300);
}
</script>