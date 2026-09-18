{{--
    Sanal Tur Hosting formu - create + edit ortak
    Beklenenler: $row (null|kayıt), $uyeler, $selectedUyeId (create), $hizmetFiyatlari, $vdsSecenekleri
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
    $currentHizmetler = old('hizmetler', []);
    if (empty($currentHizmetler) && $isEdit && !empty($row->hizmetler)) {
        $decoded = is_array($row->hizmetler) ? $row->hizmetler : json_decode($row->hizmetler, true);
        if (is_array($decoded)) $currentHizmetler = $decoded;
    }
    if (empty($currentHizmetler) && !$isEdit) $currentHizmetler = ['Sanal Tur'];
    $currentVds = old('vds', $row->vds ?? '');
    $hizmetFiyatlari = $hizmetFiyatlari ?? collect();
    $vdsSecenekleri  = $vdsSecenekleri ?? collect();
    $seciliUyeEtiket = '';
    foreach ($uyeler as $u) {
        if ($currentUyeId == $u->id) {
            $seciliUyeEtiket = trim(($u->ad ?? '').' '.($u->soyad ?? ''));
            if (!empty($u->firmaadi)) $seciliUyeEtiket = $u->firmaadi.' — '.$seciliUyeEtiket;
            break;
        }
    }
@endphp
<form action="{{ $isEdit ? route('admin.crm.sanaltur-hosting.update', ['id' => $row->id]) : route('admin.crm.sanaltur-hosting.store') }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="section">
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Domain / Hosting Adı <span class="required">*</span></label>
                <input type="text" name="domain" value="{{ $currentDomain }}" required maxlength="190" class="form-input" placeholder="ornek.com" style="text-transform:lowercase">
            </div>
            <div class="form-group">
                <label class="form-label">Müşteri <span class="required">*</span></label>
                @if($isEdit)
                    <input type="text" value="{{ $seciliUyeEtiket ?: '—' }}" class="form-input" disabled style="background:var(--bg-subtle);color:var(--text-muted)">
                    <input type="hidden" name="uyeid" value="{{ $currentUyeId }}">
                @else
                    <input type="hidden" name="uyeid" id="stUyeId" value="{{ $currentUyeId }}" required>
                    <div class="st-cs" id="stCs">
                        <button type="button" class="st-cs-toggle" id="stCsToggle" onclick="stCsToggleClick(event)">
                            @php
                                $secliEt = '';
                                foreach ($uyeler as $u) { if ($currentUyeId == $u->id) { $secliEt = (!empty($u->firmaadi) ? '['.$u->firmaadi.'] ' : '').trim(($u->ad ?? '').' '.($u->soyad ?? '')).' — '.($u->email ?? ''); break; } }
                            @endphp
                            <span class="st-cs-label {{ $secliEt ? '' : 'is-placeholder' }}" id="stCsLabel">{{ $secliEt ?: '— Müşteri seç —' }}</span>
                            <i data-lucide="chevron-down" class="st-cs-caret"></i>
                        </button>
                        <div class="st-cs-menu" id="stCsMenu">
                            <div class="st-cs-ara">
                                <i data-lucide="search" style="width:15px;height:15px;color:#94a3b8"></i>
                                <input type="text" id="stCsArama" placeholder="Müşteri ara (ad, firma, e-posta)…" oninput="stCsFiltre()" onclick="event.stopPropagation()" autocomplete="off">
                            </div>
                            <div class="st-cs-liste" id="stCsListe">
                                @foreach($uyeler as $u)
                                    @php $et = (!empty($u->firmaadi) ? '['.$u->firmaadi.'] ' : '').trim(($u->ad ?? '').' '.($u->soyad ?? '')).' — '.($u->email ?? ''); @endphp
                                    <div class="st-cs-item {{ $currentUyeId == $u->id ? 'secili' : '' }}"
                                         data-id="{{ $u->id }}"
                                         data-ad="{{ $et }}"
                                         data-search="{{ strtolower(($u->ad ?? '').' '.($u->soyad ?? '').' '.($u->firmaadi ?? '').' '.($u->email ?? '')) }}"
                                         onclick="stCsSec(this)">
                                        {{ $et }}
                                    </div>
                                @endforeach
                            </div>
                            <div class="st-cs-bos" id="stCsBos" style="display:none">Eşleşen müşteri yok</div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Başlangıç</label>
                <input type="date" name="baslangic_tarih" id="baslangic_tarih" value="{{ $currentBaslangic }}" class="form-input">
            </div>
        </div>
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">Kayıt Firması <span style="font-weight:400;color:var(--text-muted);font-size:11px">(Örn: Godaddy)</span></label>
                <input type="text" name="saglayici" value="{{ $currentSaglayici }}" maxlength="100" class="form-input" placeholder="METUNİC, GoDaddy...">
            </div>
            <div class="form-group">
                <label class="form-label">Bitiş</label>
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
        <div class="form-grid" style="grid-template-columns:2fr 1fr">
            <div class="form-group">
                <label class="form-label">Hizmet</label>
                <div id="hizmet-dropdown" style="position:relative">
                    <div id="hizmet-kutu" onclick="hizmetToggle()" class="form-input" style="min-height:42px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;cursor:pointer;padding:6px 34px 6px 10px">
                        <span id="hizmet-bos" style="color:var(--text-muted);font-size:13px;{{ count($currentHizmetler) ? 'display:none' : '' }}">— Hizmet seç (birden fazla seçilebilir) —</span>
                    </div>
                    <i data-lucide="chevron-down" style="position:absolute;right:10px;top:12px;width:16px;height:16px;pointer-events:none;color:var(--text-muted)"></i>
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
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>{{ $isEdit ? 'Kaydet' : 'Ekle' }}</span>
        </button>
        <div style="display:flex;gap:10px">
            @if($isEdit)
                <a href="{{ route('admin.crm.sanaltur-hosting.show', ['id' => $row->id]) }}" class="btn btn-secondary" title="İncele">
                    <i data-lucide="eye"></i>
                </a>
                <button type="submit" form="stSilForm" class="btn btn-danger"
                        onclick="return confirm('{{ addslashes($row->domain ?? 'Bu kayıt') }} silinsin mi?');">
                    <i data-lucide="trash-2"></i>
                    <span>Sil</span>
                </button>
            @endif
            <a href="{{ route('admin.crm.sanaltur-hosting.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
        </div>
    </div>
</form>
@if($isEdit)
<form id="stSilForm" action="{{ route('admin.crm.sanaltur-hosting.destroy', ['id' => $row->id]) }}" method="POST" style="display:none">
    @csrf @method('DELETE')
</form>
@endif
<style>
    .st-cs{position:relative;width:100%}
    .st-cs-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-md,8px);background:var(--bg-card,#fff);font-size:14px;font-family:inherit;cursor:pointer;text-align:left;color:var(--text)}
    .st-cs.acik .st-cs-toggle{border-color:var(--brand)}
    .st-cs-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .st-cs-label.is-placeholder{color:var(--text-muted)}
    .st-cs-caret{width:16px;height:16px;color:var(--text-muted);flex-shrink:0;transition:transform .18s}
    .st-cs.acik .st-cs-caret{transform:rotate(180deg)}
    .st-cs-menu{position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:60;background:var(--bg-card,#fff);border:1px solid var(--border);border-radius:10px;box-shadow:0 12px 32px rgba(0,0,0,.14);padding:6px;display:none;max-height:320px;overflow:hidden;flex-direction:column}
    .st-cs.acik .st-cs-menu{display:flex}
    .st-cs-ara{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg-subtle);margin-bottom:6px}
    .st-cs-ara input{flex:1;border:none;background:none;outline:none;font-size:14px;font-family:inherit;color:var(--text)}
    .st-cs-liste{overflow-y:auto;max-height:240px}
    .st-cs-item{padding:9px 12px;border-radius:7px;font-size:13px;color:var(--text);cursor:pointer;transition:background .12s}
    .st-cs-item:hover{background:var(--bg-subtle)}
    .st-cs-item.secili{background:rgba(184,182,46,.16);font-weight:600}
    .st-cs-bos{padding:12px;text-align:center;color:var(--text-muted);font-size:13px}
</style>
<script>
/* ---- Aranabilir müşteri seçici ---- */
function stCsToggleClick(e){
    e.stopPropagation();
    var cs=document.getElementById('stCs');
    if(!cs) return;
    cs.classList.toggle('acik');
    if(cs.classList.contains('acik')){
        var a=document.getElementById('stCsArama');
        if(a){ a.value=''; stCsFiltre(); setTimeout(function(){a.focus();},30); }
    }
}
function stCsSec(el){
    document.getElementById('stUyeId').value = el.getAttribute('data-id');
    var lbl=document.getElementById('stCsLabel');
    lbl.textContent = el.getAttribute('data-ad');
    lbl.classList.remove('is-placeholder');
    document.querySelectorAll('#stCsListe .st-cs-item').forEach(function(i){ i.classList.remove('secili'); });
    el.classList.add('secili');
    document.getElementById('stCs').classList.remove('acik');
}
function stCsFiltre(){
    var q=(document.getElementById('stCsArama').value||'').toLocaleLowerCase('tr');
    var items=document.querySelectorAll('#stCsListe .st-cs-item');
    var gorunen=0;
    items.forEach(function(it){
        var s=(it.getAttribute('data-search')||'');
        var esle = q==='' || s.indexOf(q)!==-1;
        it.style.display = esle ? '' : 'none';
        if(esle) gorunen++;
    });
    var bos=document.getElementById('stCsBos');
    if(bos) bos.style.display = gorunen===0 ? '' : 'none';
}
document.addEventListener('click', function(e){
    var cs=document.getElementById('stCs');
    if(cs && !cs.contains(e.target)) cs.classList.remove('acik');
});
function setBitis(yil) {
    const baslangic = document.getElementById('baslangic_tarih').value;
    const baseDate = baslangic ? new Date(baslangic) : new Date();
    baseDate.setFullYear(baseDate.getFullYear() + yil);
    document.getElementById('bitis_tarih').value =
        baseDate.getFullYear() + '-' + String(baseDate.getMonth() + 1).padStart(2, '0') + '-' + String(baseDate.getDate()).padStart(2, '0');
}
function hizmetToggle() {
    const l = document.getElementById('hizmet-liste');
    l.style.display = l.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function(e) {
    if (!document.getElementById('hizmet-dropdown').contains(e.target)) {
        document.getElementById('hizmet-liste').style.display = 'none';
    }
});
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
document.addEventListener('DOMContentLoaded', function() {
    const secili = document.querySelectorAll('.hizmet-cb:checked').length;
    @if($isEdit)
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