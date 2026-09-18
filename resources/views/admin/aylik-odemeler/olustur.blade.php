@extends('admin._layout')

@section('title', 'Yeni Aylık Ödeme')

@push('head')
<style>
    .gfo-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:18px}
    .gfo-back:hover{color:#8a8820}
    .gfo-head{display:flex;align-items:center;gap:10px;margin-bottom:20px}
    .gfo-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .gfo-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:26px;max-width:760px}
    .gfo-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
    .gfo-row.tek{grid-template-columns:1fr}
    .gfo-grup{display:flex;flex-direction:column;gap:7px}
    .gfo-label{font-weight:700;font-size:13.5px;color:#334155}
    .gfo-label .req{color:#ef4444}
    .gfo-input,.gfo-select,.gfo-textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:11px;padding:11px 14px;font-size:14px;font-family:inherit;color:#1a1d24;transition:.15s;box-sizing:border-box}
    .gfo-input:focus,.gfo-select:focus,.gfo-textarea:focus{outline:none;border-color:#b8b62e;box-shadow:0 0 0 3px rgba(184,182,46,.12)}
    .gfo-textarea{resize:vertical;min-height:90px;line-height:1.6}
    .gfo-tutar-wrap{position:relative}
    .gfo-tutar-wrap .sym{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-weight:700}
    .gfo-tutar-wrap input{padding-left:30px}
    .gfo-submit{display:inline-flex;align-items:center;gap:8px;background:#b8b62e;color:#1a1d24;font-weight:700;padding:13px 28px;border-radius:12px;border:none;cursor:pointer;font-size:15px;transition:.2s;margin-top:6px}
    .gfo-submit:hover{background:#a4a229}
    .gfo-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px}
    /* periyot çipleri */
    .pr-chips{display:flex;gap:8px;flex-wrap:wrap}
    .pr-chip{border:1.5px solid #e5e7eb;background:#fff;color:#334155;border-radius:10px;padding:9px 16px;font-weight:700;font-size:13.5px;cursor:pointer;transition:.15s}
    .pr-chip:hover{border-color:#b8b62e}
    .pr-chip.aktif{background:#b8b62e;border-color:#b8b62e;color:#1a1d24}
    .pr-ozel{width:120px}
    @media(max-width:600px){.gfo-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<a href="{{ route('admin.aylik-odemeler.index') }}" class="gfo-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Aylık ödemelere dön</a>
<div class="gfo-head"><h1><i data-lucide="plus-circle"></i> Yeni Aylık Ödeme</h1></div>

@if($errors->any())<div class="gfo-err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('error'))<div class="gfo-err">{{ session('error') }}</div>@endif

<form action="{{ route('admin.aylik-odemeler.store') }}" method="POST" class="gfo-card">
    @csrf

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Başlık <span class="req">*</span></label>
            <input type="text" name="baslik" class="gfo-input" value="{{ old('baslik') }}" required placeholder="Örn: Ofis kirası, Sunucu kirası, Muhasebe ücreti..." autofocus>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Kategori</label>
            <select name="kategori_id" class="gfo-select kategori-select">
                <option value="">Seçiniz</option>
                @foreach($kategoriler as $k)
                <option value="{{ $k->id }}" data-diger="{{ mb_strtolower($k->ad,'UTF-8')==='diğer'?1:0 }}" {{ (string)old('kategori_id')===(string)$k->id?'selected':'' }}>{{ $k->ad }}</option>
                @endforeach
            </select>
            <input type="text" name="kategori_diger" class="gfo-input kategori-diger" style="margin-top:8px;display:none" placeholder="Diğer — ne olduğunu yazın…" value="{{ old('kategori_diger') }}" maxlength="150">
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Tutar <span class="req">*</span></label>
            <div class="gfo-tutar-wrap">
                <span class="sym">₺</span>
                <input type="number" step="0.01" min="0" name="tutar" class="gfo-input" value="{{ old('tutar') }}" required placeholder="0,00">
            </div>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Periyot (kaç ayda bir?) <span class="req">*</span></label>
            <div class="pr-chips" id="prChips">
                @php $secili = (int) old('periyot_ay', 1); @endphp
                @foreach([1=>'Aylık',3=>'3 Ayda',6=>'6 Ayda',12=>'Yıllık'] as $ay=>$et)
                <button type="button" class="pr-chip {{ $secili===$ay?'aktif':'' }}" data-ay="{{ $ay }}">{{ $et }}</button>
                @endforeach
                <input type="number" min="1" max="60" class="gfo-input pr-ozel" id="prOzel" placeholder="Özel ay" value="{{ in_array($secili,[1,3,6,12])?'':$secili }}">
            </div>
            <input type="hidden" name="periyot_ay" id="prDeger" value="{{ $secili }}">
            <small style="color:#94a3b8;font-size:11.5px">1 = her ay, 3 = üç ayda bir, 12 = yıllık. Vade geldikçe bir sonraki döneme otomatik taşınır.</small>
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Son Ödeme Tarihi <span class="req">*</span></label>
            <input type="date" name="son_odeme_tarihi" class="gfo-input" value="{{ old('son_odeme_tarihi', date('Y-m-d')) }}" required>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Durum <span class="req">*</span></label>
            <select name="durum" class="gfo-select" required>
                <option value="bekliyor" {{ old('durum','bekliyor')==='bekliyor'?'selected':'' }}>Ödenmedi (Bekliyor)</option>
                <option value="odendi" {{ old('durum')==='odendi'?'selected':'' }}>Ödendi</option>
            </select>
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Ödeme Yöntemi</label>
            <input type="text" name="odeme_yontemi" class="gfo-input" value="{{ old('odeme_yontemi') }}" placeholder="Örn: Havale, Kredi kartı...">
        </div>
    </div>

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Açıklama</label>
            <textarea name="aciklama" class="gfo-textarea" placeholder="İsteğe bağlı not...">{{ old('aciklama') }}</textarea>
        </div>
    </div>

    <button type="submit" class="gfo-submit"><i data-lucide="check" style="width:18px;height:18px"></i> Ödemeyi Kaydet</button>
</form>
@endsection

@push('scripts')
<script>
if(window.lucide)lucide.createIcons();
// "Diğer" kategorisi seçilince serbest metin kutusu
(function(){
    document.querySelectorAll('.kategori-select').forEach(function(sel){
        var box = sel.parentElement.querySelector('.kategori-diger');
        if(!box) return;
        function tog(){ var o=sel.options[sel.selectedIndex]; var d=o&&o.getAttribute('data-diger')==='1'; box.style.display=d?'block':'none'; if(!d) box.value=''; }
        sel.addEventListener('change', tog); tog();
    });
})();
(function(){
    var chips=document.querySelectorAll('#prChips .pr-chip'), ozel=document.getElementById('prOzel'), deger=document.getElementById('prDeger');
    function sec(ay,fromOzel){ deger.value=ay;
        chips.forEach(c=>c.classList.toggle('aktif', !fromOzel && parseInt(c.dataset.ay)===ay));
        if(!fromOzel) ozel.value='';
    }
    chips.forEach(c=>c.addEventListener('click',()=>sec(parseInt(c.dataset.ay),false)));
    ozel.addEventListener('input',function(){ var v=parseInt(this.value); if(v>0){ sec(v,true); } });
})();
</script>
@endpush
