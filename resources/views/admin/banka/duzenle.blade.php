@extends('admin._layout')

@section('title', 'Banka Hesabı Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.banka.index') }}">Banka Hesapları</a>
    <span class="sep">/</span>
    <span class="current">{{ $banka->banka ?? 'Düzenle' }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ {{ $banka->banka ?? '—' }}</h1>
        <div class="page-subtitle">Hesap bilgilerini güncelle</div>
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

<form action="{{ route('admin.banka.duzenlePost', $banka->id) }}" method="POST" enctype="multipart/form-data">
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
                    <input type="text" name="banka" value="{{ old('banka', $banka->banka) }}" required maxlength="255" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Hesap Sahibi <span class="required">*</span></label>
                    <input type="text" name="hesap" value="{{ old('hesap', $banka->hesap) }}" required maxlength="255" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">IBAN</label>
                    <input type="text" name="iban" value="{{ old('iban', $banka->iban) }}" maxlength="34" class="form-input" style="font-family:monospace;text-transform:uppercase">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Hesap No</label>
                        <input type="text" name="hnumara" value="{{ old('hnumara', $banka->hnumara) }}" maxlength="50" class="form-input" style="font-family:monospace">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şube</label>
                        <input type="text" name="sube" value="{{ old('sube', $banka->sube) }}" maxlength="100" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Durum + logo + meta --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="image"></i>
                    <span>Banka Logosu</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Logo (opsiyonel)</label>
                    <input type="file" name="resim" accept="image/*" class="form-input">
                    @if(!empty($banka->resim))
                        <div style="margin-top:8px;padding:8px;background:var(--bg-subtle);border-radius:var(--radius-md);font-size:11.5px;color:var(--text-muted)">
                            📎 Mevcut: <code>{{ $banka->resim }}</code>
                        </div>
                    @endif
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="flag"></i>
                    <span>Durum</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    @php $currentDurum = old('durum', $banka->durum); @endphp
                    <div style="display:flex;flex-direction:column;gap:8px">
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid {{ $currentDurum == 1 ? 'var(--success)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentDurum == 1 ? 'rgba(16,185,129,0.06)' : 'transparent' }}">
                            <input type="radio" name="durum" value="1" {{ $currentDurum == 1 ? 'checked' : '' }} style="width:auto;margin:0">
                            <span style="font-weight:600;color:var(--success)">🟢 Aktif</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid {{ $currentDurum == 0 ? 'var(--danger)' : 'var(--border)' }};border-radius:var(--radius-md);cursor:pointer;background:{{ $currentDurum == 0 ? 'rgba(239,68,68,0.06)' : 'transparent' }}">
                            <input type="radio" name="durum" value="0" {{ $currentDurum == 0 ? 'checked' : '' }} style="width:auto;margin:0">
                            <span style="font-weight:600;color:var(--danger)">🔴 Pasif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="lbl">Hesap ID</span>
                        <span class="val">#{{ $banka->id }}</span>
                    </div>
                    @if(!empty($banka->tarih))
                        <div class="info-item">
                            <span class="lbl">Eklenme</span>
                            <span class="val">{{ \Carbon\Carbon::parse($banka->tarih)->format('d.m.Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <button type="button" onclick="document.getElementById('sil-form').submit()" class="btn btn-danger">
            <i data-lucide="trash-2"></i>
            <span>Hesabı Sil</span>
        </button>

        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.banka.index') }}" class="btn btn-secondary">İptal</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Güncelle</span>
            </button>
        </div>
    </div>
</form>

{{-- Sil formu - ANA FORMUN DIŞINDA (HTML iç içe form'a izin vermez) --}}
<form id="sil-form" action="{{ route('admin.banka.sil', $banka->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($banka->banka ?? 'Bu hesap') }} silinsin mi?');" style="display:none">
    @csrf @method('DELETE')
</form>

@endsection