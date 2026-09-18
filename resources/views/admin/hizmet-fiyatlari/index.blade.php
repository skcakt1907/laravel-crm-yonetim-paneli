@extends('admin._layout')

@section('title', 'Hizmet Fiyatları')

@push('head')
<style>
    .fiyat-row {
        display: grid;
        grid-template-columns: 1fr 200px 44px;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        transition: border-color 0.15s;
    }
    .fiyat-row:hover { border-color: var(--brand-medium); }
    .fiyat-row + .fiyat-row { margin-top: 8px; }

    .fiyat-row .ad-bolum .baslik {
        font-weight: 600;
        font-size: 14px;
        color: var(--text);
    }
    .fiyat-row .ad-bolum .anahtar {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 3px;
        font-family: 'SF Mono','Monaco','Consolas',monospace;
    }

    .fiyat-row .input-bolum {
        position: relative;
    }
    .fiyat-row .input-bolum::before {
        content: "₺";
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        color: var(--brand-dark);
        font-weight: 700;
        z-index: 1;
        pointer-events: none;
    }
    .fiyat-row .input-bolum input {
        padding-left: 30px !important;
        text-align: right;
        font-weight: 600;
    }

    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        z-index: 10;
    }

    .yeni-ekle-section {
        background: linear-gradient(135deg, rgba(16,185,129,0.05), rgba(16,185,129,0.02));
        border: 1px dashed rgba(16,185,129,0.35);
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    @if(Route::has('admin.hizmetler.index'))
        <a href="{{ route('admin.hizmetler.index') }}">Hizmetler</a>
        <span class="sep">/</span>
    @endif
    <span class="current">Fiyatlar</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="banknote"></i>
            Hizmet Fiyatları
            <span class="badge badge-brand">{{ count($fiyatlar ?? []) }}</span>
        </h1>
        <div class="page-subtitle">Standart hizmet fiyatlarınızı toplu olarak güncelleyin (yıllık)</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.hizmetler.index'))
            <a href="{{ route('admin.hizmetler.index') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="arrow-left"></i>
                <span>Hizmetlere Dön</span>
            </a>
        @endif
    </div>
</div>

{{-- 3 STAT MİNİ --}}
@php
    $toplamHiz = count($fiyatlar ?? []);
    $toplamFiyat = 0;
    $maxFiyat = 0;
    foreach (($fiyatlar ?? []) as $f) {
        $f_val = (float)($f->fiyat ?? 0);
        $toplamFiyat += $f_val;
        if ($f_val > $maxFiyat) $maxFiyat = $f_val;
    }
    $ortFiyat = $toplamHiz > 0 ? $toplamFiyat / $toplamHiz : 0;
@endphp

<div class="stat-grid" style="margin-bottom:20px;grid-template-columns:repeat(3,1fr)">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="list"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Hizmet</div>
            <div class="stat-card-value">{{ $toplamHiz }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="trending-up"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Ortalama Fiyat</div>
            <div class="stat-card-value" style="color:var(--brand-dark);font-size:22px">
                ₺{{ number_format($ortFiyat, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="award"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">En Yüksek</div>
            <div class="stat-card-value" style="color:var(--success);font-size:22px">
                ₺{{ number_format($maxFiyat, 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>

{{-- ANA FORM: Güncelleme + Yeni Ekleme --}}
<form action="{{ route('admin.hizmet-fiyatlari.update') }}" method="POST">
    @csrf

    {{-- MEVCUT FİYATLAR --}}
    <div class="section">
        <div class="section-title">
            <i data-lucide="list"></i>
            <span>Mevcut Hizmet Fiyatları</span>
            <span style="margin-left:auto;font-size:12px;color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0">
                Yıllık fiyatlar (TL)
            </span>
        </div>

        @if(empty($fiyatlar) || count($fiyatlar) === 0)
            <div class="empty-state">
                <i data-lucide="banknote" class="empty-state-icon"></i>
                <h4>Henüz hizmet fiyatı yok</h4>
                <p>Aşağıdaki "Yeni Hizmet Ekle" bölümünden ilk fiyatınızı tanımlayın.</p>
            </div>
        @else
            <div>
                @foreach($fiyatlar as $f)
                    <div class="fiyat-row">
                        <div class="ad-bolum">
                            <div class="baslik">{{ $f->etiket ?? '—' }}</div>
                            <div class="anahtar">
                                <i data-lucide="key" style="width:10px;height:10px;display:inline;vertical-align:middle"></i>
                                {{ $f->anahtar ?? '—' }}
                            </div>
                        </div>
                        <div class="input-bolum">
                            <input type="number" step="0.01" min="0"
                                   name="fiyat[{{ $f->id }}]"
                                   value="{{ (float)($f->fiyat ?? 0) }}"
                                   class="form-input">
                        </div>
                        <div>
                            {{-- HTML iç içe form yasak - sil JS ile ayrı forma submit --}}
                            <button type="button" class="table-action"
                                    title="Sil" style="color:var(--danger)"
                                    onclick="silFiyat({{ $f->id }}, '{{ addslashes($f->etiket ?? '') }}');">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- YENİ HİZMET EKLE --}}
    <div class="section yeni-ekle-section" style="margin-top:16px">
        <div class="section-title">
            <i data-lucide="plus-circle"></i>
            <span>Yeni Hizmet Ekle</span>
            <span style="margin-left:auto;font-size:12px;color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0">
                Opsiyonel
            </span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 220px;gap:12px;align-items:end">
            <div class="form-group" style="margin:0">
                <label class="form-label">Hizmet Adı</label>
                <input type="text" name="yeni_etiket" value="{{ old('yeni_etiket') }}"
                       placeholder="Örn: Cloud Storage Yıllık"
                       class="form-input">
                <small class="form-help">Anahtar otomatik üretilir (slug formatında)</small>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Yıllık Fiyat (₺)</label>
                <input type="number" step="0.01" min="0" name="yeni_fiyat"
                       value="{{ old('yeni_fiyat') }}"
                       placeholder="500" class="form-input">
            </div>
        </div>
    </div>

    {{-- STICKY ALT BAR --}}
    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            Hem mevcut fiyatlar hem de yeni hizmet (varsa) tek seferde kaydedilir
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Tümünü Kaydet</span>
        </button>
    </div>
</form>

{{-- SİL FORMLARI (HTML iç içe form yasak — ana formun DIŞINDA) --}}
@foreach($fiyatlar ?? [] as $f)
    <form id="del-fiyat-{{ $f->id }}"
          action="{{ route('admin.hizmet-fiyatlari.destroy', $f->id) }}"
          method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endforeach

<script>
function silFiyat(id, etiket) {
    if (confirm('Bu hizmeti silmek istediğinize emin misiniz?\n\n' + etiket)) {
        document.getElementById('del-fiyat-' + id).submit();
    }
}
</script>

@endsection