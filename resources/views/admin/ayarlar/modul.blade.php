@extends('admin._layout')

@section('title', 'Modül Yönetimi')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .modul-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
    }
    .modul-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
    }
    .modul-card:hover { border-color: var(--brand-medium); }
    .modul-card.aktif {
        background: linear-gradient(135deg, var(--brand-soft), transparent);
        border-color: var(--brand);
    }
    .modul-icon {
        width: 44px; height: 44px;
        border-radius: var(--radius-md);
        background: var(--bg-subtle);
        display: flex; align-items: center; justify-content: center;
        color: var(--brand-dark);
        flex-shrink: 0;
    }
    body.theme-dark .modul-icon { color: var(--brand); }
    .modul-info { flex: 1; min-width: 0; }
    .modul-name { font-weight: 600; font-size: 14px; }
    .modul-desc { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Modüller</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="puzzle"></i>
            Modül Yönetimi
        </h1>
        <div class="page-subtitle">Site modüllerini aktifleştirin veya pasifleştirin</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'modul'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.modul.post') }}" method="POST">
            @csrf

            <div class="info-card">
                <div class="ic"><i data-lucide="info"></i></div>
                <div class="body">
                    Pasifleştirilen modüller sitede görünmez ve ilgili sayfalar erişilemez hale gelir.
                </div>
            </div>

            @php
                $moduller = [
                    ['key' => 'blog_aktif',      'name' => 'Blog',           'icon' => 'newspaper',     'desc' => 'Yazı ve makale yayını'],
                    ['key' => 'referans_aktif', 'name' => 'Referanslar',    'icon' => 'award',         'desc' => 'Müşteri referansları'],
                    ['key' => 'iletisim_aktif', 'name' => 'İletişim',       'icon' => 'mail',          'desc' => 'İletişim formu ve sayfası'],
                    ['key' => 'ebulten_aktif',  'name' => 'E-Bülten',       'icon' => 'send',          'desc' => 'Bülten aboneliği'],
                    ['key' => 'kampanya_aktif', 'name' => 'Kampanyalar',    'icon' => 'megaphone',     'desc' => 'Promosyon ve indirimler'],
                ];
            @endphp

            <div class="section">
                <div class="section-title">
                    <i data-lucide="layers"></i>
                    <span>Site Modülleri</span>
                </div>

                <div class="modul-grid">
                    @foreach($moduller as $m)
                        @php $aktif = (int)($ayarlar->{$m['key']} ?? 1) === 1; @endphp
                        <label class="modul-card {{ $aktif ? 'aktif' : '' }}" id="card-{{ $m['key'] }}">
                            <div class="modul-icon">
                                <i data-lucide="{{ $m['icon'] }}" style="width:20px;height:20px"></i>
                            </div>
                            <div class="modul-info">
                                <div class="modul-name">{{ $m['name'] }}</div>
                                <div class="modul-desc">{{ $m['desc'] }}</div>
                            </div>
                            <label class="ios-toggle">
                                <input type="hidden" name="{{ $m['key'] }}" value="0">
                                <input type="checkbox" name="{{ $m['key'] }}" value="1"
                                       {{ $aktif ? 'checked' : '' }}
                                       onchange="document.getElementById('card-{{ $m['key'] }}').classList.toggle('aktif', this.checked)">
                                <span class="knob"></span>
                            </label>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Değişiklikler hemen aktif olur
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Modülleri Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection