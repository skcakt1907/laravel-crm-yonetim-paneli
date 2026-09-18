@extends('admin._layout')

@section('title', 'Arkaplan Ayarları')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .sayfa-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 16px;
        padding-bottom: 1px;
        overflow-x: auto;
    }
    .sayfa-tab {
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: -1px;
        white-space: nowrap;
    }
    .sayfa-tab:hover { color: var(--text); }
    .sayfa-tab.active {
        color: var(--brand-dark);
        border-bottom-color: var(--brand);
    }
    body.theme-dark .sayfa-tab.active { color: var(--brand); }

    .sayfa-pane { display: none; }
    .sayfa-pane.active { display: block; }

    .turu-tabs {
        display: flex;
        gap: 4px;
        background: var(--bg-subtle);
        padding: 4px;
        border-radius: var(--radius-md);
        margin-bottom: 14px;
    }
    .turu-tab {
        flex: 1;
        padding: 8px 12px;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        border-radius: calc(var(--radius-md) - 4px);
        transition: all 0.15s;
        color: var(--text-muted);
    }
    .turu-tab.active {
        background: var(--surface);
        color: var(--brand-dark);
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    body.theme-dark .turu-tab.active { color: var(--brand); }

    .color-input {
        height: 50px !important;
        cursor: pointer;
        padding: 4px !important;
    }

    .preview-bg {
        margin-top: 12px;
        height: 80px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
        background-size: cover;
        background-position: center;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Arkaplan</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="image"></i>
            Sayfa Arkaplanları
        </h1>
        <div class="page-subtitle">Her sayfa için ayrı arkaplan (resim, düz renk veya gradient)</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'arkaplan'])

    <div class="ayarlar-content">
        <div class="info-card">
            <div class="ic"><i data-lucide="info"></i></div>
            <div class="body">
                Her sayfa için ayrı arkaplan ayarlayabilirsiniz. Resim önerilen boyut: <strong>1920x500px</strong> veya üzeri.
            </div>
        </div>

        @php
            $sayfalar = [
                ['key' => 'anasayfa',    'label' => 'Anasayfa',    'icon' => 'home'],
                ['key' => 'paketler',    'label' => 'Paketler',    'icon' => 'package'],
                ['key' => 'hosting',     'label' => 'Hosting',     'icon' => 'server'],
                ['key' => 'blog',        'label' => 'Blog',        'icon' => 'newspaper'],
                ['key' => 'iletisim',    'label' => 'İletişim',    'icon' => 'phone'],
                ['key' => 'hizmet',      'label' => 'Hizmetler',   'icon' => 'briefcase'],
                ['key' => 'domain',      'label' => 'Alan Adı',    'icon' => 'globe'],
                ['key' => 'sayfa',       'label' => 'Sayfalar',    'icon' => 'file-text'],
                ['key' => 'referanslar', 'label' => 'Referanslar', 'icon' => 'award'],
                ['key' => 'firsatlar',   'label' => 'Fırsatlar',   'icon' => 'flame'],
            ];
        @endphp

        <form action="{{ route('admin.ayarlar.arkaplan.post') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="section">
                <div class="section-title">
                    <i data-lucide="layout"></i>
                    <span>Sayfa Seçin</span>
                </div>

                <div class="sayfa-tabs" id="sayfaTabs">
                    @foreach($sayfalar as $i => $sf)
                        <button type="button" class="sayfa-tab {{ $i === 0 ? 'active' : '' }}" data-sayfa="{{ $sf['key'] }}">
                            <i data-lucide="{{ $sf['icon'] }}" style="width:14px;height:14px"></i>
                            <span>{{ $sf['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach($sayfalar as $i => $sf)
                    @php
                        $sayfaAdi = $sf['key'];
                        $turu = $ayarlar->{"{$sayfaAdi}_arkaplan_turu"} ?? 'resim';
                        $renk = $ayarlar->{"{$sayfaAdi}_arkaplan_renk"} ?? '#141e30';
                        $grBas = $ayarlar->{"{$sayfaAdi}_gradient_baslangic"} ?? '#141e30';
                        $grBit = $ayarlar->{"{$sayfaAdi}_gradient_bitis"} ?? '#243b55';

                        // Klasör ve resim yolu
                        $klasorMap = [
                            'anasayfa' => 'anasayfa', 'paketler' => 'paketler', 'hosting' => 'hosting',
                            'blog' => 'blog', 'iletisim' => 'iletisim', 'hizmet' => 'hizmetler',
                            'domain' => 'alanadi', 'sayfa' => 'sayfalar', 'referanslar' => 'referanslar',
                            'firsatlar' => 'firsatlar',
                        ];
                        $klasor = $klasorMap[$sayfaAdi] ?? $sayfaAdi;

                        // arka_plan tablosundaki resim
                        try {
                            $arkaplanRow = \Illuminate\Support\Facades\DB::table('arka_plan')->where('id', 1)->first();
                            $resimKolMap = [
                                'anasayfa' => 'anasayfa', 'paketler' => 'paketler', 'hosting' => 'hosting',
                                'blog' => 'blog', 'iletisim' => 'iletisim', 'hizmet' => 'hizmetler',
                                'domain' => 'alanadi', 'sayfa' => 'sayfalar', 'referanslar' => 'referanslar',
                                'firsatlar' => 'firsatlar',
                            ];
                            $resimKol = $resimKolMap[$sayfaAdi] ?? $sayfaAdi;
                            $mevcutResim = $arkaplanRow ? ($arkaplanRow->{$resimKol} ?? null) : null;
                        } catch (\Throwable $e) {
                            $mevcutResim = null;
                        }
                    @endphp

                    <div class="sayfa-pane {{ $i === 0 ? 'active' : '' }}" data-pane="{{ $sayfaAdi }}">
                        <div style="background:var(--bg-subtle);padding:14px;border-radius:var(--radius-md);margin-bottom:12px">
                            <div style="display:flex;align-items:center;gap:10px;font-weight:600;font-size:14.5px">
                                <i data-lucide="{{ $sf['icon'] }}" style="width:18px;height:18px;color:var(--brand-dark)"></i>
                                <span>{{ $sf['label'] }} Sayfası Arkaplanı</span>
                            </div>
                        </div>

                        {{-- Türü Seçimi --}}
                        <div class="turu-tabs" data-sayfa="{{ $sayfaAdi }}">
                            <label class="turu-tab {{ $turu === 'resim' ? 'active' : '' }}" data-turu="resim">
                                <input type="radio" name="{{ $sayfaAdi }}_arkaplan_turu" value="resim"
                                       {{ $turu === 'resim' ? 'checked' : '' }} style="display:none">
                                <i data-lucide="image" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                                Resim
                            </label>
                            <label class="turu-tab {{ $turu === 'renk' ? 'active' : '' }}" data-turu="renk">
                                <input type="radio" name="{{ $sayfaAdi }}_arkaplan_turu" value="renk"
                                       {{ $turu === 'renk' ? 'checked' : '' }} style="display:none">
                                <i data-lucide="paintbrush" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                                Düz Renk
                            </label>
                            <label class="turu-tab {{ $turu === 'gradient' ? 'active' : '' }}" data-turu="gradient">
                                <input type="radio" name="{{ $sayfaAdi }}_arkaplan_turu" value="gradient"
                                       {{ $turu === 'gradient' ? 'checked' : '' }} style="display:none">
                                <i data-lucide="droplet" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                                Gradient
                            </label>
                        </div>

                        {{-- RESİM --}}
                        <div class="turu-content turu-resim" data-show-for="resim" style="display:{{ $turu === 'resim' ? 'block' : 'none' }}">
                            @if($mevcutResim)
                                <div style="margin-bottom:10px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);border:1px solid var(--border)">
                                    <img src="{{ asset('tema/uploads/arkaplan/' . $klasor . '/' . $mevcutResim) }}"
                                         alt="Mevcut"
                                         style="max-width:100%;max-height:160px;border-radius:6px;display:block;margin:0 auto"
                                         onerror="this.style.display='none'">
                                    <div style="font-size:11.5px;color:var(--text-muted);text-align:center;margin-top:6px;font-family:monospace">
                                        {{ $mevcutResim }}
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="form-label">Yeni Arkaplan Resmi</label>
                                <input type="file" name="{{ $sayfaAdi }}_arkaplan_resim"
                                       accept="image/*" class="form-input">
                                <small class="form-help">Önerilen: 1920x500px veya üzeri</small>
                            </div>
                        </div>

                        {{-- DÜZ RENK --}}
                        <div class="turu-content turu-renk" data-show-for="renk" style="display:{{ $turu === 'renk' ? 'block' : 'none' }}">
                            <div class="form-group">
                                <label class="form-label">Arkaplan Rengi</label>
                                <input type="color" name="{{ $sayfaAdi }}_arkaplan_renk"
                                       value="{{ $renk }}" class="form-input color-input"
                                       oninput="document.getElementById('prev-{{ $sayfaAdi }}-renk').style.background=this.value">
                                <div class="preview-bg" id="prev-{{ $sayfaAdi }}-renk" style="background:{{ $renk }}"></div>
                            </div>
                        </div>

                        {{-- GRADIENT --}}
                        <div class="turu-content turu-gradient" data-show-for="gradient" style="display:{{ $turu === 'gradient' ? 'block' : 'none' }}">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">Başlangıç Rengi</label>
                                    <input type="color" name="{{ $sayfaAdi }}_gradient_baslangic"
                                           value="{{ $grBas }}" class="form-input color-input"
                                           oninput="updateGradient('{{ $sayfaAdi }}')">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Bitiş Rengi</label>
                                    <input type="color" name="{{ $sayfaAdi }}_gradient_bitis"
                                           value="{{ $grBit }}" class="form-input color-input"
                                           oninput="updateGradient('{{ $sayfaAdi }}')">
                                </div>
                            </div>
                            <div class="preview-bg" id="prev-{{ $sayfaAdi }}-gradient"
                                 style="background:linear-gradient(135deg, {{ $grBas }}, {{ $grBit }})"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Tüm sayfaların ayarları birlikte kaydedilir
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Arkaplanları Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Sayfa tabları
document.querySelectorAll('#sayfaTabs .sayfa-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const sayfa = this.dataset.sayfa;
        document.querySelectorAll('#sayfaTabs .sayfa-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.sayfa-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.querySelector('[data-pane="' + sayfa + '"]').classList.add('active');
    });
});

// Türü değiştirme
document.querySelectorAll('.turu-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        const turu = this.dataset.turu;
        const parent = this.parentElement;
        const sayfa = parent.dataset.sayfa;
        const pane = parent.parentElement; // sayfa-pane

        parent.querySelectorAll('.turu-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        // İçerikleri gizle/göster
        pane.querySelectorAll('.turu-content').forEach(c => c.style.display = 'none');
        pane.querySelector('.turu-' + turu).style.display = 'block';
    });
});

function updateGradient(sayfa) {
    const bas = document.querySelector('[name="' + sayfa + '_gradient_baslangic"]').value;
    const bit = document.querySelector('[name="' + sayfa + '_gradient_bitis"]').value;
    const prev = document.getElementById('prev-' + sayfa + '-gradient');
    if (prev) prev.style.background = 'linear-gradient(135deg, ' + bas + ', ' + bit + ')';
}
</script>

@endsection