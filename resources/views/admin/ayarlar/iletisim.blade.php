@extends('admin._layout')

@section('title', 'İletişim Ayarları')

@push('head')
@include('admin.ayarlar._partials.styles')
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">İletişim</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="phone"></i>
            İletişim Ayarları
        </h1>
        <div class="page-subtitle">Telefon, e-posta, adres ve harita bilgileri</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'iletisim'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.iletisim.post') }}" method="POST">
            @csrf

            <div class="section">
                <div class="section-title">
                    <i data-lucide="building-2"></i>
                    <span>Firma İletişim Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" name="firma_adi"
                               value="{{ old('firma_adi', $ayarlar->firma_adi ?? '') }}"
                               class="form-input"
                               placeholder="DN Kreatif">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i data-lucide="phone" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                            Telefon
                        </label>
                        <input type="text" name="telefon"
                               value="{{ old('telefon', $ayarlar->firma_telefon ?? '') }}"
                               class="form-input"
                               placeholder="+90 212 ...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i data-lucide="mail" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                            E-posta
                        </label>
                        <input type="email" name="email"
                               value="{{ old('email', $ayarlar->firma_email ?? '') }}"
                               class="form-input"
                               placeholder="info@...">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">
                            <i data-lucide="map-pin" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                            Adres
                        </label>
                        <textarea name="adres" rows="3" class="form-textarea"
                                  placeholder="Tam adres">{{ old('adres', $ayarlar->firma_adres ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="map"></i>
                    <span>Google Maps</span>
                </div>

                <div class="info-card">
                    <div class="ic"><i data-lucide="info"></i></div>
                    <div class="body">
                        Google Maps'ten "Yerleştir" kodunu (iframe) kopyalayıp buraya yapıştırın.
                        Sadece <code>src="..."</code> kısmını da yapıştırabilirsiniz.
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Google Maps Embed Kodu</label>
                    <textarea name="maps" rows="4" class="form-textarea"
                              placeholder='<iframe src="https://www.google.com/maps/embed?..."></iframe>'
                              style="font-family:monospace;font-size:12.5px">{{ old('maps', $ayarlar->google_maps ?? '') }}</textarea>
                </div>

                <div class="info-card" style="margin-top:14px">
                    <div class="ic"><i data-lucide="navigation"></i></div>
                    <div class="body">
                        <strong>Randevu konum linkleri</strong> artık her lokasyona ayrı tanımlanıyor.
                        <a href="{{ route('admin.randevu.hizmetler') }}">Randevu → Lokasyonlar</a> sayfasından
                        ilgili ofisi (Gaziantep / İstanbul / Marmaris) düzenleyip <em>Konum Linki</em> alanına
                        Google Maps bağlantısını girin. Randevuda o ofis seçilince link müşteriye SMS ile gider.
                    </div>
                </div>

                @if(!empty($ayarlar->google_maps))
                    <div style="margin-top:14px;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--border)">
                        <div style="padding:8px 12px;background:var(--bg-subtle);font-size:12px;color:var(--text-secondary);font-weight:600">
                            <i data-lucide="eye" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                            Önizleme
                        </div>
                        <div style="aspect-ratio:16/7;background:var(--bg-subtle);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:12px">
                            Harita önizlemesi sitede görünecek
                        </div>
                    </div>
                @endif
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Değişiklikler sitede iletişim bölümlerine yansır
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>İletişim Ayarlarını Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection