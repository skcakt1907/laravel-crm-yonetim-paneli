@extends('admin._layout')

@section('title', 'Bayi Düzenle')

@push('head')
<style>
    .toggle-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; gap: 16px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
    }
    .toggle-card .desc { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
    .toggle-card .lbl-strong { font-weight: 600; font-size: 14px; }

    .ios-toggle { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
    .ios-toggle input { opacity: 0; width: 0; height: 0; }
    .ios-toggle .knob {
        position: absolute; cursor: pointer; inset: 0;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 26px;
        transition: 0.25s;
    }
    .ios-toggle .knob:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background: #fff;
        border-radius: 50%; transition: 0.25s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .ios-toggle input:checked + .knob {
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        border-color: var(--brand);
    }
    .ios-toggle input:checked + .knob:before { transform: translateX(22px); }

    .readonly-box {
        background: var(--bg-subtle);
        border: 1px dashed var(--border);
        border-radius: var(--radius-md);
        padding: 14px;
    }
    .readonly-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
    .readonly-row + .readonly-row { border-top: 1px dashed var(--border); }
    .readonly-row .lbl { color: var(--text-muted); }
    .readonly-row .val { font-weight: 600; }
</style>
@endpush

@section('content')

@php
    $bayiAd = trim(($bayi->ad ?? '') . ' ' . ($bayi->soyad ?? '')) ?: 'Bayi';
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.bayiler.index') }}">Bayiler</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.bayiler.detay', $bayi->id) }}">{{ $bayiAd }}</a>
    <span class="sep">/</span>
    <span class="current">Düzenle</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Bayi Düzenle
        </h1>
        <div class="page-subtitle">
            <strong style="color:var(--text)">{{ $bayiAd }}</strong> bilgilerini güncelle
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.bayiler.detay', $bayi->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="eye"></i>
            <span>Detayı Gör</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.bayiler.guncelle', $bayi->id) }}" method="POST">
    @csrf

    <div class="form-grid">
        <div>
            {{-- KOMİSYON BİLGİLERİ --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="percent"></i>
                    <span>Komisyon ve İndirim Oranları</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Genel Komisyon (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="komisyon_orani"
                               value="{{ old('komisyon_orani', $bayi->komisyon_orani ?? 10) }}"
                               min="0" max="100" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Müşteri İndirim (%) <span class="required">*</span></label>
                        <input type="number" step="0.01" name="musteri_indirim_orani"
                               value="{{ old('musteri_indirim_orani', $bayi->musteri_indirim_orani ?? 0) }}"
                               min="0" max="100" required class="form-input">
                        <small class="form-help">Bayinin müşterilerine yapacağı maksimum indirim</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Peşin Satış Komisyon (%)</label>
                        <input type="number" step="0.01" name="pesin_komisyon_orani"
                               value="{{ old('pesin_komisyon_orani', $bayi->pesin_komisyon_orani ?? 0) }}"
                               min="0" max="100" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vadeli Satış Komisyon (%)</label>
                        <input type="number" step="0.01" name="vadeli_komisyon_orani"
                               value="{{ old('vadeli_komisyon_orani', $bayi->vadeli_komisyon_orani ?? 0) }}"
                               min="0" max="100" class="form-input">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Min. Sepet Tutarı (₺)</label>
                        <input type="number" step="0.01" name="min_sepet_tutari"
                               value="{{ old('min_sepet_tutari', $bayi->min_sepet_tutari ?? 0) }}"
                               min="0" class="form-input">
                        <small class="form-help">Bu tutarın altındaki sepetlerde bayi komisyon kazanmaz</small>
                    </div>
                </div>
            </div>

            {{-- KONUM BİLGİLERİ --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="map-pin"></i>
                    <span>Konum Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">İl</label>
                        <input type="text" name="il" value="{{ old('il', $bayi->il ?? '') }}"
                               class="form-input" placeholder="İstanbul">
                    </div>
                    <div class="form-group">
                        <label class="form-label">İlçe</label>
                        <input type="text" name="ilce" value="{{ old('ilce', $bayi->ilce ?? '') }}"
                               class="form-input" placeholder="Kadıköy">
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            {{-- BAYİ KİMLİK (READONLY) --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="badge"></i>
                    <span>Bayi Kimliği</span>
                </div>
                <div class="readonly-box">
                    <div class="readonly-row">
                        <span class="lbl">Ad Soyad</span>
                        <span class="val">{{ $bayiAd }}</span>
                    </div>
                    <div class="readonly-row">
                        <span class="lbl">E-posta</span>
                        <span class="val" style="font-size:12px">{{ $bayi->email ?? '—' }}</span>
                    </div>
                    @if(!empty($bayi->telefon))
                        <div class="readonly-row">
                            <span class="lbl">Telefon</span>
                            <span class="val">{{ $bayi->telefon }}</span>
                        </div>
                    @endif
                    <div class="readonly-row">
                        <span class="lbl">Bayi Kodu</span>
                        <span class="val" style="font-family:monospace;color:var(--brand-dark)">
                            {{ $bayi->bayi_kodu ?? '—' }}
                        </span>
                    </div>
                    <div class="readonly-row">
                        <span class="lbl">Bayi ID</span>
                        <span class="val">#{{ $bayi->id }}</span>
                    </div>
                </div>
                <small class="form-help" style="margin-top:10px;display:block">
                    Üye bilgilerini değiştirmek için üye düzenleme sayfasını kullanın.
                </small>
            </div>

            {{-- DURUM --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="shield-check"></i>
                    <span>Aktiflik ve Onay</span>
                </div>

                <div class="toggle-card" style="margin-bottom:12px">
                    <div>
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Bayi sisteme giriş yapabilir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', $bayi->durum ?? 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Onaylı</div>
                        <div class="desc">Bayi başvurusu onaylanmış</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="onay_durumu" value="1"
                               {{ old('onay_durumu', ($bayi->onay_durumu ?? 0) == 1 ? 1 : 0) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <small class="form-help" style="margin-top:10px;display:block">
                    Onay durumunu reddetmek için <a href="{{ route('admin.bayiler.detay', $bayi->id) }}"
                    style="color:var(--brand-dark);font-weight:600">detay sayfasındaki</a> "Reddet" butonunu kullanın.
                </small>
            </div>
        </div>
    </div>

    {{-- ALT AKSİYON --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:24px;flex-wrap:wrap">
        <a href="{{ route('admin.bayiler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Değişiklikleri Kaydet</span>
        </button>
    </div>
</form>

@endsection