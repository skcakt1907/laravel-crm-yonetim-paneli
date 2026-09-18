@extends('admin._layout')

@section('title', 'Fiyat Düzenle')

@push('head')
@include("admin._partials.form-css.domain-fiyatlar")
@endpush

@section('content')

@php
    $isLegacy = !empty($fiyat->is_legacy) || !empty($fiyat->legacy_id);
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.domain.fiyatlar.index') }}">Alan Adı Fiyatları</a>
    <span class="sep">/</span>
    <span class="current">{{ $fiyat->uzanti ?? 'Düzenle' }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Fiyat Düzenle
        </h1>
        <div class="page-subtitle">
            <strong style="color:var(--brand-dark);font-family:monospace">
                @if(!str_starts_with($fiyat->uzanti ?? '', '.'))
                    .{{ $fiyat->uzanti }}
                @else
                    {{ $fiyat->uzanti }}
                @endif
            </strong>
            @if($isLegacy)
                · <span class="badge badge-warning" style="font-size:11px">
                    <i data-lucide="archive" style="width:11px;height:11px"></i>
                    Legacy
                </span>
            @endif
        </div>
    </div>
</div>

@if($isLegacy)
    <div class="alert alert-info" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;background:rgba(59,130,246,0.06);border-color:rgba(59,130,246,0.3)">
        <i data-lucide="info" style="width:20px;height:20px;flex-shrink:0;margin-top:2px;color:#3b82f6"></i>
        <div>
            <strong>Legacy kayıt düzenleniyor.</strong>
            <div style="margin-top:6px;font-size:13px">
                Bu fiyat <code style="background:rgba(0,0,0,0.1);padding:1px 6px;border-radius:4px">alanadi</code>
                tablosundaki JSON yapısının bir parçasıdır. Transfer fiyatı bu yapıda saklanmaz.
            </div>
        </div>
    </div>
@endif

<form action="{{ route('admin.domain.fiyatlar.duzenlePost', $fiyat->id) }}" method="POST">
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
                    <input type="text" name="uzanti"
                           value="{{ old('uzanti', $fiyat->uzanti ?? '') }}"
                           required class="form-input"
                           style="font-family:'SF Mono','Monaco',monospace;font-weight:600">
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
                                   value="{{ old('kayit_fiyat', $fiyat->kayit_fiyat ?? 0) }}"
                                   required class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Yenileme Fiyatı <span class="required">*</span></label>
                        <div class="fiyat-input">
                            <input type="number" step="0.01" min="0" name="yenileme_fiyat"
                                   value="{{ old('yenileme_fiyat', $fiyat->yenileme_fiyat ?? 0) }}"
                                   required class="form-input">
                        </div>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">
                            Transfer Fiyatı <span class="required">*</span>
                            @if($isLegacy)
                                <span style="color:var(--text-muted);font-weight:500;font-size:11px;margin-left:6px">
                                    (Legacy'de saklanmaz)
                                </span>
                            @endif
                        </label>
                        <div class="fiyat-input">
                            <input type="number" step="0.01" min="0" name="transfer_fiyat"
                                   value="{{ old('transfer_fiyat', $fiyat->transfer_fiyat ?? 0) }}"
                                   required class="form-input"
                                   {{ $isLegacy ? 'readonly' : '' }}>
                        </div>
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
                        <div class="desc">Müşteriler bu uzantıyı görebilir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', $fiyat->durum ?? 1) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            <div class="section" style="margin-top:14px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Bilgi</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="lbl">ID</span>
                        <span class="val" style="font-family:monospace;font-size:12px">{{ $fiyat->id }}</span>
                    </div>
                    @if($isLegacy)
                        <div class="info-item">
                            <span class="lbl">Tablo</span>
                            <span class="val" style="font-family:monospace;font-size:11px">alanadi</span>
                        </div>
                        @if(isset($fiyat->legacy_id))
                            <div class="info-item">
                                <span class="lbl">Legacy ID</span>
                                <span class="val">{{ $fiyat->legacy_id }}</span>
                            </div>
                        @endif
                    @else
                        <div class="info-item">
                            <span class="lbl">Tablo</span>
                            <span class="val" style="font-family:monospace;font-size:11px">domain_fiyatlar</span>
                        </div>
                    @endif
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
            <span>Değişiklikleri Kaydet</span>
        </button>
    </div>
</form>

@endsection