@extends('admin._layout')

@section('title', 'Müşteri Borç Takip')

@push('head')
<style>
    .mb-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px}
    .mb-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .mb-sub{color: var(--text-muted);font-size:13.5px;margin-bottom:20px}

    .mb-genel{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;padding:26px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
    .mb-genel-l{font-size:13px;font-weight:600;color: var(--text-muted);text-transform:uppercase;letter-spacing:.5px}
    .mb-genel-v{font-size:38px;font-weight:800;margin-top:6px;line-height:1}
    .mb-genel-v.eksi{color: var(--danger)}
    .mb-genel-v.art{color: var(--success)}
    .mb-genel-aciklama{font-size:13px;color: var(--text-secondary);margin-top:8px}
    .mb-genel-ikon{width:74px;height:74px;border-radius: 18px;display:flex;align-items:center;justify-content:center}

    .mb-arac{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}.mb-arac input{flex:1;min-width:220px;padding:10px 14px;border: 1px solid var(--border);border-radius: 10px;font-size:14px;font-family:inherit;outline: none;background: var(--surface);color: var(--text);}
    .mb-arac input:focus{border-color: var(--brand)}
    .mb-arac button{padding:10px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 10px;font-weight:700;cursor:pointer}
    .mb-arac a.temizle{padding:10px 16px;background: var(--bg-subtle);color: var(--text-secondary);border-radius: 10px;text-decoration:none;font-weight:600}

    .mb-kart{background: var(--surface);border: 1px solid var(--border);border-radius: 18px;overflow:hidden}
    .mb-tablo{width:100%;border-collapse: collapse;font-size:13.5px}
    .mb-tablo th{text-align:left;padding:12px 16px;color: var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:.4px;border-bottom: 2px solid var(--border);font-weight:700}
    .mb-tablo th.sag,.mb-tablo td.sag{text-align:right}
    .mb-tablo td{padding:13px 16px;border-bottom: 1px solid #f5f5f5;vertical-align:middle}
    .mb-tablo tbody tr{cursor:pointer;transition:background .15s}
    .mb-tablo tbody tr:hover td{background: var(--surface-hover)}
    .mb-musteri-ad{font-weight:700;color: var(--text)}
    .mb-musteri-alt{color: var(--text-muted);font-size:12px;margin-top:2px}
    .mb-tutar.borc{color: var(--danger);font-weight:700}
    .mb-tutar.odeme{color: var(--success);font-weight:700}
    .mb-net{font-weight:800}
    .mb-net.eksi{color: var(--danger)}
    .mb-net.art{color: var(--success)}
    .mb-bos{text-align:center;padding:40px;color: var(--text-muted);font-size:14px}

    .mb-uyari{background: #fffbeb;border: 1px solid #fde68a;color: var(--warning);padding:16px 20px;border-radius: 12px;margin-bottom:18px;font-size:14px}
    .mb-alert{padding:13px 18px;border-radius: 12px;margin-bottom:18px;font-size:14px;font-weight:600}
    .mb-alert.ok{background: var(--success-soft);color: #065f46}
    .mb-alert.err{background: var(--danger-soft);color: var(--danger)}

    .mb-ekle-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 10px;font-weight:700;font-size:14px;cursor:pointer}
    .mb-ekle-btn:hover{background: var(--brand-hover)}

    .mb-modal-arka{display:none;position:fixed;inset:0;background: rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;padding:20px}
    .mb-modal-arka.acik{display:flex}
    .mb-modal{background: var(--surface);border-radius: 16px;padding:24px;max-width:480px;width:100%;box-shadow:0 24px 48px rgba(0,0,0,.2)}
    .mb-modal h3{font-size:18px;font-weight:800;margin:0 0 18px;color: var(--text)}
    .mb-modal label{font-size:12px;font-weight:700;color: var(--text-secondary);display:block;margin-bottom:5px;margin-top:12px}.mb-modal select,.mb-modal input,.mb-modal textarea{width:100%;padding:10px 12px;border: 1px solid var(--border);border-radius: 8px;font-size:14px;font-family:inherit;outline: none;background: var(--surface);color: var(--text);}
    .mb-modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
    .mb-btn-iptal{padding:9px 18px;background: var(--bg-subtle);color: var(--text-secondary);border: none;border-radius: 8px;font-weight:700;cursor:pointer}
    .mb-btn-kaydet{padding:9px 18px;background: var(--brand);color: var(--text);border: none;border-radius: 8px;font-weight:700;cursor:pointer}
    /* Aranabilir müşteri seçici */
    .mb-cs{position:relative;width:100%}
    .mb-cs-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 12px;border: 1px solid var(--border);border-radius: 8px;background: var(--surface);font-size:14px;font-family:inherit;cursor:pointer;text-align:left;color: var(--text)}
    .mb-cs-toggle:focus,.mb-cs.acik .mb-cs-toggle{border-color: var(--brand);outline: none}
    .mb-cs-label.is-placeholder{color: var(--text-muted)}
    .mb-cs-caret{width:16px;height:16px;color: var(--text-muted);flex-shrink:0;transition:transform .18s ease}
    .mb-cs.acik .mb-cs-caret{transform:rotate(180deg)}
    .mb-cs-menu{position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:50;background: var(--surface);border: 1px solid var(--border);border-radius: 10px;box-shadow:0 12px 32px rgba(0,0,0,.14);padding:6px;display:none;max-height:300px;overflow:hidden;flex-direction:column}
    .mb-cs.acik .mb-cs-menu{display:flex}
    .mb-cs-ara{display:flex;align-items:center;gap:8px;padding:8px 10px;border: 1px solid var(--border);border-radius: 8px;background: var(--bg-subtle);margin-bottom:6px}
    .mb-cs-ara input{flex:1;border: none;background: none;outline: none;font-size:14px;font-family:inherit;color: var(--text)}
    .mb-cs-liste{overflow-y:auto;max-height:220px}
    .mb-cs-item{padding:9px 12px;border-radius: 7px;font-size:14px;color: var(--text);cursor:pointer;transition:background .12s}
    .mb-cs-item:hover,.mb-cs-item.vurgu{background: rgba(184,182,46,.14)}
    .mb-cs-unvan{color: var(--text-muted);font-size:12.5px}
    .mb-cs-bos{padding:12px;text-align:center;color: var(--text-muted);font-size:13px}
    @media(max-width:760px){ .mb-gizle{display:none} }
</style>
@endpush

@section('content')
<div class="mb-head">
    <h1><i data-lucide="users"></i> Müşteri Borç Takip</h1>
    @unless($kurulumGerekli)
    <button type="button" class="mb-ekle-btn" onclick="mbEkleAc()"><i data-lucide="plus" style="width:16px;height:16px;display:inline;vertical-align:middle"></i> Borç / Ödeme Ekle</button>
    @endunless
</div>
<div class="mb-sub">Her müşteri için borç (+) ve ödeme (−) girerek cari bakiyeyi (kalan borç) takip edin. Detay için satıra tıklayın.</div>

@if(session('success'))<div class="mb-alert ok">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-alert err">{{ session('error') }}</div>@endif
@if($errors->any())<div class="mb-alert err">{{ $errors->first() }}</div>@endif

@if($kurulumGerekli)
<div class="mb-uyari">
    <strong>Kurulum gerekli:</strong> <code>musteri_borc_takip</code> tablosu bulunamadı.
    Lütfen <code>php artisan migrate</code> çalıştırın ya da ilgili SQL'i içe aktarın.
</div>
@else

{{-- GENEL ÖZET --}}
@php $genelArtida = $genelToplam['net'] <= 0; @endphp
<div class="mb-genel">
    <div>
        <div class="mb-genel-l">Toplam Kalan Alacak (Tüm Müşteriler)</div>
        <div class="mb-genel-v {{ $genelArtida ? 'art' : 'eksi' }}">₺{{ number_format(abs($genelToplam['net']), 2, ',', '.') }}</div>
        <div class="mb-genel-aciklama">
            Toplam Borç: <strong style="color: var(--danger)">₺{{ number_format($genelToplam['borc'], 2, ',', '.') }}</strong>
            &nbsp;•&nbsp; Toplam Tahsilat: <strong style="color: var(--success)">₺{{ number_format($genelToplam['odeme'], 2, ',', '.') }}</strong>
        </div>
    </div>
    <div class="mb-genel-ikon" style="background: {{ $genelArtida ? '#ecfdf5' : '#fef2f2' }}">
        <i data-lucide="{{ $genelArtida ? 'check-circle' : 'alert-circle' }}" style="width:36px;height:36px;color: {{ $genelArtida ? '#10b981' : '#ef4444' }}"></i>
    </div>
</div>

{{-- ARAMA --}}
<form method="GET" class="mb-arac">
    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Müşteri ara (ad, ünvan, e-posta, telefon)…">
    <button type="submit"><i data-lucide="search" style="width:14px;height:14px;display:inline;vertical-align:middle"></i> Ara</button>
    @if(!empty($q))<a href="{{ route('admin.musteri-borc.index') }}" class="temizle">Temizle</a>@endif
</form>

<div class="mb-kart">
    <table class="mb-tablo">
        <thead>
            <tr>
                <th>Müşteri</th>
                <th class="sag mb-gizle" style="width:140px">Toplam Borç</th>
                <th class="sag mb-gizle" style="width:140px">Tahsilat</th>
                <th class="sag" style="width:150px">Kalan Borç</th>
                <th class="mb-gizle" style="width:70px">Kayıt</th>
            </tr>
        </thead>
        <tbody>
        @forelse($musteriler as $m)
            <tr onclick="window.location='{{ route('admin.musteri-borc.goster', $m->id) }}'">
                <td>
                    <div class="mb-musteri-ad">{{ $m->adi }}</div>
                    <div class="mb-musteri-alt">{{ $m->unvan ?: ($m->email ?: $m->telefon) }}</div>
                </td>
                <td class="sag mb-tutar borc mb-gizle">₺{{ number_format($m->borc, 2, ',', '.') }}</td>
                <td class="sag mb-tutar odeme mb-gizle">₺{{ number_format($m->odeme, 2, ',', '.') }}</td>
                <td class="sag mb-net {{ $m->net <= 0 ? 'art' : 'eksi' }}">₺{{ number_format(abs($m->net), 2, ',', '.') }}</td>
                <td class="mb-gizle">{{ $m->adet }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="mb-bos">
                @if(!empty($q))Aramanızla eşleşen müşteri bulunamadı.@else Henüz cari hareketi olan müşteri yok. Bir müşteri aratıp borç/ödeme ekleyebilirsiniz.@endif
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- EKLEME MODAL --}}
<div class="mb-modal-arka" id="mbEkleModal">
    <div class="mb-modal">
        <h3>Borç / Ödeme Ekle</h3>
        <form action="{{ route('admin.musteri-borc.store') }}" method="POST">
            @csrf
            <label>Müşteri</label>
            <input type="hidden" name="musteri_id" id="mbMusteriId" required>
            <div class="mb-cs" id="mbCs">
                <button type="button" class="mb-cs-toggle" id="mbCsToggle" onclick="mbCsToggleClick(event)">
                    <span class="mb-cs-label is-placeholder" id="mbCsLabel">— Müşteri seçin —</span>
                    <i data-lucide="chevron-down" class="mb-cs-caret"></i>
                </button>
                <div class="mb-cs-menu" id="mbCsMenu">
                    <div class="mb-cs-ara">
                        <i data-lucide="search" style="width:15px;height:15px;color: var(--text-muted)"></i>
                        <input type="text" id="mbCsArama" placeholder="Müşteri ara…" oninput="mbCsFiltre()" onclick="event.stopPropagation()" autocomplete="off">
                    </div>
                    <div class="mb-cs-liste" id="mbCsListe">
                        @foreach($tumMusteriler as $tm)
                            <div class="mb-cs-item" data-id="{{ $tm->id }}" data-ad="{{ $tm->adi }}@if($tm->unvan) — {{ $tm->unvan }}@endif" onclick="mbCsSec(this)">
                                {{ $tm->adi }}@if($tm->unvan) <span class="mb-cs-unvan">— {{ $tm->unvan }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-cs-bos" id="mbCsBos" style="display:none">Eşleşen müşteri yok</div>
                </div>
            </div>
            <label>Tür</label>
            <select name="tip" required>
                <option value="borc">Borç (+)</option>
                <option value="odeme">Ödeme (−)</option>
            </select>
            <label>Başlık</label>
            <input type="text" name="baslik" maxlength="200" required placeholder="Örn: Web tasarım hizmeti">
            <label>Tutar (₺)</label>
            <input type="number" name="tutar" step="0.01" min="0" required placeholder="0,00">
            <label>Tarih</label>
            <input type="date" name="tarih" value="{{ date('Y-m-d') }}" required>
            <label>Açıklama (isteğe bağlı)</label>
            <textarea name="aciklama" rows="2"></textarea>
            <div class="mb-modal-footer">
                <button type="button" class="mb-btn-iptal" onclick="mbEkleKapat()">İptal</button>
                <button type="submit" class="mb-btn-kaydet">Kaydet</button>
            </div>
        </form>
    </div>
</div>

@endif
@endsection

@push('scripts')
<script>
function mbEkleAc(){
    document.getElementById('mbEkleModal').classList.add('acik');
    // aç/temizle: arama kutusuna odak
    setTimeout(function(){ var a=document.getElementById('mbCsArama'); if(a){ a.value=''; mbCsFiltre(); a.focus(); } }, 60);
}
function mbEkleKapat(){
    document.getElementById('mbEkleModal').classList.remove('acik');
    var cs=document.getElementById('mbCs'); if(cs) cs.classList.remove('acik');
}
var mbModal = document.getElementById('mbEkleModal');
mbModal && mbModal.addEventListener('click', function(e){ if(e.target===this) mbEkleKapat(); });

/* ---- Aranabilir müşteri seçici ---- */
function mbCsToggleClick(e){
    e.stopPropagation();
    var cs=document.getElementById('mbCs');
    cs.classList.toggle('acik');
    if(cs.classList.contains('acik')){
        var a=document.getElementById('mbCsArama');
        if(a){ a.value=''; mbCsFiltre(); setTimeout(function(){a.focus();},30); }
    }
}
function mbCsSec(el){
    document.getElementById('mbMusteriId').value = el.getAttribute('data-id');
    var lbl=document.getElementById('mbCsLabel');
    lbl.textContent = el.getAttribute('data-ad');
    lbl.classList.remove('is-placeholder');
    document.getElementById('mbCs').classList.remove('acik');
}
function mbCsFiltre(){
    var q=(document.getElementById('mbCsArama').value||'').toLocaleLowerCase('tr');
    var items=document.querySelectorAll('#mbCsListe .mb-cs-item');
    var gorunen=0;
    items.forEach(function(it){
        var ad=(it.getAttribute('data-ad')||'').toLocaleLowerCase('tr');
        var esle = q==='' || ad.indexOf(q)!==-1;
        it.style.display = esle ? '' : 'none';
        if(esle) gorunen++;
    });
    document.getElementById('mbCsBos').style.display = gorunen===0 ? '' : 'none';
}
// Dışarı tıklayınca kapan
document.addEventListener('click', function(e){
    var cs=document.getElementById('mbCs');
    if(cs && !cs.contains(e.target)) cs.classList.remove('acik');
});
// lucide ikonlarını tazele
if(window.lucide) lucide.createIcons();
</script>
@endpush