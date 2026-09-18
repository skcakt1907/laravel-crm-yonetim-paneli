@extends('admin._layout')

@section('title', $baslik)

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.randevu.hizmetler') }}">Lokasyonlar</a>
    <span class="sep">/</span>
    <span class="current">{{ $baslik }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $hizmet ? '✏️ Lokasyon Düzenleme' : '➕ Yeni Lokasyon' }}</h1>
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
    <form method="POST" action="{{ route('admin.randevu.hizmet-kaydet') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $hizmet->id ?? '' }}">

        <div class="form-group">
            <label class="form-label">Lokasyon Adı *</label>
            <input type="text" name="ad" class="form-input" required
                   value="{{ old('ad', $hizmet->ad ?? '') }}" placeholder="Örn: DN Kreatif Marmaris ofisinde, Online Zoom Üzerinden">
        </div>

        <div class="form-group">
            <label class="form-label">Açıklama</label>
            <textarea name="aciklama" class="form-input" rows="2" placeholder="Açıklama">{{ old('aciklama', $hizmet->aciklama ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label">
                <i data-lucide="map-pin" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                Konum Linki (Harita)
            </label>
            <input type="url" name="konum_link" class="form-input"
                   value="{{ old('konum_link', $hizmet->konum_link ?? '') }}"
                   placeholder="https://maps.app.goo.gl/...">
            <small style="color:var(--text-muted);font-size:12px">
                Bu lokasyon (ör. ofis) için Google Maps konum linki. Randevu kaydedilince müşteriye SMS ile gönderilir.
                Online lokasyonlarda (Zoom/WhatsApp) boş bırakın — konum eklenmez.
            </small>
        </div>

        <div class="form-group">
            <label class="form-label">Randevu Süresi (Dakika) *</label>
            <input type="number" name="sure_dk" class="form-input" min="5" step="5" required
                   value="{{ old('sure_dk', $hizmet->sure_dk ?? 45) }}">
        </div>

        <div class="form-group">
            <label class="form-label">Tekrarlama Süresi</label>
            <input type="number" name="tekrarlama_suresi" class="form-input" min="0" step="1"
                   value="{{ old('tekrarlama_suresi', $hizmet->tekrarlama_suresi ?? 0) }}">
            <small style="color:var(--text-muted);font-size:12px">Sms bildiriminde kullanılır, yok ise 0 yazınız.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Takvim Rengi</label>
            <input type="color" name="renk" class="form-input" style="height:42px;padding:4px;max-width:120px"
                   value="{{ old('renk', (isset($hizmet->renk) && preg_match('/^#/', $hizmet->renk)) ? $hizmet->renk : '#3b82f6') }}">
        </div>

        <div class="form-group">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="durum" value="1" {{ old('durum', $hizmet->durum ?? 1) ? 'checked' : '' }}>
                <span>Aktif</span>
            </label>
        </div>

        <div style="display:flex;gap:10px;margin-top:8px">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <span>Kaydet</span>
            </button>
            <a href="{{ route('admin.randevu.hizmetler') }}" class="btn btn-ghost">
                <i data-lucide="arrow-left"></i> <span>Lokasyonlara Dön</span>
            </a>
        </div>
    </form>
</div>

@endsection