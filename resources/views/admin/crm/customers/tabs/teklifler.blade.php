{{-- Tab: Teklifler --}}

<div class="section" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <h4 style="font-size:14px;font-weight:600">
            💼 Teklifler
        </h4>
        <button type="button" onclick="document.getElementById('yeniTeklifModal').classList.add('show')" class="btn btn-primary btn-sm">
            <i data-lucide="plus"></i>
            <span>Yeni Teklif</span>
        </button>
    </div>

    @php
        $tumTeklifler = collect($teklifler ?? $customer->teklifler ?? []);
        if (isset($musteriTeklifleri) && is_iterable($musteriTeklifleri)) {
            foreach ($musteriTeklifleri as $mt) {
                $tumTeklifler->push((object) [
                    'baslik'        => $mt->paket_adi ?? '—',
                    'toplam_tl'     => $mt->tutar ?? 0,
                    'odeme_yontemi' => $mt->odeme_yontemi ?? null,
                    'durum'         => $mt->durum ?? 'gonderildi',
                    'created_at'    => $mt->created_at ?? null,
                    'token'         => $mt->token ?? null,
                    'crm_teklif_id' => $mt->id ?? null,
                    // Düzenleme modalı için ham alanlar
                    'duz_paket_adi'     => $mt->paket_adi ?? '',
                    'duz_tutar'         => $mt->tutar ?? 0,
                    'duz_odeme'         => $mt->odeme_yontemi ?? 'online',
                    'duz_mesaj'         => $mt->mesaj ?? '',
                ]);
            }
        }
        // Admin panelinden oluşturulan Paket Teklifleri (paket_teklifleri)
        if (isset($paketTeklifleri) && is_iterable($paketTeklifleri)) {
            foreach ($paketTeklifleri as $pt) {
                $tumTeklifler->push((object) [
                    'baslik'        => $pt->baslik ?? 'Paket Teklifi',
                    'toplam_tl'     => $pt->toplam_tl ?? 0,
                    'odeme_yontemi' => null,
                    'durum'         => $pt->durum ?? 'beklemede',
                    'created_at'    => $pt->created_at ?? null,
                    'token'         => $pt->token ?? null,
                    'seo'           => $pt->seo ?? null,
                    'paket_teklif_id' => $pt->id ?? null,
                ]);
            }
        }
        $tumTeklifler = $tumTeklifler->sortByDesc('created_at')->values();
    @endphp

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Teklif</th>
                    <th>Tutar</th>
                    <th>Ödeme</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tumTeklifler as $t)
                @php
                    $teklifLink = null;
                    if (!empty($t->seo) && \Illuminate\Support\Facades\Route::has('teklif.detay.slug')) {
                        $teklifLink = route('teklif.detay.slug', $t->seo);
                    } elseif (!empty($t->token) && !empty($t->paket_teklif_id) && \Illuminate\Support\Facades\Route::has('teklif.detay.public')) {
                        $teklifLink = route('teklif.detay.public', $t->token);
                    } elseif (!empty($t->paket_teklif_id) && \Illuminate\Support\Facades\Route::has('admin.paketler.teklif.goruntule')) {
                        $teklifLink = route('admin.paketler.teklif.goruntule', $t->paket_teklif_id);
                    } elseif (!empty($t->token) && \Illuminate\Support\Facades\Route::has('bayi.odeme.show')) {
                        $teklifLink = route('bayi.odeme.show', $t->token);
                    }
                @endphp
                <tr @if($teklifLink) class="teklif-row" data-href="{{ $teklifLink }}" style="cursor:pointer" @endif>
                    <td>
                        @if($teklifLink)
                            <a href="{{ $teklifLink }}" target="_blank" class="teklif-isim-link"
                               style="color:var(--brand);text-decoration:none;font-weight:700">{{ $t->baslik ?? '—' }}</a>
                        @else
                            <strong>{{ $t->baslik ?? '—' }}</strong>
                        @endif
                    </td>
                    <td><strong style="color:var(--brand)">₺{{ number_format($t->toplam_tl ?? 0, 2, ',', '.') }}</strong></td>
                    <td>
                        @if(($t->odeme_yontemi ?? '') === 'online')
                            <span class="badge badge-info">💳 Online</span>
                        @elseif(($t->odeme_yontemi ?? '') === 'havale')
                            <span class="badge badge-warning">🏦 Havale</span>
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td>
                        @php $d = $t->durum ?? 'beklemede'; @endphp
                        @if(in_array($d, ['onaylandi','odendi','paid']))<span class="badge badge-success">✓ Onay</span>
                        @elseif(in_array($d, ['reddedildi','iptal']))<span class="badge badge-danger">✗ Red</span>
                        @else<span class="badge badge-warning">⏳ Bekliyor</span>@endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)">{{ $t->created_at ?? '—' }}</td>
                    <td class="text-right">
                        <div style="display:inline-flex;gap:4px;align-items:center;justify-content:flex-end">
                            @if(!empty($t->paket_teklif_id) && !empty($t->seo) && \Illuminate\Support\Facades\Route::has('teklif.detay.slug'))
                                <a href="{{ route('teklif.detay.slug', $t->seo) }}" target="_blank" class="table-action" title="Teklifi Görüntüle">
                                    <i data-lucide="eye"></i>
                                </a>
                            @elseif(!empty($t->paket_teklif_id) && !empty($t->token) && \Illuminate\Support\Facades\Route::has('teklif.detay.public'))
                                <a href="{{ route('teklif.detay.public', $t->token) }}" target="_blank" class="table-action" title="Teklifi Görüntüle">
                                    <i data-lucide="eye"></i>
                                </a>
                            @elseif(!empty($t->paket_teklif_id) && \Illuminate\Support\Facades\Route::has('admin.paketler.teklif.goruntule'))
                                <a href="{{ route('admin.paketler.teklif.goruntule', $t->paket_teklif_id) }}" class="table-action" title="Teklifi Görüntüle">
                                    <i data-lucide="eye"></i>
                                </a>
                            @elseif(!empty($t->token) && \Illuminate\Support\Facades\Route::has('bayi.odeme.show'))
                                <a href="{{ route('bayi.odeme.show', $t->token) }}" target="_blank" class="table-action" title="Görüntüle / Yazdır">
                                    <i data-lucide="printer"></i>
                                </a>
                            @endif

                            @if(!empty($t->paket_teklif_id) && \Illuminate\Support\Facades\Route::has('admin.paketler.teklif.duzenle'))
                                <a href="{{ route('admin.paketler.teklif.duzenle', $t->paket_teklif_id) }}" class="table-action" style="color:var(--brand-dark)" title="Teklifi Düzenle">
                                    <i data-lucide="pencil"></i>
                                </a>
                            @endif

                            @if(!empty($t->paket_teklif_id) && \Illuminate\Support\Facades\Route::has('admin.paketler.teklif.sil'))
                                <button type="button" class="table-action" style="color:var(--danger)"
                                        title="Teklifi Sil" onclick="silCrmTeklif({{ $t->paket_teklif_id }}, '{{ addslashes($t->baslik ?? 'Teklif') }}')">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            @endif

                            @if(!empty($t->crm_teklif_id) && \Illuminate\Support\Facades\Route::has('admin.crm.musteriler.teklif-guncelle'))
                                <button type="button" class="table-action" style="color:var(--brand-dark)"
                                        title="Teklifi Düzenle"
                                        data-duzenle-teklif="{{ $t->crm_teklif_id }}"
                                        data-paket-adi="{{ $t->duz_paket_adi ?? '' }}"
                                        data-tutar="{{ $t->duz_tutar ?? 0 }}"
                                        data-odeme="{{ $t->duz_odeme ?? 'online' }}"
                                        data-mesaj="{{ $t->duz_mesaj ?? '' }}"
                                        onclick="ozelTeklifDuzenleAc(this)">
                                    <i data-lucide="pencil"></i>
                                </button>
                            @endif

                            @if(!empty($t->crm_teklif_id) && \Illuminate\Support\Facades\Route::has('admin.crm.musteriler.teklif-sil'))
                                <button type="button" class="table-action" style="color:var(--danger)"
                                        title="Teklifi Sil" onclick="silOzelTeklif({{ $t->crm_teklif_id }}, '{{ addslashes($t->baslik ?? 'Teklif') }}')">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            @endif

                            @if(empty($t->paket_teklif_id) && empty($t->token) && empty($t->crm_teklif_id))
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="empty-state">
                        <i data-lucide="briefcase" class="empty-state-icon"></i>
                        <h4>Teklif yok</h4>
                        <p>Yukarıdaki "Yeni Teklif" butonu ile başla.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Paket teklif sil formları (HTML iç içe form yasak — dışarıda) --}}
@if(\Illuminate\Support\Facades\Route::has('admin.paketler.teklif.sil') && isset($paketTeklifleri) && is_iterable($paketTeklifleri))
    @foreach($paketTeklifleri as $pt)
        <form id="del-crm-teklif-{{ $pt->id }}" action="{{ route('admin.paketler.teklif.sil', $pt->id) }}" method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
    <script>
    function silCrmTeklif(id, baslik) {
        if (confirm('Bu teklifi silmek istediğinize emin misiniz?\n\n' + baslik + '\n\nBu işlem geri alınamaz.')) {
            var f = document.getElementById('del-crm-teklif-' + id);
            if (f) f.submit();
        }
    }
    </script>
@endif

{{-- CRM müşteri teklifleri (crm_musteri_teklifleri) sil formları --}}
@if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.teklif-sil') && isset($musteriTeklifleri) && is_iterable($musteriTeklifleri))
    @foreach($musteriTeklifleri as $mt)
        <form id="del-ozel-teklif-{{ $mt->id }}" action="{{ route('admin.crm.musteriler.teklif-sil', [$customer->id, $mt->id]) }}" method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
    <script>
    function silOzelTeklif(id, baslik) {
        if (confirm('Bu teklifi silmek istediğinize emin misiniz?\n\n' + baslik + '\n\nBu işlem geri alınamaz.')) {
            var f = document.getElementById('del-ozel-teklif-' + id);
            if (f) f.submit();
        }
    }
    </script>
@endif

{{-- Teklif Düzenle Modal --}}
@if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.teklif-guncelle'))
<div id="teklifDuzenleModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Teklifi Düzenle</h3>
            <button type="button" onclick="document.getElementById('teklifDuzenleModal').classList.remove('show')" class="icon-btn">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form id="teklifDuzenleForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Teklif / Paket Adı <span class="required">*</span></label>
                        <input type="text" id="duz_paket_adi" name="paket_adi" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tutar (₺) <span class="required">*</span></label>
                        <input type="number" id="duz_tutar" name="tutar" step="0.01" min="0" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ödeme Yöntemi <span class="required">*</span></label>
                        <select id="duz_odeme" name="odeme_yontemi" required class="form-select">
                            <option value="online">💳 Online (PayTR)</option>
                            <option value="havale">🏦 Havale / EFT</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Açıklama / Mesaj (opsiyonel)</label>
                        <textarea id="duz_mesaj" name="mesaj" rows="8" class="form-textarea teklif-aciklama-buyuk" placeholder="Müşteriye gönderilecek özel not..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('teklifDuzenleModal').classList.remove('show')" class="btn btn-secondary">İptal</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Değişiklikleri Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function ozelTeklifDuzenleAc(btn) {
    const id     = btn.getAttribute('data-duzenle-teklif');
    const form   = document.getElementById('teklifDuzenleForm');
    // Route: admin/crm/musteriler/{customer}/teklif/{talep}/guncelle
    form.action = "{{ url('admin/crm/musteriler/' . ($customer->id ?? 0) . '/teklif') }}/" + id + "/guncelle";
    document.getElementById('duz_paket_adi').value = btn.getAttribute('data-paket-adi') || '';
    document.getElementById('duz_tutar').value     = btn.getAttribute('data-tutar') || '';
    document.getElementById('duz_odeme').value     = btn.getAttribute('data-odeme') || 'online';
    document.getElementById('duz_mesaj').value     = btn.getAttribute('data-mesaj') || '';
    document.getElementById('teklifDuzenleModal').classList.add('show');
}
document.getElementById('teklifDuzenleModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
@endif

{{-- Yeni Teklif Modal --}}
<div id="yeniTeklifModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ Yeni Teklif Oluştur</h3>
            <button type="button" onclick="document.getElementById('yeniTeklifModal').classList.remove('show')" class="icon-btn">
                <i data-lucide="x"></i>
            </button>
        </div>
        <form action="{{ route('admin.crm.musteriler.ozel-teklif', $customer->id) }}" method="POST">
            @csrf
            <div class="modal-body">
                <div style="font-size:12px;color:var(--text-muted);margin-bottom:14px">
                    Müşteri: <strong style="color:var(--text)">{{ $customer->adi ?? '' }} {{ $customer->soyad ?? '' }}</strong> ({{ $customer->email ?? '' }})
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Ürün Tipi <span class="required">*</span></label>
                        <select id="teklif_urun_tipi" name="urun_tipi" required class="form-select" onchange="teklifTipDegisti()">
                            <option value="">— Seç —</option>
                            <option value="hosting">🌐 Web Hosting</option>
                            <option value="paket">📦 Web Paketi</option>
                            <option value="ozel">✏️ Özel / Manuel</option>
                        </select>
                    </div>

                    <div class="form-group" id="teklif_urun_box_hosting" style="display:none">
                        <label class="form-label">Hosting Paketi</label>
                        <select id="teklif_urun_hosting" class="form-select" onchange="teklifUrunSecildi('hosting')">
                            <option value="">— Seç —</option>
                            @foreach($hostingPaketleri ?? [] as $hp)
                                <option value="{{ $hp->id }}" data-adi="{{ $hp->adi }}" data-tutar="{{ $hp->tutar }}">{{ $hp->adi }} — ₺{{ number_format((float) $hp->tutar, 2, ',', '.') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="teklif_urun_box_paket" style="display:none">
                        <label class="form-label">Paket (yazmaya başla)</label>
                        <input type="text" id="teklif_urun_paket_search" list="teklif_paket_options" autocomplete="off" class="form-input" placeholder="Paket adı..." oninput="teklifPaketSeciliyor(this)">
                        <datalist id="teklif_paket_options">
                            @foreach($webPaketleri ?? [] as $wp)
                                <option value="{{ $wp->adi }}" data-tutar="{{ $wp->tutar }}">₺{{ number_format((float) $wp->tutar, 2, ',', '.') }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Teklif / Paket Adı <span class="required">*</span></label>
                        <input type="text" id="teklif_paket_adi" name="paket_adi" required class="form-input" placeholder="Yukarıdan seçince otomatik dolar">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tutar (₺) <span class="required">*</span></label>
                        <input type="number" id="teklif_tutar" name="tutar" step="0.01" min="0" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ödeme Yöntemi <span class="required">*</span></label>
                        <select name="odeme_yontemi" required class="form-select">
                            <option value="online">💳 Online (PayTR)</option>
                            <option value="havale">🏦 Havale / EFT</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Açıklama / Mesaj (opsiyonel)</label>
                        <textarea name="mesaj" rows="8" class="form-textarea teklif-aciklama-buyuk" placeholder="Müşteriye gönderilecek özel not..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('yeniTeklifModal').classList.remove('show')" class="btn btn-secondary">İptal</button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="send"></i>
                    <span>Teklifi Gönder</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function teklifTipDegisti() {
    const tip = document.getElementById('teklif_urun_tipi').value;
    document.getElementById('teklif_urun_box_hosting').style.display = (tip === 'hosting') ? '' : 'none';
    document.getElementById('teklif_urun_box_paket').style.display = (tip === 'paket') ? '' : 'none';
    if (tip === 'ozel') document.getElementById('teklif_paket_adi').focus();
}
function teklifUrunSecildi(tip) {
    if (tip === 'hosting') {
        const sel = document.getElementById('teklif_urun_hosting');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('teklif_paket_adi').value = opt.dataset.adi || opt.text;
            document.getElementById('teklif_tutar').value = parseFloat(opt.dataset.tutar || 0).toFixed(2);
        }
    }
}
function teklifPaketSeciliyor(input) {
    const dlist = document.getElementById('teklif_paket_options');
    const opt = Array.from(dlist.options).find(o => o.value === input.value);
    if (opt) {
        document.getElementById('teklif_paket_adi').value = opt.value;
        const tutar = parseFloat(opt.dataset.tutar || 0);
        if (tutar > 0) document.getElementById('teklif_tutar').value = tutar.toFixed(2);
    }
}
// Modal arka plan tıklama
document.getElementById('yeniTeklifModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>

<style>
    .teklif-row:hover { background: var(--brand-soft); }
    .teklif-isim-link:hover { text-decoration: underline; }

    /* Açıklama alanı: büyük, kalın, daha okunur font */
    .teklif-aciklama-buyuk {
        min-height: 180px;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.6;
        resize: vertical;
    }
    /* Modal'ı biraz genişlet ki açıklama rahat dursun */
    #teklifDuzenleModal .modal,
    #yeniTeklifModal .modal {
        max-width: 640px;
    }

    /* Sağ alttaki sabit mesaj/sohbet widget'ı tablo işlem butonlarının
       üstüne biniyordu. Tablonun altına ekstra boşluk + son satır işlem
       hücresine sağ boşluk vererek çakışmayı önlüyoruz. */
    .table-scroll { padding-bottom: 72px; }
    @media (max-width: 768px) {
        .table-scroll td.text-right { padding-right: 8px; }
    }
</style>
<script>
(function () {
    document.querySelectorAll('.teklif-row[data-href]').forEach(function (row) {
        row.addEventListener('click', function (e) {
            // İşlem butonları / link / form üzerine tıklamada satır yönlendirmesini engelle
            if (e.target.closest('a, button, form, .table-action')) return;
            window.open(row.getAttribute('data-href'), '_blank');
        });
    });
})();
</script>