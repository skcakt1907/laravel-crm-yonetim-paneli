@extends('admin._layout')

@section('title', $baslik)

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.randevu.calisanlar') }}">Çalışanlar</a>
    <span class="sep">/</span>
    <span class="current">{{ $baslik }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $calisan ? '✏️ Çalışan Düzenleme' : '➕ Yeni Çalışan' }}</h1>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <i data-lucide="alert-circle"></i>
    <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<div class="section" style="max-width:680px">
    <form method="POST" action="{{ route('admin.randevu.calisan-kaydet') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" value="{{ $calisan->id ?? '' }}">

        {{-- Avatar --}}
        <div class="form-group">
            <label class="form-label">Fotoğraf (Avatar)</label>
            <div style="display:flex;align-items:center;gap:16px">
                <img id="clsnPreview"
                     src="{{ !empty($calisan->foto) ? asset($calisan->foto) : 'https://ui-avatars.com/api/?background=f59e0b&color=fff&name='.urlencode($calisan->ad ?? 'YK') }}"
                     style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:1px solid var(--border)">
                <input type="file" name="foto" accept="image/*" class="form-input" style="max-width:340px"
                       onchange="clsnPrev(event)">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Yönetici Seç *</label>
            <select name="yonetici_id" class="form-input" required onchange="clsnYoneticiSec(this)">
                <option value="">— Panel yöneticilerinden seç —</option>
                @foreach(($yoneticiler ?? []) as $y)
                    <option value="{{ $y->id }}"
                            data-ad="{{ $y->adi }}"
                            data-email="{{ $y->email ?: ($y->eposta ?? '') }}"
                            {{ (string) old('yonetici_id', $calisan->yonetici_id ?? '') === (string) $y->id ? 'selected' : '' }}>
                        {{ $y->adi }}
                    </option>
                @endforeach
            </select>
            <small style="color:var(--text-muted);font-size:12px">Takvimde kolon olarak görünecek kişi panel yöneticilerinden seçilir.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Görünen Ad *</label>
            <input type="text" name="ad" id="clsnAd" class="form-input" required
                   value="{{ old('ad', $calisan->ad ?? '') }}" placeholder="Yönetici seçince otomatik dolar">
        </div>

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="clsnEmail" class="form-input"
                   value="{{ old('email', $calisan->email ?? '') }}" placeholder="ornek@mail.com">
        </div>

        <div class="form-group">
            <label class="form-label">Telefon</label>
            <input type="text" name="telefon" class="form-input"
                   value="{{ old('telefon', $calisan->telefon ?? '') }}" placeholder="05xx...">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
                <label class="form-label">Çalışma Başlangıç</label>
                <input type="time" name="mesai_baslangic" class="form-input"
                       value="{{ old('mesai_baslangic', $calisan->mesai_baslangic ?? '09:00') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Çalışma Bitiş</label>
                <input type="time" name="mesai_bitis" class="form-input"
                       value="{{ old('mesai_bitis', $calisan->mesai_bitis ?? '18:00') }}">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="form-group">
                <label class="form-label">Takvim Rengi</label>
                <input type="color" name="renk" class="form-input" style="height:42px;padding:4px;max-width:120px"
                       value="{{ old('renk', (isset($calisan->renk) && preg_match('/^#/', $calisan->renk)) ? $calisan->renk : '#f59e0b') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Sıralama</label>
                <input type="number" name="siralama" class="form-input" min="0"
                       value="{{ old('siralama', $calisan->siralama ?? 0) }}">
            </div>
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="durum" value="1" {{ old('durum', $calisan->durum ?? 1) ? 'checked' : '' }}>
                <span>Aktif</span>
            </label>
        </div>

        <div style="display:flex;gap:10px;margin-top:8px">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <span>Kaydet</span>
            </button>
            <a href="{{ route('admin.randevu.calisanlar') }}" class="btn btn-ghost">
                <i data-lucide="arrow-left"></i> <span>Çalışanlara Dön</span>
            </a>
        </div>
    </form>
</div>

<script>
function clsnPrev(e){
    const f = e.target.files[0];
    if (f) document.getElementById('clsnPreview').src = URL.createObjectURL(f);
}
function clsnYoneticiSec(sel){
    const opt = sel.selectedOptions[0];
    if (!opt || !opt.value) return;
    document.getElementById('clsnAd').value = opt.dataset.ad || '';
    if (opt.dataset.email) document.getElementById('clsnEmail').value = opt.dataset.email;
}
</script>

@endsection