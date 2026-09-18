{{-- Tab: Faturalar --}}

<div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <button type="button" class="btn btn-primary btn-sm" onclick="crmToggleForm('faturaEkleForm')">
        <i data-lucide="plus"></i>
        <span>Yeni Fatura Ekle</span>
    </button>
</div>

<div class="section" id="faturaEkleForm" style="{{ $errors->any() ? '' : 'display:none' }}">
    <div class="section-title">
        <i data-lucide="plus-circle"></i>
        <span>Yeni Fatura Oluştur</span>
    </div>

    <form action="{{ route('admin.crm.musteriler.fatura.ekle', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Başlık <span class="required">*</span></label>
                <input type="text" name="baslik" required class="form-input" placeholder="Örn: Mayıs ayı hizmet bedeli">
            </div>
            <div class="form-group">
                <label class="form-label">Hizmet</label>
                <input type="text" name="hizmet" class="form-input" placeholder="Hizmet adı">
            </div>
            <div class="form-group">
                <label class="form-label">Tutar (₺) <span class="required">*</span></label>
                <input type="number" step="0.01" min="0" name="tutar" required class="form-input" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Vade Tarihi</label>
                <input type="date" name="bitis_tarih" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Ödeme Yöntemi</label>
                <select name="odeme_yontemi" class="form-select">
                    <option value="havale">Havale/EFT</option>
                    <option value="online">Online (PayTR)</option>
                    <option value="elden">Elden</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Durum</label>
                <select name="durum" class="form-select">
                    <option value="0">⏳ Bekliyor</option>
                    <option value="1">✅ Ödendi</option>
                    <option value="2">❌ İptal</option>
                </select>
            </div>
            <div class="form-group full">
                <label class="form-label">Açıklama</label>
                <textarea name="aciklama" rows="3" class="form-textarea" placeholder="Fatura ile ilgili notlar..."></textarea>
            </div>
            <div class="form-group full">
                <label class="form-label">Dosya Ekle <span style="color:var(--text-muted);font-size:11px">(PDF, JPG, PNG, DOCX, XLSX)</span></label>
                <input type="file" name="dosya" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" class="form-input">
            </div>
        </div>

        <div style="margin:12px 0;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0 0 8px;font-size:14px">
                <input type="checkbox" name="mail_gonder" value="1">
                <span>📧 Müşteriye bilgilendirme <strong>maili</strong> gönder</span>
            </label>

            {{-- GÖREV #217/5 — bilgilendirme SMS'i (mail'den bağımsız) --}}
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;font-size:14px">
                <input type="checkbox" name="sms_gonder" value="1">
                <span>📱 Müşteriye bilgilendirme <strong>SMS'i</strong> gönder</span>
            </label>
            @php $_tel = trim((string) ($uye->telefon ?? $customer->telefon ?? '')); @endphp
            @if($_tel === '')
                <div style="font-size:11.5px;color:#8A6200;margin-top:7px">
                    ⚠️ Bu müşterinin telefonu kayıtlı değil — SMS gönderilemez.
                </div>
            @else
                <div style="font-size:11.5px;color:var(--text-muted);margin-top:7px">SMS gidecek numara: {{ $_tel }}</div>
            @endif
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px">
            <button type="reset" class="btn btn-ghost btn-sm">Temizle</button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="save"></i>
                <span>Fatura Oluştur</span>
            </button>
        </div>
    </form>
</div>

@php $_faturalar = $faturalar ?? $customer->faturalar ?? collect(); @endphp

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <h4 style="font-size:14px;font-weight:600">
            🧾 Faturalar
            <span class="badge badge-brand" style="margin-left:6px">{{ count($_faturalar) }}</span>
        </h4>
    </div>
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Başlık</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>Dosya</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($_faturalar as $f)
                <tr>
                    <td style="font-family:monospace;font-size:12px">#{{ $f->fatura_no ?? $f->id }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($f->baslik ?? '—', 40) }}</td>
                    <td><strong>₺{{ number_format((float) ($f->tutar ?? 0), 2, ',', '.') }}</strong></td>
                    <td>
                        @if(($f->durum ?? 0) == 1)
                            <span class="badge badge-success">✓ Ödendi</span>
                        @elseif(($f->durum ?? 0) == 2)
                            <span class="badge badge-danger">✗ İptal</span>
                        @else
                            <span class="badge badge-warning">⏳ Bekliyor</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $f->tarih ?? '—' }}</td>
                    <td>
                        @if(!empty($f->dosya))
                            <a href="{{ asset('storage/' . $f->dosya) }}" target="_blank" class="badge badge-brand" style="text-decoration:none">
                                <i data-lucide="paperclip" style="width:11px;height:11px"></i>
                                İndir
                            </a>
                        @else <span style="color:var(--text-muted)">—</span> @endif
                    </td>
                    <td class="text-right">
                        <div style="display:inline-flex;gap:6px;justify-content:flex-end;align-items:center">
                        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.goster'))
                        <a href="{{ route('admin.faturalar.goster', $f->id) }}" target="_blank" class="table-action" title="Görüntüle / Yazdır">
                            <i data-lucide="printer"></i>
                        </a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.detay'))
                        <a href="{{ route('admin.faturalar.detay', $f->id) }}" class="table-action" title="Detay">
                            <i data-lucide="eye"></i>
                        </a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.duzenle'))
                        <a href="{{ route('admin.faturalar.duzenle', $f->id) }}" class="table-action" title="Düzenle">
                            <i data-lucide="pencil"></i>
                        </a>
                        @endif
                        @if(($f->durum ?? 0) != 1 && \Illuminate\Support\Facades\Route::has('admin.faturalar.durum'))
                        <form action="{{ route('admin.faturalar.durum', [$f->id, 'odendi']) }}" method="POST" style="display:inline"
                              onsubmit="return confirm('Bu fatura ÖDENDİ olarak işaretlensin mi?')">
                            @csrf
                            <button type="submit" class="table-action" style="color:#16a34a" title="Onayla (Ödendi)">
                                <i data-lucide="check"></i>
                            </button>
                        </form>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.faturalar.sil'))
                        <form action="{{ route('admin.faturalar.sil', $f->id) }}" method="POST" style="display:inline"
                              onsubmit="return confirm('Fatura silinsin mi? Bu işlem geri alınamaz.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="table-action" style="color:#dc2626" title="Sil">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                        @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty-state">
                        <i data-lucide="receipt" class="empty-state-icon"></i>
                        <h4>Fatura yok</h4>
                        <p>Bu müşterinin henüz faturası bulunmuyor.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>