@extends('admin._layout')

@section('title', 'Bakım Modu')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .bakim-hero {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        border-radius: var(--radius-lg);
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 16px;
    }
    .bakim-hero.aktif {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        animation: pulse-warn 2.5s ease-in-out infinite;
    }
    @keyframes pulse-warn {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
    }
    .bakim-hero-icon {
        width: 64px; height: 64px;
        background: rgba(255,255,255,0.2);
        border-radius: var(--radius-lg);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .bakim-hero-icon svg {
        width: 32px; height: 32px;
    }
    .bakim-hero h2 {
        margin: 0 0 4px;
        font-size: 22px;
    }
    .bakim-hero p {
        margin: 0;
        opacity: 0.95;
        font-size: 13.5px;
    }

    .preview-frame {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border-radius: var(--radius-lg);
        padding: 40px 28px;
        text-align: center;
        margin-top: 14px;
        border: 1px solid var(--border);
    }
    .preview-frame .pf-icon {
        width: 64px; height: 64px;
        margin: 0 auto 16px;
        color: #f59e0b;
    }
    .preview-frame .pf-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .preview-frame .pf-msg {
        font-size: 14px;
        opacity: 0.85;
        max-width: 400px;
        margin: 0 auto;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Bakım Modu</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="wrench"></i>
            Bakım Modu
        </h1>
        <div class="page-subtitle">Tüm siteyi bakıma alın, ziyaretçilere özel mesaj gösterin</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'bakim'])

    <div class="ayarlar-content">
        @php $bakimAktif = (int)($ayarlar->bakim_modu ?? 0) === 1; @endphp

        <div class="bakim-hero {{ $bakimAktif ? 'aktif' : '' }}">
            <div class="bakim-hero-icon">
                <i data-lucide="{{ $bakimAktif ? 'alert-triangle' : 'shield-check' }}"></i>
            </div>
            <div style="flex:1;min-width:0">
                <h2>{{ $bakimAktif ? 'Site BAKIMDA' : 'Site YAYINDA' }}</h2>
                <p>
                    {{ $bakimAktif
                        ? 'Şu anda ziyaretçiler bakım sayfasını görüyor. Sadece admin panele erişilebilir.'
                        : 'Site normal şekilde çalışıyor. Bakım moduna almak için aşağıdaki ayarları yapın.' }}
                </p>
            </div>
        </div>

        <form action="{{ route('admin.ayarlar.bakim.post') }}" method="POST">
            @csrf

            <div class="section">
                <div class="section-title">
                    <i data-lucide="power"></i>
                    <span>Bakım Modu Durumu</span>
                </div>

                <div class="toggle-card" style="background:{{ $bakimAktif ? 'rgba(239,68,68,0.1)' : 'var(--brand-soft)' }};border-color:{{ $bakimAktif ? 'rgba(239,68,68,0.3)' : 'rgba(184,182,46,0.2)' }}">
                    <div>
                        <div class="lbl-strong">{{ $bakimAktif ? '🔴 Bakım Modu Aktif' : '🟢 Bakım Modu Kapalı' }}</div>
                        <div class="desc">Aktif edildiğinde ziyaretçilere bakım sayfası gösterilir</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="hidden" name="bakim_modu" value="0">
                        <input type="checkbox" name="bakim_modu" value="1"
                               {{ $bakimAktif ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="message-square-text"></i>
                    <span>Bakım Mesajı</span>
                </div>

                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Bakım Sayfası Başlığı</label>
                        <input type="text" name="bakim_baslik"
                               value="{{ old('bakim_baslik', $ayarlar->bakim_baslik ?? 'Site Bakımda') }}"
                               class="form-input"
                               placeholder="Site Bakımda"
                               oninput="document.getElementById('pfTitle').textContent = this.value || 'Site Bakımda'">
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Bakım Mesajı</label>
                        <textarea name="bakim_mesaj" rows="4" class="form-textarea"
                                  placeholder="Sevgili ziyaretçilerimiz, sitemiz şu anda bakımdadır. Kısa süre içinde hizmetinizde olacağız."
                                  oninput="document.getElementById('pfMsg').textContent = this.value || 'Sevgili ziyaretçi, sitemiz bakımdadır.'">{{ old('bakim_mesaj', $ayarlar->bakim_mesaj ?? 'Sevgili ziyaretçilerimiz, sitemiz şu anda bakımdadır.') }}</textarea>
                    </div>
                </div>

                {{-- ÖNİZLEME --}}
                <div style="margin-top:14px">
                    <div style="padding:8px 12px;background:var(--bg-subtle);font-size:12px;color:var(--text-secondary);font-weight:600;border-radius:var(--radius-md) var(--radius-md) 0 0;border:1px solid var(--border);border-bottom:0">
                        <i data-lucide="eye" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                        Ziyaretçilerin Göreceği Sayfa (Önizleme)
                    </div>
                    <div class="preview-frame" style="border-radius:0 0 var(--radius-lg) var(--radius-lg)">
                        <i data-lucide="wrench" class="pf-icon"></i>
                        <div class="pf-title" id="pfTitle">{{ $ayarlar->bakim_baslik ?? 'Site Bakımda' }}</div>
                        <div class="pf-msg" id="pfMsg">{{ $ayarlar->bakim_mesaj ?? 'Sevgili ziyaretçilerimiz, sitemiz şu anda bakımdadır.' }}</div>
                    </div>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="alert-circle" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Bakım moduna alma anında etkili olur
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Bakım Ayarlarını Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection