@extends('admin._layout')

@section('title', 'Sosyal Medya')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .social-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-bottom: 10px;
        transition: all 0.15s;
    }
    .social-item:hover { border-color: var(--brand-medium); }

    .social-icon {
        width: 44px; height: 44px;
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: #fff;
    }
    .social-info { flex: 1; min-width: 0; }
    .social-name { font-weight: 600; font-size: 14px; }
    .social-domain { font-size: 11.5px; color: var(--text-muted); }

    .social-input {
        flex: 2;
        min-width: 0;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Sosyal Medya</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="share-2"></i>
            Sosyal Medya Linkleri
        </h1>
        <div class="page-subtitle">Sosyal medya hesaplarınızın bağlantılarını yönetin</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'sosyal'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.sosyal.post') }}" method="POST">
            @csrf

            <div class="section">
                <div class="section-title">
                    <i data-lucide="link"></i>
                    <span>Hesap Bağlantıları</span>
                </div>

                <div class="info-card">
                    <div class="ic"><i data-lucide="info"></i></div>
                    <div class="body">
                        URL'leri tam olarak girin (örn: <code>https://facebook.com/firma</code>). Boş bırakılanlar sitede gösterilmez.
                    </div>
                </div>

                @php
                    $socials = [
                        ['key' => 'facebook',  'name' => 'Facebook',  'icon' => 'facebook',  'color' => '#1877F2', 'placeholder' => 'https://facebook.com/...'],
                        ['key' => 'twitter',   'name' => 'Twitter/X', 'icon' => 'twitter',   'color' => '#000000', 'placeholder' => 'https://twitter.com/...'],
                        ['key' => 'instagram', 'name' => 'Instagram', 'icon' => 'instagram', 'color' => '#E4405F', 'placeholder' => 'https://instagram.com/...'],
                        ['key' => 'linkedin',  'name' => 'LinkedIn',  'icon' => 'linkedin',  'color' => '#0A66C2', 'placeholder' => 'https://linkedin.com/...'],
                        ['key' => 'youtube',   'name' => 'YouTube',   'icon' => 'youtube',   'color' => '#FF0000', 'placeholder' => 'https://youtube.com/...'],
                        ['key' => 'tiktok',    'name' => 'TikTok',    'icon' => 'music-2',   'color' => '#000000', 'placeholder' => 'https://tiktok.com/@...'],
                        ['key' => 'pinterest', 'name' => 'Pinterest', 'icon' => 'image',     'color' => '#E60023', 'placeholder' => 'https://pinterest.com/...'],
                    ];
                @endphp

                @foreach($socials as $s)
                    @php $value = old($s['key'], $ayarlar->{$s['key']} ?? ''); @endphp
                    <div class="social-item">
                        <div class="social-icon" style="background:{{ $s['color'] }}">
                            <i data-lucide="{{ $s['icon'] }}"></i>
                        </div>
                        <div class="social-info">
                            <div class="social-name">{{ $s['name'] }}</div>
                            <div class="social-domain">{{ parse_url($value, PHP_URL_HOST) ?: 'Bağlantı eklenmemiş' }}</div>
                        </div>
                        <div class="social-input">
                            <input type="url" name="{{ $s['key'] }}"
                                   value="{{ $value }}"
                                   class="form-input"
                                   placeholder="{{ $s['placeholder'] }}">
                        </div>
                        @if($value)
                            <a href="{{ $value }}" target="_blank" class="table-action" title="Aç">
                                <i data-lucide="external-link"></i>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Footer ve iletişim alanında görünür
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Bağlantıları Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection