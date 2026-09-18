@extends('admin._layout')

@section('title', 'Toplu Mail Gönder')

@push('head')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .hedef-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }
    .hedef-tab {
        position: relative;
        padding: 14px 16px;
        border: 2px solid var(--border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all .15s ease;
        background: var(--card-bg);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .hedef-tab input[type=radio] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .hedef-tab:hover { border-color: var(--brand); }
    .hedef-tab.active {
        border-color: var(--brand);
        background: var(--brand-soft);
    }
    .hedef-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .hedef-tab.active .hedef-icon {
        background: var(--brand);
        color: #fff;
    }
    .hedef-name {
        font-weight: 600;
        font-size: 14px;
    }
    .hedef-count {
        font-size: 12px;
        color: var(--text-muted);
    }
</style>
@endpush

@section('content')

@php
    $hedef = old('hedef', 'aboneler');
    $aboneSayisi = $aboneler->count();
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ebulten.index') }}">E-Bülten</a>
    <span class="sep">/</span>
    <span class="current">Toplu Mail</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="send"></i>
            Toplu Mail Gönder
        </h1>
        <div class="page-subtitle">Abonelere, üyelere veya bayilere HTML mail gönder</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.ebulten.index') }}" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{!! session('success') !!}</div>@endif

<form action="{{ route('admin.ebulten.toplu-mail.gonder') }}" method="POST"
      onsubmit="return confirm('Toplu mail gönderilecek. Onaylıyor musun?')">
    @csrf

    {{-- HEDEF GRUBU --}}
    <div class="section" style="margin-bottom:16px">
        <div class="section-title">
            <i data-lucide="target"></i>
            <span>Alıcı Grubu</span>
        </div>

        <div class="hedef-grid">
            <label class="hedef-tab {{ $hedef === 'aboneler' ? 'active' : '' }}" data-hedef="aboneler">
                <input type="radio" name="hedef" value="aboneler" {{ $hedef === 'aboneler' ? 'checked' : '' }} required>
                <div class="hedef-icon"><i data-lucide="mailbox"></i></div>
                <div>
                    <div class="hedef-name">Bülten Aboneleri</div>
                    <div class="hedef-count">{{ $aboneSayisi }} aktif abone</div>
                </div>
            </label>

            <label class="hedef-tab {{ $hedef === 'uyeler' ? 'active' : '' }}" data-hedef="uyeler">
                <input type="radio" name="hedef" value="uyeler" {{ $hedef === 'uyeler' ? 'checked' : '' }}>
                <div class="hedef-icon"><i data-lucide="users"></i></div>
                <div>
                    <div class="hedef-name">Tüm Üyeler</div>
                    <div class="hedef-count">{{ $uyeSayisi ?? 0 }} üye</div>
                </div>
            </label>

            <label class="hedef-tab {{ $hedef === 'bayiler' ? 'active' : '' }}" data-hedef="bayiler">
                <input type="radio" name="hedef" value="bayiler" {{ $hedef === 'bayiler' ? 'checked' : '' }}>
                <div class="hedef-icon"><i data-lucide="handshake"></i></div>
                <div>
                    <div class="hedef-name">Bayiler</div>
                    <div class="hedef-count">{{ $bayiSayisi ?? 0 }} aktif bayi</div>
                </div>
            </label>

            <label class="hedef-tab {{ $hedef === 'hepsi' ? 'active' : '' }}" data-hedef="hepsi">
                <input type="radio" name="hedef" value="hepsi" {{ $hedef === 'hepsi' ? 'checked' : '' }}>
                <div class="hedef-icon"><i data-lucide="globe"></i></div>
                <div>
                    <div class="hedef-name">Hepsi</div>
                    <div class="hedef-count">Aboneler + Üyeler + Bayiler</div>
                </div>
            </label>
        </div>
    </div>

    {{-- MAIL İÇERİĞİ --}}
    <div class="form-grid">
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="mail"></i>
                    <span>Mail İçeriği</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Konu <span class="required">*</span></label>
                    <input type="text" name="konu" value="{{ old('konu') }}" required
                           class="form-input" maxlength="255"
                           placeholder="Örn: Yaz Kampanyası Başladı!">
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">İçerik <span class="required">*</span></label>
                    <textarea name="icerik" id="icerikEditor" rows="14" required
                              class="form-textarea">{{ old('icerik') }}</textarea>
                    <small class="form-help">HTML destekli. Test için önce kendi adresine gönder.</small>
                </div>
            </div>
        </div>

        <div>
            <div class="section" style="background:rgba(245,158,11,.05);border-color:rgba(245,158,11,.2);margin-bottom:16px">
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <i data-lucide="alert-triangle" style="color:#f59e0b;flex-shrink:0;margin-top:2px"></i>
                    <div style="font-size:13px;line-height:1.6">
                        <strong>Dikkat:</strong>
                        <ul style="margin:6px 0 0 18px;padding:0">
                            <li>Toplu mail gönderimi <strong>geri alınamaz</strong>.</li>
                            <li>SMTP ayarları
                                <a href="{{ route('admin.ayarlar.mail') }}" style="color:var(--brand-dark);font-weight:600">
                                    Ayarlar → Mail
                                </a>'da yapılandırılmalı.</li>
                            <li>Yoğun gönderimde sunucu kuyruğa alınabilir.</li>
                            <li>Spam'a düşmemek için kısa, net içerik öner.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="link"></i>
                    <span>İlgili Sayfalar</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="{{ route('admin.mail-templates.index') }}"
                       class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                        <i data-lucide="file-text"></i>
                        <span>Mail Şablonları</span>
                    </a>
                    <a href="{{ route('admin.ayarlar.mail') }}"
                       class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                        <i data-lucide="settings"></i>
                        <span>SMTP Ayarları</span>
                    </a>
                    <a href="{{ route('admin.ebulten.index') }}"
                       class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                        <i data-lucide="mailbox"></i>
                        <span>Abone Listesi</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.ebulten.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="send"></i>
            <span>Mail Gönder</span>
        </button>
    </div>
</form>

<script>
// Hedef tab toggle
document.querySelectorAll('.hedef-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        document.querySelectorAll('.hedef-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        var radio = this.querySelector('input[type=radio]');
        if(radio) radio.checked = true;
    });
});

// TinyMCE
if (typeof tinymce !== 'undefined') {
    tinymce.init({
        selector: '#icerikEditor',
        height: 380,
        menubar: false,
        plugins: 'lists link image table code preview',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link image table | code preview',
        branding: false,
        promotion: false,
        skin: (document.body.classList.contains('theme-dark') ? 'oxide-dark' : 'oxide'),
        content_css: (document.body.classList.contains('theme-dark') ? 'dark' : 'default')
    });
}
</script>

@endsection