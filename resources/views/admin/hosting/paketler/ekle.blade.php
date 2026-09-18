@extends('admin._layout')

@section('title', 'Yeni Hosting Paketi')

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

    /* Spec input - ikon prefix'li */
    .spec-input { position: relative; }
    .spec-input .ic {
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        color: var(--brand-dark);
        pointer-events: none;
    }
    .spec-input input { padding-left: 42px !important; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.hosting.paketler.index') }}">Hosting Paketleri</a>
    <span class="sep">/</span>
    <span class="current">Yeni Paket</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="server-cog"></i>
            Yeni Hosting Paketi
        </h1>
        <div class="page-subtitle">Yeni bir hosting paketi tanımlayın</div>
    </div>
</div>

<form action="{{ route('admin.hosting.paketler.eklePost') }}" method="POST">
    @csrf

    <div class="form-grid">
        <div>
            {{-- PAKET BİLGİLERİ --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="package"></i>
                    <span>Paket Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Paket Adı <span class="required">*</span></label>
                        <input type="text" name="adi" value="{{ old('adi') }}"
                               required class="form-input"
                               placeholder="Örn: Başlangıç Hosting">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Yıllık Fiyat (₺) <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" name="fiyat"
                               value="{{ old('fiyat') }}"
                               required class="form-input"
                               placeholder="299.00">
                    </div>

                    @if(isset($kategoriler) && count($kategoriler) > 0)
                        <div class="form-group full">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select">
                                <option value="">— Otomatik (ilk aktif kategori) —</option>
                                @foreach($kategoriler as $k)
                                    <option value="{{ $k->id }}"
                                        {{ old('kategori') == $k->id ? 'selected' : '' }}>
                                        {{ $k->adi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            {{-- TEKNİK SPESİFİKASYONLAR --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Teknik Spesifikasyonlar</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Disk Alanı <span class="required">*</span></label>
                        <div class="spec-input">
                            <i data-lucide="hard-drive" class="ic" style="width:16px;height:16px"></i>
                            <input type="text" name="disk_alani" value="{{ old('disk_alani') }}"
                                   required class="form-input"
                                   placeholder="10 GB">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trafik <span class="required">*</span></label>
                        <div class="spec-input">
                            <i data-lucide="activity" class="ic" style="width:16px;height:16px"></i>
                            <input type="text" name="trafik" value="{{ old('trafik') }}"
                                   required class="form-input"
                                   placeholder="100 GB">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Veritabanı Sayısı <span class="required">*</span></label>
                        <div class="spec-input">
                            <i data-lucide="database" class="ic" style="width:16px;height:16px"></i>
                            <input type="text" name="veritabani" value="{{ old('veritabani') }}"
                                   required class="form-input"
                                   placeholder="5">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-posta Hesap Sayısı <span class="required">*</span></label>
                        <div class="spec-input">
                            <i data-lucide="mail" class="ic" style="width:16px;height:16px"></i>
                            <input type="text" name="email" value="{{ old('email') }}"
                                   required class="form-input"
                                   placeholder="10">
                        </div>
                    </div>
                </div>
            </div>

            {{-- AÇIKLAMA + ÖZELLİKLER --}}
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Açıklama ve Özellikler</span>
                </div>
                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Açıklama</label>
                        <textarea name="aciklama" rows="3" class="form-textarea"
                                  placeholder="Paketin kısa tanıtımı">{{ old('aciklama') }}</textarea>
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Özellikler</label>
                        <textarea name="ozellikler" rows="6" class="form-textarea"
                                  placeholder="✓ Ücretsiz SSL&#10;✓ Günlük Yedekleme&#10;✓ cPanel Dahil&#10;✓ 7/24 Destek">{{ old('ozellikler') }}</textarea>
                        <small class="form-help">Her satır bir özelliği temsil eder</small>
                    </div>
                </div>
            </div>

            {{-- ÇOKLU DİL --}}
            <div style="margin-top:16px">
                @include('admin.partials.ceviri-tabs', [
                    'tablo'     => $tableName ?? 'hostingler',
                    'kayit_id'  => null,
                    'tr_values' => [],
                    'fields'    => [
                        'adi'      => ['label' => 'Paket Adı', 'type' => 'text', 'required' => true],
                        'aciklama' => ['label' => 'Açıklama', 'type' => 'textarea', 'rows' => 4],
                    ],
                ])
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
                        <div class="desc">Site üzerinde görünür ve satın alınabilir</div>
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
                        Disk Alanı / Trafik gibi alanlarda metin yazabilirsiniz (örn: "Sınırsız", "10 GB", "100 GB").
                    </p>
                    <p style="margin:0">
                        Fiyatlar <strong>yıllık</strong> olarak girilir.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.hosting.paketler.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Paketi Kaydet</span>
            </button>
        </div>
    </div>
</form>

@endsection