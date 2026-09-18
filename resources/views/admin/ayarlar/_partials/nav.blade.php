{{--
═══════════════════════════════════════════════════════════
AYARLAR SUB-NAV — Tüm ayarlar sayfalarının sol sidebar'ı
Kullanım: @include('admin.ayarlar._partials.nav', ['active' => 'genel'])
═══════════════════════════════════════════════════════════
--}}

@php
    $active = $active ?? 'genel';

    $ayarlarMenu = [
        [
            'title' => 'SİTE',
            'items' => [
                ['key' => 'genel',         'route' => 'admin.ayarlar.index',       'label' => 'Genel Ayarlar',  'icon' => 'settings'],
                ['key' => 'iletisim',      'route' => 'admin.ayarlar.iletisim',    'label' => 'İletişim',       'icon' => 'phone'],
                ['key' => 'sosyal',        'route' => 'admin.ayarlar.sosyal',      'label' => 'Sosyal Medya',   'icon' => 'share-2'],
                ['key' => 'arkaplan',      'route' => 'admin.ayarlar.arkaplan',    'label' => 'Arka Plan',      'icon' => 'image'],
            ],
        ],
        [
            'title' => 'SİSTEM',
            'items' => [
                ['key' => 'modul',         'route' => 'admin.ayarlar.modul',       'label' => 'Modüller',       'icon' => 'puzzle'],
                ['key' => 'limit',         'route' => 'admin.ayarlar.limit',       'label' => 'Limitler',       'icon' => 'gauge'],
                ['key' => 'api',           'route' => 'admin.ayarlar.api',         'label' => 'API Entegrasyon','icon' => 'key'],
                ['key' => 'bakim',         'route' => 'admin.ayarlar.bakim',       'label' => 'Bakım Modu',     'icon' => 'wrench'],
                ['key' => 'sayfa-bakim',   'route' => 'admin.ayarlar.sayfa-bakim', 'label' => 'Sayfa Bakım',    'icon' => 'file-warning'],
            ],
        ],
        [
            'title' => 'İLETİŞİM',
            'items' => [
                ['key' => 'mail',          'route' => 'admin.ayarlar.mail',        'label' => 'SMTP / Mail',    'icon' => 'mail'],
                ['key' => 'sms',           'route' => 'admin.ayarlar.sms',         'label' => 'SMS Ayarları',   'icon' => 'message-square'],
            ],
        ],
        [
            'title' => 'TİCARİ',
            'items' => [
                ['key' => 'sanal',         'route' => 'admin.ayarlar.sanal',       'label' => 'Sanal POS',      'icon' => 'credit-card'],
                ['key' => 'fatura',        'route' => 'admin.ayarlar.fatura',      'label' => 'Fatura',         'icon' => 'receipt'],
            ],
        ],
    ];

    // Aktif öğenin etiketini bul (telefon accordion başlığı için)
    $aktifLabel = 'Menü';
    foreach ($ayarlarMenu as $g) {
        foreach ($g['items'] as $it) {
            if ($it['key'] === $active) { $aktifLabel = $it['label']; break 2; }
        }
    }
@endphp

<style>
    .ayarlar-layout {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 20px;
        align-items: start;
    }
    /* Telefon accordion toggle butonu — masaüstünde gizli */
    .ayarlar-nav-toggle { display: none; }

    @media (max-width: 900px) {
        .ayarlar-layout { grid-template-columns: 1fr; }

        /* Accordion toggle butonu görünür */
        .ayarlar-nav-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 12px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            margin-bottom: 12px;
        }
        .ayarlar-nav-toggle .nt-left { display: flex; align-items: center; gap: 8px; }
        .ayarlar-nav-toggle .nt-left small { color: var(--text-muted); font-weight: 500; }
        .ayarlar-nav-toggle .nt-chevron { transition: transform 0.2s; flex-shrink: 0; }
        .ayarlar-nav-toggle.open .nt-chevron { transform: rotate(180deg); }

        /* Subnav telefonda: varsayılan KAPALI, sticky değil */
        .ayarlar-subnav {
            position: static;
            top: auto;
            max-height: none;
            overflow: visible;
            display: none;
            margin-bottom: 16px;
        }
        .ayarlar-subnav.open { display: block; }

        /* Menü açıkken formu gizle: ya menü ya form (üst üste binmesin) */
        body.ayarlar-nav-acik .ayarlar-content { display: none; }
    }

    @media (max-width: 900px) {
        /* Kaydet barı telefonda havada kalmasın: sticky değil, normal aksın */
        .sticky-save {
            position: static;
            bottom: auto;
            box-shadow: none;
        }
    }

    @media (max-width: 560px) {
        .ayarlar-content .form-grid { grid-template-columns: 1fr !important; }
        .sticky-save { flex-direction: column; align-items: stretch; gap: 8px; }
        .sticky-save .btn { width: 100%; justify-content: center; }
    }

    .ayarlar-subnav {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 12px;
        position: sticky;
        top: 84px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }

    .ayarlar-group {
        margin-bottom: 14px;
    }
    .ayarlar-group:last-child { margin-bottom: 0; }

    .ayarlar-group-title {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        padding: 6px 12px;
        margin-bottom: 2px;
    }

    .ayarlar-sub-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: var(--radius-md);
        font-size: 13.5px;
        font-weight: 500;
        color: var(--text-secondary);
        text-decoration: none;
        transition: all 0.15s;
        margin-bottom: 2px;
    }
    .ayarlar-sub-link svg {
        width: 16px; height: 16px;
        flex-shrink: 0;
    }
    .ayarlar-sub-link:hover {
        background: var(--bg-subtle);
        color: var(--text);
    }
    .ayarlar-sub-link.active {
        background: linear-gradient(135deg, var(--brand-soft), rgba(184,182,46,0.04));
        color: var(--brand-dark);
        border-left: 3px solid var(--brand);
        padding-left: 9px;
        font-weight: 600;
    }
    body.theme-dark .ayarlar-sub-link.active { color: var(--brand); }

    .ayarlar-content {
        min-width: 0;
    }
</style>

<button type="button" class="ayarlar-nav-toggle" id="ayarlarNavToggle" onclick="toggleAyarlarNav()">
    <span class="nt-left">
        <i data-lucide="menu"></i>
        <span>Bölüm: <strong>{{ $aktifLabel }}</strong></span>
    </span>
    <i data-lucide="chevron-down" class="nt-chevron"></i>
</button>

<aside class="ayarlar-subnav" id="ayarlarSubnav">
    @foreach($ayarlarMenu as $group)
        <div class="ayarlar-group">
            <div class="ayarlar-group-title">{{ $group['title'] }}</div>
            @foreach($group['items'] as $item)
                @if(Route::has($item['route']))
                    <a href="{{ route($item['route']) }}"
                       class="ayarlar-sub-link {{ $active === $item['key'] ? 'active' : '' }}">
                        <i data-lucide="{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    @endforeach
</aside>

<script>
function toggleAyarlarNav() {
    var sub = document.getElementById('ayarlarSubnav');
    var btn = document.getElementById('ayarlarNavToggle');
    var acik = sub?.classList.toggle('open');
    btn?.classList.toggle('open');
    document.body.classList.toggle('ayarlar-nav-acik', !!acik);
    if (window.lucide) window.lucide.createIcons();
}

// Menüden bir bölüm seçilince (link tıklanınca) menü zaten yeni sayfaya gider;
// ama aynı sayfadaysa formu geri göstermek için body class temizle
document.querySelectorAll('.ayarlar-sub-link').forEach(function(a) {
    a.addEventListener('click', function() {
        document.body.classList.remove('ayarlar-nav-acik');
    });
});
</script>