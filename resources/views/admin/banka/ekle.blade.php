@extends('admin._layout')

@section('title', 'Yeni Banka Hesabı')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.banka.index') }}">Banka Hesapları</a>
    <span class="sep">/</span>
    <span class="current">Yeni Hesap</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Banka Hesabı</h1>
        <div class="page-subtitle">Müşterilerin havale yapabilmesi için banka hesap bilgileri</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.banka.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">
        <strong>Form Hataları:</strong>
        <ul style="margin:6px 0 0 18px;font-size:13px">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.banka.eklePost') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-grid">
        {{-- SOL: Banka bilgileri --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="landmark"></i>
                    <span>Banka Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Banka Adı <span class="required">*</span></label>
                    <input type="text" name="banka" value="{{ old('banka') }}" required maxlength="255" class="form-input" placeholder="Örn: Ziraat Bankası">
                </div>

                <div class="form-group">
                    <label class="form-label">Hesap Sahibi <span class="required">*</span></label>
                    <input type="text" name="hesap" value="{{ old('hesap') }}" required maxlength="255" class="form-input" placeholder="Örn: DN Kreatif Reklam Ltd. Şti.">
                </div>

                <div class="form-group">
                    <label class="form-label">IBAN</label>
                    <input type="text" name="iban" value="{{ old('iban') }}" maxlength="34" class="form-input" placeholder="TR00 0000 0000 0000 0000 0000 00" style="font-family:monospace;text-transform:uppercase">
                    <small class="form-help">Türkiye için TR ile başlayan 26 haneli IBAN</small>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Hesap No</label>
                        <input type="text" name="hnumara" value="{{ old('hnumara') }}" maxlength="50" class="form-input" placeholder="123456-7890" style="font-family:monospace">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şube</label>
                        <input type="text" name="sube" value="{{ old('sube') }}" maxlength="100" class="form-input" placeholder="Adana / Merkez Şubesi">
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Durum + logo --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Banka Logosu</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Logo (opsiyonel)</label>
                    <input type="file" name="resim" accept="image/*" class="form-input">
                    <small class="form-help">Banka logosu — müşteri ödeme sayfasında gösterilir</small>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="flag"></i>
                    <span>Durum</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <div style="display:flex;flex-direction:column;gap:8px">
                        @php $currentDurum = old('durum', 1); @endphp
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid {{ $currentDurum == 1 ? 'var(--success)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentDurum == 1 ? 'rgba(16,185,129,0.06)' : 'transparent' }}">
                            <input type="radio" name="durum" value="1" {{ $currentDurum == 1 ? 'checked' : '' }} style="width:auto;margin:0">
                            <span style="font-weight:600;color:var(--success)">🟢 Aktif</span>
                            <span style="font-size:11.5px;color:var(--text-muted);margin-left:auto">Müşteri görür</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid {{ $currentDurum == 0 ? 'var(--danger)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentDurum == 0 ? 'rgba(239,68,68,0.06)' : 'transparent' }}">
                            <input type="radio" name="durum" value="0" {{ $currentDurum == 0 ? 'checked' : '' }} style="width:auto;margin:0">
                            <span style="font-weight:600;color:var(--danger)">🔴 Pasif</span>
                            <span style="font-size:11.5px;color:var(--text-muted);margin-left:auto">Gizli</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.banka.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Banka Hesabı Ekle</span>
        </button>
    </div>
</form>

@endsection