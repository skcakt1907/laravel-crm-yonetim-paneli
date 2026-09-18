@extends('admin._layout')

@section('title', 'Yeni Envanter Kalemi')

@push('head')
<style>
    .gfo-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:18px}
    .gfo-back:hover{color:#8a8820}
    .gfo-head{display:flex;align-items:center;gap:10px;margin-bottom:20px}
    .gfo-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .gfo-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:26px;max-width:680px}
    .gfo-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
    .gfo-row.tek{grid-template-columns:1fr}
    .gfo-grup{display:flex;flex-direction:column;gap:7px}
    .gfo-label{font-weight:700;font-size:13.5px;color:#334155}
    .gfo-label .req{color:#ef4444}
    .gfo-input,.gfo-select,.gfo-textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:11px;padding:11px 14px;font-size:14px;font-family:inherit;color:#1a1d24;transition:.15s;box-sizing:border-box}
    .gfo-input:focus,.gfo-select:focus,.gfo-textarea:focus{outline:none;border-color:#b8b62e;box-shadow:0 0 0 3px rgba(184,182,46,.12)}
    .gfo-textarea{resize:vertical;min-height:80px;line-height:1.6}
    .gfo-fiyat-wrap{position:relative}
    .gfo-fiyat-wrap .sym{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-weight:700}
    .gfo-fiyat-wrap input{padding-left:30px}
    .gfo-submit{display:inline-flex;align-items:center;gap:8px;background:#b8b62e;color:#1a1d24;font-weight:700;padding:13px 28px;border-radius:12px;border:none;cursor:pointer;font-size:15px;transition:.2s;margin-top:6px}
    .gfo-submit:hover{background:#a4a229}
    .gfo-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px}
    @media(max-width:600px){.gfo-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<a href="{{ route('admin.giderler.envanter') }}" class="gfo-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Envantere dön</a>
<div class="gfo-head"><h1><i data-lucide="package-plus"></i> Yeni Envanter Kalemi</h1></div>

@if($errors->any())<div class="gfo-err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('error'))<div class="gfo-err">{{ session('error') }}</div>@endif

<form action="{{ route('admin.giderler.envanter.store') }}" method="POST" class="gfo-card">
    @csrf
    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Ürün / Malzeme Adı <span class="req">*</span></label>
            <input type="text" name="ad" class="gfo-input" value="{{ old('ad') }}" required placeholder="Örn: Dell Laptop, Ofis sandalyesi..." autofocus>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Kategori</label>
            <input type="text" name="kategori" class="gfo-input" value="{{ old('kategori') }}" placeholder="Örn: Bilgisayar, Mobilya, Kırtasiye...">
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Durum <span class="req">*</span></label>
            <select name="durum" class="gfo-select" required>
                <option value="kullanimda" {{ old('durum','kullanimda')==='kullanimda'?'selected':'' }}>Kullanımda</option>
                <option value="depoda" {{ old('durum')==='depoda'?'selected':'' }}>Depoda</option>
                <option value="arizali" {{ old('durum')==='arizali'?'selected':'' }}>Arızalı</option>
                <option value="elden_cikti" {{ old('durum')==='elden_cikti'?'selected':'' }}>Elden Çıktı</option>
            </select>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Adet <span class="req">*</span></label>
            <input type="number" name="adet" class="gfo-input" min="1" value="{{ old('adet', 1) }}" required>
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Birim Fiyat <span class="req">*</span></label>
            <div class="gfo-fiyat-wrap">
                <span class="sym">₺</span>
                <input type="number" step="0.01" min="0" name="birim_fiyat" class="gfo-input" value="{{ old('birim_fiyat') }}" required placeholder="0,00">
            </div>
        </div>
    </div>

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Alış Tarihi</label>
            <input type="date" name="alis_tarihi" class="gfo-input" value="{{ old('alis_tarihi') }}">
        </div>
    </div>

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Açıklama</label>
            <textarea name="aciklama" class="gfo-textarea" placeholder="İsteğe bağlı not...">{{ old('aciklama') }}</textarea>
        </div>
    </div>

    <button type="submit" class="gfo-submit"><i data-lucide="check" style="width:18px;height:18px"></i> Kaydet</button>
</form>
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush