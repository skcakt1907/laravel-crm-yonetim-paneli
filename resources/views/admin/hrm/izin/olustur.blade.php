@extends('admin._layout')

@section('title', 'İzin Talebi Oluştur')

@push('head')
<style>
    .io-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:16px}
    .io-back:hover{color:#8a8820}
    .io-head{display:flex;align-items:center;gap:10px;margin-bottom:20px}
    .io-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .io-bakiye{display:flex;gap:10px;margin-bottom:20px;max-width:560px}
    .io-bk{flex:1;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;text-align:center}
    .io-bk-v{font-size:24px;font-weight:800}
    .io-bk-l{font-size:11.5px;color:#94a3b8;font-weight:600;margin-top:3px}
    .io-bk.hak .io-bk-v{color:#3b82f6}
    .io-bk.kul .io-bk-v{color:#f59e0b}
    .io-bk.kal .io-bk-v{color:#10b981}
    .io-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:26px;max-width:560px}
    .io-grup{margin-bottom:18px;display:flex;flex-direction:column;gap:7px}
    .io-label{font-weight:700;font-size:13.5px;color:#334155}
    .io-label .req{color:#ef4444}
    .io-input,.io-select,.io-textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:11px;padding:11px 14px;font-size:14px;font-family:inherit;color:#1a1d24;box-sizing:border-box}
    .io-input:focus,.io-select:focus,.io-textarea:focus{outline:none;border-color:#b8b62e;box-shadow:0 0 0 3px rgba(184,182,46,.12)}
    .io-textarea{resize:vertical;min-height:80px}
    .io-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .io-gun{font-size:13.5px;color:#64748b;margin-top:8px;font-weight:600}
    .io-gun strong{color:#8a8820}
    .io-submit{display:inline-flex;align-items:center;gap:8px;background:#b8b62e;color:#1a1d24;font-weight:700;padding:13px 28px;border-radius:12px;border:none;cursor:pointer;font-size:15px;margin-top:6px}
    .io-submit:hover{background:#a4a229}
    .io-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px}
    @media(max-width:600px){.io-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<a href="{{ route('admin.hrm.izin.index') }}" class="io-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> İzin taleplerine dön</a>
<div class="io-head"><h1><i data-lucide="calendar-plus"></i> İzin Talebi Oluştur</h1></div>

@if($errors->any())<div class="io-err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('error'))<div class="io-err">{{ session('error') }}</div>@endif

<div class="io-bakiye">
    <div class="io-bk hak"><div class="io-bk-v">{{ $bakiye['hak'] }}</div><div class="io-bk-l">YILLIK HAK</div></div>
    <div class="io-bk kul"><div class="io-bk-v">{{ $bakiye['kullanilan'] }}</div><div class="io-bk-l">KULLANILAN</div></div>
    <div class="io-bk kal"><div class="io-bk-v">{{ $bakiye['kalan'] }}</div><div class="io-bk-l">KALAN</div></div>
</div>

<form action="{{ route('admin.hrm.izin.store') }}" method="POST" class="io-card" id="izinForm">
    @csrf
    <div class="io-grup">
        <label class="io-label">İzin Tipi <span class="req">*</span></label>
        <select name="izin_tipi" class="io-select" required>
            <option value="yillik" {{ old('izin_tipi','yillik')==='yillik'?'selected':'' }}>Yıllık İzin</option>
            <option value="hastalik" {{ old('izin_tipi')==='hastalik'?'selected':'' }}>Hastalık İzni</option>
            <option value="mazeret" {{ old('izin_tipi')==='mazeret'?'selected':'' }}>Mazeret İzni</option>
            <option value="ucretsiz" {{ old('izin_tipi')==='ucretsiz'?'selected':'' }}>Ücretsiz İzin</option>
        </select>
    </div>

    <div class="io-grup">
        <div class="io-row">
            <div>
                <label class="io-label">Başlangıç <span class="req">*</span></label>
                <input type="date" name="baslangic" id="bas" class="io-input" value="{{ old('baslangic') }}" required>
            </div>
            <div>
                <label class="io-label">Bitiş <span class="req">*</span></label>
                <input type="date" name="bitis" id="bit" class="io-input" value="{{ old('bitis') }}" required>
            </div>
        </div>
        <div class="io-gun" id="gunBilgi"></div>
    </div>

    <div class="io-grup">
        <label class="io-label">Açıklama</label>
        <textarea name="aciklama" class="io-textarea" placeholder="İsteğe bağlı not...">{{ old('aciklama') }}</textarea>
    </div>

    <button type="submit" class="io-submit"><i data-lucide="send" style="width:18px;height:18px"></i> Talebi Gönder</button>
</form>
@endsection

@push('scripts')
<script>
if(window.lucide)lucide.createIcons();
const bas=document.getElementById('bas'), bit=document.getElementById('bit'), gunBilgi=document.getElementById('gunBilgi');
function hesapla(){
    if(bas.value && bit.value){
        const d1=new Date(bas.value), d2=new Date(bit.value);
        if(d2>=d1){
            const gun=Math.round((d2-d1)/(1000*60*60*24))+1;
            gunBilgi.innerHTML='Toplam <strong>'+gun+' gün</strong> izin talep ediyorsunuz.';
        } else {
            gunBilgi.innerHTML='<span style="color:#ef4444">Bitiş tarihi başlangıçtan önce olamaz.</span>';
        }
    } else { gunBilgi.innerHTML=''; }
}
bas.addEventListener('change',hesapla); bit.addEventListener('change',hesapla); hesapla();
</script>
@endpush
