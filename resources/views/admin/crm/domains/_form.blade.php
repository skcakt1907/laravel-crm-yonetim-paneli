{{--
    Paylaşılan domain/hosting formu - create + edit ortak
    (Domain Düzenle ekranıyla birebir aynı düzen; Hizmet çoklu-seçim dropdown,
     fiyat "Hizmet Fiyatları"ndan otomatik hesaplanır)
    Beklenenler:
        - $row (null veya satilanlar satırı - edit modunda)
        - $uyeler (collect)
        - $selectedUyeId (null veya int - create modunda)
        - $hizmetFiyatlari (collect: hizmet_fiyatlari — VDS olmayanlar)
        - $vdsSecenekleri (collect: hizmet_fiyatlari — etiketi VDS içerenler)
--}}
@php
    $isEdit = isset($row) && $row && !empty($row->id);
    $currentUyeId = old('uyeid', $row->uyeid ?? $selectedUyeId ?? null);
    $currentDurum = old('durum', $row->durum ?? 1);
    $currentDomain = old('domain', $row->domain ?? '');
    $currentTutar = old('tutar', $row->tutar ?? '');
    $currentBaslangic = old('baslangic_tarih', isset($row->baslangic_tarih) && $row->baslangic_tarih ? \Carbon\Carbon::parse($row->baslangic_tarih)->format('Y-m-d') : '');
    $currentBitis = old('bitis_tarih', isset($row->bitis_tarih) && $row->bitis_tarih ? \Carbon\Carbon::parse($row->bitis_tarih)->format('Y-m-d') : '');
    $currentMesaj = old('mesaj', $row->mesaj ?? '');
    $currentSaglayici = old('saglayici', $row->saglayici ?? '');

    // Seçili hizmetler (json kolon | eski kayıtlar için boş)
    $currentHizmetler = old('hizmetler', []);
    if (empty($currentHizmetler) && $isEdit && !empty($row->hizmetler)) {
        $decoded = is_array($row->hizmetler) ? $row->hizmetler : json_decode($row->hizmetler, true);
        if (is_array($decoded)) $currentHizmetler = $decoded;
    }
    if (empty($currentHizmetler) && !$isEdit) $currentHizmetler = ['Domain']; // varsayılan

    $currentVds = old('vds', $row->vds ?? '');

    $hizmetFiyatlari = $hizmetFiyatlari ?? collect();
    $vdsSecenekleri  = $vdsSecenekleri ?? collect();

    // Seçili üyenin etiketi (edit'te salt-okunur kutu için)
    $seciliUyeEtiket = '';
    foreach ($uyeler as $u) {
        if ($currentUyeId == $u->id) {
            $seciliUyeEtiket = trim(($u->ad ?? '').' '.($u->soyad ?? ''));
            if (!empty($u->firmaadi)) $seciliUyeEtiket = $u->firmaadi.' — '.$seciliUyeEtiket;
            break;
        }
    }
    // Canlı arama listesi (JS'e @json ile aktarılır — @json içine closure YAZMA, Blade patlıyor)
    $uyeAramaListesi = [];
    foreach ($uyeler as $u) {
        $adSoyad = trim(($u->ad ?? '') . ' ' . ($u->soyad ?? ''));
        $uyeAramaListesi[] = [
            'id'    => (int) $u->id,
            'ad'    => $adSoyad !== '' ? $adSoyad : '—',
            'firma' => (string) ($u->firmaadi ?? ''),
            'email' => (string) ($u->email ?? ''),
            'arama' => mb_strtolower(($u->ad ?? '') . ' ' . ($u->soyad ?? '') . ' ' . ($u->firmaadi ?? '') . ' ' . ($u->email ?? ''), 'UTF-8'),
        ];
    }

    /*
     * DÜZELTME (31.07.2026) — "domain eklerken müşteri listede çıkmıyor".
     * Liste yalnızca `uyeler`den (giriş hesabı) kuruluyordu; CRM'de kayıtlı olup
     * üye hesabı OLMAYAN müşteriler (80 kayıt) hiç çıkmıyordu.
     * Örn: "İlhan Bey #726 / info@marmarissunblue.com".
     * Bunlar da listeye ekleniyor; id'leri "crm:726" biçiminde gider, controller
     * bunu ayırıp domaini `crm_musteri_id` ile bağlar (üye hesabı açılmaz).
     */
    foreach (($crmMusteriler ?? collect()) as $m) {
        $uyeAramaListesi[] = [
            'id'    => 'crm:' . $m->id,
            'ad'    => trim((string) ($m->adi ?? '')) !== '' ? $m->adi : '—',
            'firma' => (string) ($m->unvan ?? ''),
            'email' => (string) ($m->email ?? ''),
            'arama' => mb_strtolower(($m->adi ?? '') . ' ' . ($m->unvan ?? '') . ' ' . ($m->email ?? ''), 'UTF-8'),
            'crm'   => true,
        ];
    }
@endphp

<style>
.dm-uye-wrap{position:relative}
.dm-uye-wrap #dm-uye-input{padding-right:34px}
.dm-uye-temizle{position:absolute;right:11px;top:11px;cursor:pointer;font-size:19px;
    line-height:1;color:var(--text-muted,#9a9d90);user-select:none}
.dm-uye-temizle:hover{color:var(--danger,#ef4444)}
.dm-uye-liste{display:none;position:absolute;z-index:50;left:0;right:0;top:calc(100% + 4px);
    max-height:290px;overflow-y:auto;background:var(--card,#fff);
    border:1px solid var(--border,#e6e6dc);border-radius:10px;
    box-shadow:0 12px 34px rgba(31,36,25,.16)}
.dm-uye-sat{display:flex;align-items:center;gap:11px;padding:9px 13px;cursor:pointer;
    border-bottom:1px solid var(--border,#f0efe6)}
.dm-uye-sat:last-child{border-bottom:0}
.dm-uye-sat.aktif{background:var(--brand-soft,rgba(184,182,46,.13))}
.dm-uye-harf{width:31px;height:31px;border-radius:50%;flex-shrink:0;
    background:linear-gradient(135deg,#b8b62e,#8a8a1f);color:#000;
    font-weight:700;font-size:12.5px;display:inline-flex;align-items:center;justify-content:center}
.dm-uye-bilgi{flex:1;min-width:0;display:flex;flex-direction:column}
.dm-uye-ad{font-size:13px;font-weight:600;color:var(--text,#1f2419);
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dm-uye-mail{font-size:11px;color:var(--text-muted,#8a8d80);margin-top:1px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dm-uye-no{font-size:10.5px;color:var(--text-muted,#9a9d90);font-family:monospace;flex-shrink:0}
.dm-uye-bos{padding:16px;text-align:center;font-size:12.5px;color:var(--text-muted,#8a8d80)}
</style>

<form action="{{ $isEdit ? route('admin.crm.domains.update', ['kaynak' => 'manuel', 'id' => $row->id]) : route('admin.crm.domains.store') }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="section">
        {{-- SATIR 1: Domain Adı | Müşteri | Domain Başlangıç --}}
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Domain Adı <span class="required">*</span></label>
                <input type="text" name="domain" value="{{ $currentDomain }}" required maxlength="190" class="form-input" placeholder="ornek.com" style="text-transform:lowercase">
            </div>

            <div class="form-group">
                <label class="form-label">Müşteri <span class="required">*</span></label>
                {{-- Tek kutulu canlı arama: yazdıkça altta sonuçlar açılır, tıklayınca seçilir.
                     Müşteri değiştirmek MAİL GÖNDERMEZ; hatırlatma mailleri yalnızca günlük
                     domain:yenileme-hatirlat cron'undan, bitişe 30/15/7 gün kalanlara çıkar. --}}
                <div class="dm-uye-wrap" id="dm-uye-wrap">
                    <input type="hidden" name="uyeid" id="dm-uye-id" value="{{ $currentUyeId }}">
                    <input type="text" id="dm-uye-input" class="form-input" autocomplete="off"
                           value="{{ $seciliUyeEtiket }}"
                           placeholder="Ad, firma veya e-posta yazın...">
                    <span class="dm-uye-temizle" id="dm-uye-temizle" title="Seçimi temizle"
                          style="{{ $currentUyeId ? '' : 'display:none' }}">&times;</span>
                    <div class="dm-uye-liste" id="dm-uye-liste"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Domain Başlangıç</label>
                <input type="date" name="baslangic_tarih" id="baslangic_tarih" value="{{ $currentBaslangic }}" class="form-input">
            </div>
        </div>

        {{-- SATIR 2: Kayıt Firması | Domain Bitiş | Fiyat (otomatik) --}}
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Domain Kayıt Firması <span style="font-weight:400;color:var(--text-muted);font-size:11px">(Örn: Godaddy)</span></label>
                <input type="text" name="saglayici" value="{{ $currentSaglayici }}" maxlength="100" class="form-input" placeholder="METUNİC, ResellerClub, GoDaddy...">
            </div>

            <div class="form-group">
                <label class="form-label">Domain Bitiş</label>
                <input type="date" name="bitis_tarih" id="bitis_tarih" value="{{ $currentBitis }}" class="form-input">
                <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
                    <button type="button" onclick="setBitis(1)" class="btn btn-ghost btn-sm" style="font-size:11px">+1 Yıl</button>
                    <button type="button" onclick="setBitis(2)" class="btn btn-ghost btn-sm" style="font-size:11px">+2 Yıl</button>
                    <button type="button" onclick="setBitis(3)" class="btn btn-ghost btn-sm" style="font-size:11px">+3 Yıl</button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Fiyat</label>
                <input type="text" id="fiyat-goster" value="{{ $currentTutar !== '' ? number_format((float)$currentTutar, 2, ',', '.') : '' }}" class="form-input" readonly style="background:var(--bg-subtle);color:var(--text-muted)" placeholder="Hizmet seçince otomatik">
                <input type="hidden" name="tutar" id="tutar-input" value="{{ $currentTutar }}">
                <small class="form-help">Hizmet Fiyatları sayfasındaki sabit fiyatlardan otomatik toplanır</small>
            </div>
        </div>

        {{-- SATIR 3: Hizmet (çoklu seçim dropdown) | VDS --}}
        <div class="form-grid" style="grid-template-columns:2fr 1fr">
            <div class="form-group">
                <label class="form-label">Hizmet</label>
                <div id="hizmet-dropdown" style="position:relative">
                    {{-- seçili etiketler --}}
                    <div id="hizmet-kutu" onclick="hizmetToggle()" class="form-input" style="min-height:42px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;cursor:pointer;padding:6px 34px 6px 10px">
                        <span id="hizmet-bos" style="color:var(--text-muted);font-size:13px;{{ count($currentHizmetler) ? 'display:none' : '' }}">— Hizmet seç (birden fazla seçilebilir) —</span>
                    </div>
                    <i data-lucide="chevron-down" style="position:absolute;right:10px;top:12px;width:16px;height:16px;pointer-events:none;color:var(--text-muted)"></i>
                    {{-- açılır liste --}}
                    <div id="hizmet-liste" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--bg-card,#fff);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:0 12px 32px rgba(0,0,0,.12);z-index:50;max-height:240px;overflow:auto;padding:6px">
                        @forelse($hizmetFiyatlari as $hf)
                            <label style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer" onmouseover="this.style.background='var(--bg-subtle)'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" class="hizmet-cb" name="hizmetler[]" value="{{ $hf->etiket }}" data-fiyat="{{ (float) $hf->fiyat }}"
                                       {{ in_array($hf->etiket, $currentHizmetler) ? 'checked' : '' }} style="width:auto;margin:0" onchange="hizmetGuncelle()">
                                <span style="flex:1;font-size:13px;font-weight:600">{{ $hf->etiket }}</span>
                                <span style="font-size:12px;color:var(--brand);font-weight:700">₺{{ number_format((float)$hf->fiyat, 2, ',', '.') }}</span>
                            </label>
                        @empty
                            <div style="padding:10px;font-size:12.5px;color:var(--text-muted)">
                                Hizmet Fiyatları sayfasında kayıt yok —
                                <a href="{{ \Illuminate\Support\Facades\Route::has('admin.hizmet-fiyatlari.index') ? route('admin.hizmet-fiyatlari.index') : '#' }}">önce fiyat ekleyin</a>.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">VDS</label>
                <select name="vds" id="vds-select" class="form-select" onchange="hizmetGuncelle()">
                    <option value="" data-fiyat="0">— Yok —</option>
                    @foreach($vdsSecenekleri as $v)
                        <option value="{{ $v->etiket }}" data-fiyat="{{ (float) $v->fiyat }}" {{ $currentVds === $v->etiket ? 'selected' : '' }}>
                            {{ $v->etiket }} (₺{{ number_format((float)$v->fiyat, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- SATIR 4: Durum + Not --}}
        <div class="form-grid" style="grid-template-columns:1fr 2fr">
            <div class="form-group">
                <label class="form-label">Durum</label>
                <select name="durum" class="form-select">
                    <option value="1" {{ $currentDurum == 1 ? 'selected' : '' }}>🟢 Aktif</option>
                    <option value="0" {{ $currentDurum == 0 ? 'selected' : '' }}>🔴 Pasif</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Not <span style="font-weight:400;color:var(--text-muted);font-size:11px">(opsiyonel)</span></label>
                <input type="text" name="mesaj" value="{{ $currentMesaj }}" maxlength="2000" class="form-input" placeholder="DNS bilgileri, nameserverler, özel notlar...">
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>{{ $isEdit ? 'Kaydet' : 'Ekle' }}</span>
        </button>

        <div style="display:flex;gap:10px">
            @if($isEdit)
                <a href="{{ route('admin.crm.domains.show', ['kaynak' => 'manuel', 'id' => $row->id]) }}" class="btn btn-secondary" title="İncele">
                    <i data-lucide="eye"></i>
                </a>
                <button type="submit" form="domainSilForm" class="btn btn-danger"
                        onclick="return confirm('{{ addslashes($row->domain ?? 'Bu kayıt') }} silinsin mi?');">
                    <i data-lucide="trash-2"></i>
                    <span>Sil</span>
                </button>
            @endif
            <a href="{{ route('admin.crm.domains.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
        </div>
    </div>
</form>

@if($isEdit)
<form id="domainSilForm" action="{{ route('admin.crm.domains.destroy', ['kaynak' => 'manuel', 'id' => $row->id]) }}" method="POST" style="display:none">
    @csrf @method('DELETE')
</form>
@endif

<script>
// ---- Müşteri canlı arama (tek kutu + açılır liste) ----
(function() {
    const UYELER = @json($uyeAramaListesi);

    const wrap    = document.getElementById('dm-uye-wrap');
    const input   = document.getElementById('dm-uye-input');
    const hidden  = document.getElementById('dm-uye-id');
    const liste   = document.getElementById('dm-uye-liste');
    const temizle = document.getElementById('dm-uye-temizle');
    if (!wrap || !input || !hidden || !liste) return;

    let sonuclar = [];
    let aktif = -1;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function etiket(u) {
        return (u.firma ? u.firma + ' — ' : '') + u.ad;
    }

    function kapat() {
        liste.style.display = 'none';
        aktif = -1;
    }

    function ac(items) {
        sonuclar = items;
        aktif = -1;

        if (!items.length) {
            liste.innerHTML = '<div class="dm-uye-bos">Eşleşen müşteri bulunamadı</div>';
            liste.style.display = 'block';
            return;
        }

        liste.innerHTML = items.map((u, i) => `
            <div class="dm-uye-sat" data-i="${i}">
                <span class="dm-uye-harf">${esc((u.ad || '?').charAt(0).toUpperCase())}</span>
                <span class="dm-uye-bilgi">
                    <span class="dm-uye-ad">${esc(etiket(u))}</span>
                    <span class="dm-uye-mail">${esc(u.email || '—')}</span>
                </span>
                <span class="dm-uye-no">#${u.id}</span>
            </div>
        `).join('');

        liste.querySelectorAll('.dm-uye-sat').forEach(el => {
            el.addEventListener('click', () => sec(parseInt(el.dataset.i)));
            el.addEventListener('mouseenter', () => { aktif = parseInt(el.dataset.i); isaretle(); });
        });

        liste.style.display = 'block';
    }

    function isaretle() {
        liste.querySelectorAll('.dm-uye-sat').forEach((el, i) => {
            el.classList.toggle('aktif', i === aktif);
        });
    }

    function sec(i) {
        const u = sonuclar[i];
        if (!u) return;
        hidden.value = u.id;
        input.value = etiket(u);
        if (temizle) temizle.style.display = '';
        kapat();
    }

    function ara(q) {
        q = q.toLowerCase().trim();
        if (!q) return UYELER.slice(0, 30);
        return UYELER.filter(u => u.arama.includes(q)).slice(0, 30);
    }

    input.addEventListener('input', function () {
        hidden.value = '';                 // yazmaya başlayınca seçim düşer
        if (temizle) temizle.style.display = this.value ? '' : 'none';
        ac(ara(this.value));
    });

    input.addEventListener('focus', function () {
        ac(ara(this.value === '' ? '' : this.value));
    });

    input.addEventListener('keydown', function (e) {
        if (liste.style.display === 'none' || !sonuclar.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            aktif = Math.min(aktif + 1, sonuclar.length - 1);
            isaretle();
            const el = liste.querySelector(`[data-i="${aktif}"]`);
            if (el) el.scrollIntoView({block: 'nearest'});
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            aktif = Math.max(aktif - 1, 0);
            isaretle();
            const el = liste.querySelector(`[data-i="${aktif}"]`);
            if (el) el.scrollIntoView({block: 'nearest'});
        } else if (e.key === 'Enter') {
            if (aktif >= 0) { e.preventDefault(); sec(aktif); }
        } else if (e.key === 'Escape') {
            kapat();
        }
    });

    if (temizle) {
        temizle.addEventListener('click', function () {
            hidden.value = '';
            input.value = '';
            this.style.display = 'none';
            input.focus();
            ac(ara(''));
        });
    }

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) kapat();
    });

    // Seçim yapılmadan gönderilmesin
    const form = wrap.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!hidden.value) {
                e.preventDefault();
                input.focus();
                ac(ara(input.value));
                alert('Lütfen listeden bir müşteri seçin.');
            }
        });
    }
})();

// ---- Bitiş tarihi hızlı butonlar ----
function setBitis(yil) {
    const baslangic = document.getElementById('baslangic_tarih').value;
    const baseDate = baslangic ? new Date(baslangic) : new Date();
    baseDate.setFullYear(baseDate.getFullYear() + yil);
    document.getElementById('bitis_tarih').value =
        baseDate.getFullYear() + '-' + String(baseDate.getMonth() + 1).padStart(2, '0') + '-' + String(baseDate.getDate()).padStart(2, '0');
}

// ---- Hizmet çoklu-seçim dropdown ----
function hizmetToggle() {
    const l = document.getElementById('hizmet-liste');
    l.style.display = l.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('hizmet-dropdown').contains(e.target)) {
        document.getElementById('hizmet-liste').style.display = 'none';
    }
});

// Seçilenleri etiket olarak göster + fiyatı otomatik topla
function hizmetGuncelle() {
    const kutu = document.getElementById('hizmet-kutu');
    const bos = document.getElementById('hizmet-bos');
    kutu.querySelectorAll('.hizmet-tag').forEach(t => t.remove());

    let toplam = 0, adet = 0;
    document.querySelectorAll('.hizmet-cb:checked').forEach(cb => {
        adet++;
        toplam += parseFloat(cb.dataset.fiyat || 0);
        const tag = document.createElement('span');
        tag.className = 'hizmet-tag';
        tag.style.cssText = 'background:var(--bg-subtle);border:1px solid var(--border);border-radius:6px;padding:3px 10px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px';
        tag.textContent = cb.value;
        kutu.insertBefore(tag, bos);
    });

    const vds = document.getElementById('vds-select');
    if (vds && vds.selectedIndex >= 0) {
        toplam += parseFloat(vds.options[vds.selectedIndex].dataset.fiyat || 0);
    }

    bos.style.display = adet ? 'none' : '';
    document.getElementById('fiyat-goster').value = toplam > 0
        ? toplam.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : '';
    document.getElementById('tutar-input').value = toplam > 0 ? toplam.toFixed(2) : '';
}

// İlk yüklemede seçili hizmet etiketlerini çiz (edit modu)
document.addEventListener('DOMContentLoaded', function() {
    const secili = document.querySelectorAll('.hizmet-cb:checked').length;
    @if($isEdit)
        // Edit'te kayıtlı tutarı koru; hizmet değiştirilirse yeniden hesaplanır
        if (secili) {
            const kutu = document.getElementById('hizmet-kutu');
            const bos = document.getElementById('hizmet-bos');
            document.querySelectorAll('.hizmet-cb:checked').forEach(cb => {
                const tag = document.createElement('span');
                tag.className = 'hizmet-tag';
                tag.style.cssText = 'background:var(--bg-subtle);border:1px solid var(--border);border-radius:6px;padding:3px 10px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px';
                tag.textContent = cb.value;
                kutu.insertBefore(tag, bos);
            });
            bos.style.display = 'none';
        }
    @else
        if (secili) hizmetGuncelle();
    @endif
});
</script>