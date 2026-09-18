@extends('admin._layout')

@section('title', 'Gider Düzenle')

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
    .gfo-input,.gfo-select,.gfo-textarea{width:100%;border:1.5px solid #e5e7eb;border-radius:11px;padding:11px 14px;
        font-size:14px;font-family:inherit;color:#1a1d24;transition:.15s;box-sizing:border-box}
    .gfo-input:focus,.gfo-select:focus,.gfo-textarea:focus{outline:none;border-color:#b8b62e;box-shadow:0 0 0 3px rgba(184,182,46,.12)}
    .gfo-textarea{resize:vertical;min-height:90px;line-height:1.6}
    .gfo-tutar-wrap{position:relative}
    .gfo-tutar-wrap .sym{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-weight:700}
    .gfo-tutar-wrap input{padding-left:30px}
    .gfo-submit{display:inline-flex;align-items:center;gap:8px;background:#b8b62e;color:#1a1d24;font-weight:700;
        padding:13px 28px;border-radius:12px;border:none;cursor:pointer;font-size:15px;transition:.2s;margin-top:6px}
    .gfo-submit:hover{background:#a4a229}
    .gfo-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px}
    @media(max-width:600px){.gfo-row{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<a href="{{ route('admin.giderler.index') }}" class="gfo-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Harcamalara dön</a>
<div class="gfo-head"><h1><i data-lucide="pencil"></i> Gider Düzenle</h1></div>

@if($errors->any())<div class="gfo-err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('error'))<div class="gfo-err">{{ session('error') }}</div>@endif

<form action="{{ route('admin.giderler.guncelle', $gider->id) }}" method="POST" class="gfo-card">
    @csrf
    @method('PUT')

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Başlık <span class="req">*</span></label>
            <input type="text" name="baslik" class="gfo-input" value="{{ old('baslik', $gider->baslik) }}" required autofocus>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Kategori</label>
            <select name="kategori_id" class="gfo-select">
                <option value="">Seçiniz</option>
                @foreach($kategoriler as $k)
                <option value="{{ $k->id }}" {{ (string)old('kategori_id', $gider->kategori_id)===(string)$k->id?'selected':'' }}>{{ $k->ad }}</option>
                @endforeach
            </select>
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Tutar <span class="req">*</span></label>
            <div class="gfo-tutar-wrap">
                <span class="sym">₺</span>
                <input type="number" step="0.01" min="0" name="tutar" class="gfo-input" value="{{ old('tutar', $gider->tutar) }}" required>
            </div>
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Gider Tarihi</label>
            <input type="date" name="gider_tarihi" class="gfo-input" value="{{ old('gider_tarihi', $gider->gider_tarihi) }}">
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Son Ödeme Tarihi</label>
            <input type="date" name="son_odeme_tarihi" class="gfo-input" value="{{ old('son_odeme_tarihi', $gider->son_odeme_tarihi) }}">
        </div>
    </div>

    <div class="gfo-row">
        <div class="gfo-grup">
            <label class="gfo-label">Durum <span class="req">*</span></label>
            <select name="durum" class="gfo-select" required>
                <option value="bekliyor" {{ old('durum', $gider->durum)==='bekliyor'?'selected':'' }}>Bekliyor</option>
                <option value="odendi" {{ old('durum', $gider->durum)==='odendi'?'selected':'' }}>Ödendi</option>
                <option value="gecikti" {{ old('durum', $gider->durum)==='gecikti'?'selected':'' }}>Gecikti</option>
            </select>
        </div>
        <div class="gfo-grup">
            <label class="gfo-label">Otomatik Tekrar</label>
            @php $tekEden = old('tekrar_eden', $gider->tekrar_eden ?? 0); @endphp
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:9px 0;margin:0">
                <input type="checkbox" name="tekrar_eden" value="1" id="tekrarEden" {{ $tekEden?'checked':'' }} onchange="document.getElementById('tekrarAyWrap').style.display=this.checked?'block':'none'">
                <span style="font-size:13px">Bu gider periyodik tekrarlasın (kira, maaş, abonelik vb.)</span>
            </label>
        </div>
        <div class="gfo-grup" id="tekrarAyWrap" style="display:{{ $tekEden?'block':'none' }}">
            <label class="gfo-label">Kaç ayda bir?</label>
            <input type="number" name="tekrar_ay" class="gfo-input" min="1" max="60" value="{{ old('tekrar_ay', $gider->tekrar_ay ?? 1) }}" placeholder="1">
            <small style="color:#94a3b8;font-size:11.5px;display:block;margin-top:4px">1 = her ay, 3 = üç ayda bir, 12 = yıllık. Son ödeme tarihi geldikçe sistem bir sonraki gideri otomatik oluşturur.</small>
        </div>
    </div>

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Ödeme Yöntemi</label>
            <input type="text" name="odeme_yontemi" class="gfo-input" value="{{ old('odeme_yontemi', $gider->odeme_yontemi) }}" placeholder="Örn: Kredi kartı, Havale, Nakit...">
        </div>
    </div>

    <div class="gfo-row tek">
        <div class="gfo-grup">
            <label class="gfo-label">Açıklama</label>
            <textarea name="aciklama" class="gfo-textarea">{{ old('aciklama', $gider->aciklama) }}</textarea>
        </div>
    </div>

    <button type="submit" class="gfo-submit"><i data-lucide="check" style="width:18px;height:18px"></i> Değişiklikleri Kaydet</button>
</form>
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush