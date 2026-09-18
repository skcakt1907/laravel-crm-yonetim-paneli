@extends('admin._layout')

@section('title', 'Navbar Menüleri')

@push('head')
<style>
    .menu-tabs {
        display: flex; gap: 4px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 20px;
    }
    .menu-tab {
        padding: 10px 16px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-muted);
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: -1px;
        transition: all 0.2s;
    }
    .menu-tab:hover { color: var(--text); }
    .menu-tab.active {
        color: var(--brand-dark);
        border-bottom-color: var(--brand);
    }
    body.theme-dark .menu-tab.active { color: var(--brand); }

    /* Menü Liste */
    .menu-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .menu-item {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        transition: all 0.15s;
    }
    .menu-item.dragging { opacity: 0.5; }
    .menu-item.drag-over { border-color: var(--brand); background: var(--brand-soft); }

    .menu-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
    }

    .drag-handle {
        cursor: grab;
        color: var(--text-muted);
        flex-shrink: 0;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.15s;
    }
    .drag-handle:hover {
        background: var(--bg-subtle);
        color: var(--brand-dark);
    }
    .drag-handle:active { cursor: grabbing; }

    .menu-toggle {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px 6px;
        color: var(--text-muted);
        border-radius: 4px;
        transition: all 0.15s;
        flex-shrink: 0;
    }
    .menu-toggle:hover { background: var(--bg-subtle); color: var(--text); }
    .menu-toggle .chevron {
        transition: transform 0.2s;
        display: block;
    }
    .menu-item.expanded .menu-toggle .chevron {
        transform: rotate(90deg);
    }

    .menu-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .menu-title {
        font-weight: 600;
        font-size: 14px;
        color: var(--text);
    }
    .menu-link {
        font-size: 11.5px;
        color: var(--text-muted);
        font-family: monospace;
        word-break: break-all;
    }

    .menu-badge-count {
        background: var(--brand-soft);
        color: var(--brand-dark);
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
    }
    body.theme-dark .menu-badge-count { color: var(--brand); }

    .menu-actions {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }

    /* Alt menü panel */
    .alt-menu-panel {
        display: none;
        padding: 14px 14px 14px 50px;
        background: var(--bg-subtle);
        border-top: 1px solid var(--border);
    }
    .menu-item.expanded .alt-menu-panel { display: block; }

    .alt-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 6px;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .alt-menu-item .alt-info {
        flex: 1;
        min-width: 0;
    }
    .alt-menu-item .alt-link {
        font-size: 11px;
        color: var(--text-muted);
        font-family: monospace;
    }

    .alt-menu-add-form {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 6px;
        margin-top: 10px;
        padding: 10px;
        background: var(--surface);
        border: 1px dashed var(--brand-medium);
        border-radius: 6px;
    }
    .alt-menu-add-form input {
        padding: 6px 10px;
        font-size: 12.5px;
    }

    /* Inline ekleme form */
    .add-menu-form {
        background: linear-gradient(135deg, var(--brand-soft), transparent);
        border: 1px dashed var(--brand-medium);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 16px;
    }

    .form-grid-inline {
        display: grid;
        grid-template-columns: 1.5fr 2fr 0.5fr auto auto;
        gap: 8px;
        align-items: end;
    }
    @media (max-width: 768px) {
        .form-grid-inline { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span>Menüler</span>
    <span class="sep">/</span>
    <span class="current">Navbar (Header)</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="menu"></i>
            Navbar Menüleri
            <span class="badge badge-brand">{{ count($menuler) }}</span>
        </h1>
        <div class="page-subtitle">Site üst menüsünü düzenleyin, sıralamayı sürükleyip bırakın</div>
    </div>
</div>

{{-- ALT TAB MENU --}}
<div class="menu-tabs">
    <a href="{{ route('admin.menuler.header') }}" class="menu-tab active">
        <i data-lucide="menu" style="width:15px;height:15px"></i>
        <span>Navbar (Header)</span>
    </a>
    @if(Route::has('admin.menuler.footer'))
        <a href="{{ route('admin.menuler.footer') }}" class="menu-tab">
            <i data-lucide="layout-bottom" style="width:15px;height:15px"></i>
            <span>Footer</span>
        </a>
    @endif
    @if(Route::has('admin.menuler.navbar-ayarlari'))
        <a href="{{ route('admin.menuler.navbar-ayarlari') }}" class="menu-tab">
            <i data-lucide="settings" style="width:15px;height:15px"></i>
            <span>Navbar Üst Bar Ayarları</span>
        </a>
    @endif
</div>

{{-- YENİ MENÜ EKLEME FORMU --}}
<div class="add-menu-form">
    <div style="font-weight:600;font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;color:var(--brand-dark)">
        <i data-lucide="plus-circle" style="width:18px;height:18px"></i>
        <span>Yeni Menü Ekle</span>
    </div>
    <form action="{{ route('admin.menuler.header.ekle') }}" method="POST">
        @csrf
        <div class="form-grid-inline">
            <div>
                <label class="form-label" style="font-size:11.5px">Menü Adı <span class="required">*</span></label>
                <input type="text" name="menu_isim" value="{{ old('menu_isim') }}"
                       required class="form-input" placeholder="Örn: Hakkımızda">
            </div>
            <div>
                <label class="form-label" style="font-size:11.5px">Link / URL <span class="required">*</span></label>
                <input type="text" name="link" value="{{ old('link') }}"
                       required class="form-input" placeholder="/hakkimizda veya https://...">
            </div>
            <div>
                <label class="form-label" style="font-size:11.5px">Sıra</label>
                <input type="number" name="sira" min="0" value="0" class="form-input">
            </div>
            <label class="form-label" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;padding:8px 0;cursor:pointer;white-space:nowrap">
                <input type="checkbox" name="sekme" value="1" style="margin:0">
                Yeni sekme
            </label>
            <button type="submit" class="btn btn-primary" style="height:38px">
                <i data-lucide="plus"></i>
                <span>Ekle</span>
            </button>
        </div>
    </form>
</div>

{{-- MENÜ LİSTESİ --}}
@if(count($menuler) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="menu" class="empty-state-icon"></i>
            <h4>Henüz menü yok</h4>
            <p>Yukarıdaki formdan ilk menünüzü ekleyin.</p>
        </div>
    </div>
@else
    <div class="menu-list" id="menuList">
        @foreach($menuler as $menu)
            @php
                $altCount = isset($menu->altmenu) ? count($menu->altmenu) : 0;
                $aktif = (int)($menu->durum ?? 1) === 1;
                $yeniSekme = (int)($menu->sekme ?? 0) === 1;
            @endphp
            <div class="menu-item" data-id="{{ $menu->id }}" draggable="true">
                <div class="menu-row">
                    <span class="drag-handle" title="Sürükle">
                        <i data-lucide="grip-vertical" style="width:18px;height:18px"></i>
                    </span>

                    @if($altCount > 0 || true)
                        <button type="button" class="menu-toggle" onclick="toggleAltMenu(this)" title="Alt menüleri göster">
                            <i data-lucide="chevron-right" class="chevron" style="width:16px;height:16px"></i>
                        </button>
                    @endif

                    <div class="menu-info">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="menu-title">{{ $menu->menu_isim ?? $menu->adi ?? '—' }}</span>
                            @if($altCount > 0)
                                <span class="menu-badge-count">{{ $altCount }} alt</span>
                            @endif
                            @if($yeniSekme)
                                <span style="color:var(--brand-dark)" title="Yeni sekmede açılır">
                                    <i data-lucide="external-link" style="width:13px;height:13px"></i>
                                </span>
                            @endif
                        </div>
                        <span class="menu-link">{{ $menu->link ?? '—' }}</span>
                    </div>

                    <div>
                        @if($aktif)
                            <span class="badge badge-success">
                                <i data-lucide="check" style="width:11px;height:11px"></i>
                                Aktif
                            </span>
                        @else
                            <span class="badge badge-neutral">
                                <i data-lucide="pause" style="width:11px;height:11px"></i>
                                Pasif
                            </span>
                        @endif
                    </div>

                    <div class="menu-actions">
                        <a href="{{ route('admin.menuler.header.duzenle', $menu->id) }}"
                           class="table-action" title="Düzenle">
                            <i data-lucide="edit-3"></i>
                        </a>
                        <button type="button" class="table-action"
                                style="color:var(--danger)"
                                onclick="silMenu({{ $menu->id }}, '{{ addslashes($menu->menu_isim ?? '') }}', {{ $altCount }})"
                                title="Sil">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>

                {{-- ALT MENÜ PANEL --}}
                <div class="alt-menu-panel">
                    @if($altCount > 0)
                        <div style="font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em">
                            Alt Menüler ({{ $altCount }})
                        </div>
                        @foreach($menu->altmenu as $alt)
                            @php
                                $altAktif = (int)($alt->durum ?? 1) === 1;
                            @endphp
                            <div class="alt-menu-item">
                                <i data-lucide="corner-down-right" style="width:14px;height:14px;color:var(--text-muted);flex-shrink:0"></i>
                                <div class="alt-info">
                                    <div style="font-weight:600">{{ $alt->menu_isim ?? '—' }}</div>
                                    <div class="alt-link">{{ $alt->link ?? '—' }}</div>
                                </div>
                                @if(!$altAktif)
                                    <span class="badge badge-neutral" style="font-size:10px">Pasif</span>
                                @endif
                                <button type="button" class="table-action"
                                        style="color:var(--danger);width:26px;height:26px"
                                        onclick="silAltMenu({{ $alt->id }})"
                                        title="Alt menü sil">
                                    <i data-lucide="x" style="width:13px;height:13px"></i>
                                </button>
                            </div>
                        @endforeach
                    @else
                        <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0">
                            Henüz alt menü yok
                        </div>
                    @endif

                    {{-- Alt menü ekleme formu --}}
                    <form action="{{ route('admin.menuler.alt-menu.ekle', $menu->id) }}"
                          method="POST" class="alt-menu-add-form">
                        @csrf
                        <input type="text" name="menu_isim" required
                               class="form-input" placeholder="Alt menü adı">
                        <input type="text" name="link" required
                               class="form-input" placeholder="/sayfa veya https://...">
                        <button type="submit" class="btn btn-primary btn-sm" style="padding:6px 12px">
                            <i data-lucide="plus" style="width:14px;height:14px"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- HİDDEN FORMLAR --}}
@foreach($menuler as $menu)
    <form id="del-menu-{{ $menu->id }}"
          action="{{ route('admin.menuler.header.sil', $menu->id) }}"
          method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>

    @if(isset($menu->altmenu))
        @foreach($menu->altmenu as $alt)
            <form id="del-alt-{{ $alt->id }}"
                  action="{{ route('admin.menuler.alt-menu.sil', $alt->id) }}"
                  method="POST" style="display:none">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
@endforeach

<script>
function toggleAltMenu(btn) {
    btn.closest('.menu-item').classList.toggle('expanded');
}

function silMenu(id, ad, altCount) {
    let msg = 'Bu menüyü silmek istediğinize emin misiniz?\n\n' + ad;
    if (altCount > 0) {
        msg += '\n\n⚠️ Bu menünün ' + altCount + ' alt menüsü de silinecek!';
    }
    if (confirm(msg)) {
        document.getElementById('del-menu-' + id).submit();
    }
}

function silAltMenu(id) {
    if (confirm('Bu alt menüyü silmek istediğinize emin misiniz?')) {
        document.getElementById('del-alt-' + id).submit();
    }
}

// DRAG & DROP
(function() {
    const list = document.getElementById('menuList');
    if (!list) return;

    let draggedItem = null;

    list.querySelectorAll('.menu-item').forEach(function(item) {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            list.querySelectorAll('.menu-item').forEach(i => i.classList.remove('drag-over'));
            updateOrder();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (this !== draggedItem) {
                this.classList.add('drag-over');
            }
        });

        item.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });

        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            if (this !== draggedItem) {
                const allItems = Array.from(list.querySelectorAll('.menu-item'));
                const draggedIdx = allItems.indexOf(draggedItem);
                const targetIdx = allItems.indexOf(this);

                if (draggedIdx < targetIdx) {
                    this.parentNode.insertBefore(draggedItem, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(draggedItem, this);
                }
            }
        });
    });

    function updateOrder() {
        const items = list.querySelectorAll('.menu-item');
        const data = Array.from(items).map((item, idx) => ({
            id: parseInt(item.dataset.id),
            sira: idx
        }));

        fetch('{{ route("admin.menuler.header.sira-guncelle") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ menuler: data })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                // Hafif başarı bildirim - sadece görsel
                const list = document.getElementById('menuList');
                list.style.transition = 'background 0.3s';
                list.style.background = 'rgba(16,185,129,0.05)';
                setTimeout(() => list.style.background = '', 600);
            }
        })
        .catch(err => console.error('Sıralama hatası:', err));
    }
})();
</script>

@endsection