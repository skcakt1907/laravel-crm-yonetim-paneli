@extends('admin._layout')

@section('title', 'Fatura Ayarları')

@push('head')
@include('admin.ayarlar._partials.styles')
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Fatura</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="receipt"></i>
            Fatura Ayarları
        </h1>
        <div class="page-subtitle">Faturada görünecek firma bilgileri ve otomasyon</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'fatura'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.fatura.post') }}" method="POST">
            @csrf

            <div class="info-card">
                <div class="ic"><i data-lucide="info"></i></div>
                <div class="body">
                    Bu bilgiler kesilen fatura ve makbuzlarda gönderici firma olarak görünecektir.
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="building-2"></i>
                    <span>Firma Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Firma Adı (Faturada görünür)</label>
                        <input type="text" name="fatura_firma_adi"
                               value="{{ old('fatura_firma_adi', $ayarlar->fatura_firma_adi ?? '') }}"
                               class="form-input"
                               placeholder="DN Grup Medya ve Teknoloji A.Ş.">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vergi Numarası</label>
                        <input type="text" name="fatura_vergi_no"
                               value="{{ old('fatura_vergi_no', $ayarlar->fatura_vergi_no ?? '') }}"
                               class="form-input"
                               style="font-family:monospace"
                               placeholder="1234567890">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="fatura_vergi_dairesi"
                               value="{{ old('fatura_vergi_dairesi', $ayarlar->fatura_vergi_dairesi ?? '') }}"
                               class="form-input"
                               placeholder="Şişli">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Fatura Adresi</label>
                        <textarea name="fatura_adres" rows="3" class="form-textarea"
                                  placeholder="Tam adres">{{ old('fatura_adres', $ayarlar->fatura_adres ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="phone"></i>
                    <span>İletişim Bilgileri (Faturada)</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="fatura_telefon"
                               value="{{ old('fatura_telefon', $ayarlar->fatura_telefon ?? '') }}"
                               class="form-input"
                               placeholder="+90 212 ...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="fatura_email"
                               value="{{ old('fatura_email', $ayarlar->fatura_email ?? '') }}"
                               class="form-input"
                               placeholder="fatura@firma.com">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Web Sitesi</label>
                        <input type="url" name="fatura_web"
                               value="{{ old('fatura_web', $ayarlar->fatura_web ?? '') }}"
                               class="form-input"
                               placeholder="https://firma.com">
                    </div>
                </div>
            </div>

            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="zap"></i>
                    <span>Otomasyon</span>
                </div>

                @php $otoKes = (int)($ayarlar->fatura_otomatik_kes ?? 0) === 1; @endphp
                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Otomatik Fatura Kes</div>
                        <div class="desc">Ödeme tamamlandığında fatura otomatik olarak kesilir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="hidden" name="fatura_otomatik_kes" value="0">
                        <input type="checkbox" name="fatura_otomatik_kes" value="1"
                               {{ $otoKes ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Yeni faturalarda bu bilgiler kullanılır
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Fatura Ayarlarını Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection