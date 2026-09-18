@extends('admin._layout')

@section('title', 'Yeni Alan Adı Fiyatı')

@push('head')
@include("admin._partials.form-css.domain-fiyatlar")
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.domain.fiyatlar.index') }}">Alan Adı Fiyatları</a>
    <span class="sep">/</span>
    <span class="current">Yeni Uzantı</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="plus-circle"></i>
            Yeni Alan Adı Fiyatı
        </h1>
        <div class="page-subtitle">Bir domain uzantısı ve fiyatlarını tanımlayın</div>
    </div>
</div>

<form action="{{ route('admin.domain.fiyatlar.eklePost') }}" method="POST">
    @csrf

    <div class="form-grid">
        <div>
            {{-- UZANTI --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="globe"></i>
                    <span>Uzantı Bilgisi</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Uzantı <span class="required">*</span></label>
                    <input type="text" name="uzanti" value="{{ old('uzanti') }}"
                           required class="form-input"
                           placeholder=".com"
                           style="font-family:'SF Mono','Monaco',monospace;font-weight:600">
                    <small class="form-help">
                        Örnek: <code>.com</code>, <code>.com.tr</code>, <code>.net</code>, <code>.org</code>
                    </small>
                </div>
            </div>

            {{-- FİYATLAR --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="dollar-sign"></i>
                    <span>Fiyatlar (Yıllık)</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kayıt Fiyatı <span class="required">*</span></label>
                        <div class="fiyat-input">
                            <input type="number" step="0.01" min="0" name="kayit_fiyat"
                                   value="{{ old('kayit_fiyat') }}"
                                   required class="form-input"
                                   placeholder="89.00">
                        </div>
                        <small class="form-help">Yeni alan adı satın alma fiyatı</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Yenileme Fiyatı <span class="required">*</span></label>
                        <div class="fiyat-input">
                            <input type="number" step="0.01" min="0" name="yenileme_fiyat"
                                   value="{{ old('yenileme_fiyat') }}"
                                   required class="form-input"
                                   placeholder="89.00">
                        </div>
                        <small class="form-help">Süresi dolan domainin yenileme fiyatı</small>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Transfer Fiyatı <span class="required">*</span></label>
                        <div class="fiyat-input">
                            <input type="number" step="0.01" min="0" name="transfer_fiyat"
                                   value="{{ old('transfer_fiyat') }}"
                                   required class="form-input"
                                   placeholder="89.00">
                        </div>
                        <small class="form-help">Başka servisten transfer fiyatı (yoksa 0 yazın)</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="eye"></i>
                    <span>Görünürlük</span>
                </div>
                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Müşteriler bu uzantıyı görebilir ve satın alabilir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.7">
                    <p style="margin:0 0 8px"><span class="required">*</span> ile işaretli alanlar zorunludur.</p>
                    <p style="margin:0 0 8px">
                        Uzantı tek bir uzantı olmalıdır. Birden fazla uzantı için ayrı kayıtlar açın.
                    </p>
                    <p style="margin:0">
                        Transfer fiyatı genelde kayıt fiyatına yakındır, vermek istemiyorsanız <strong>0</strong> yazabilirsiniz.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ALT AKSİYON --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:24px;flex-wrap:wrap">
        <a href="{{ route('admin.domain.fiyatlar.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Fiyatı Kaydet</span>
        </button>
    </div>
</form>

@endsection