@extends('admin._layout')

@section('title', 'Footer Menüleri')

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

    .add-menu-form {
        background: linear-gradient(135deg, var(--brand-soft), transparent);
        border: 1px dashed var(--brand-medium);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 16px;
    }

    .form-grid-inline {
        display: grid;
        grid-template-columns: 1.5fr 2fr 0.5fr auto;
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
    <span class="current">Footer</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="layout-template"></i>
            Footer Menüleri
            <span class="badge badge-brand">{{ count($menuler) }}</span>
        </h1>
        <div class="page-subtitle">Site alt kısmındaki menü bağlantılarını yönetin</div>
    </div>
</div>

{{-- ALT TAB MENU --}}
<div class="menu-tabs">
    @if(Route::has('admin.menuler.header'))
        <a href="{{ route('admin.menuler.header') }}" class="menu-tab">
            <i data-lucide="menu" style="width:15px;height:15px"></i>
            <span>Navbar (Header)</span>
        </a>
    @endif
    <a href="{{ route('admin.menuler.footer') }}" class="menu-tab active">
        <i data-lucide="layout-template" style="width:15px;height:15px"></i>
        <span>Footer</span>
    </a>
    @if(Route::has('admin.menuler.navbar-ayarlari'))
        <a href="{{ route('admin.menuler.navbar-ayarlari') }}" class="menu-tab">
            <i data-lucide="settings" style="width:15px;height:15px"></i>
            <span>Navbar Üst Bar Ayarları</span>
        </a>
    @endif
</div>

{{-- YENİ MENÜ EKLEME --}}
<div class="add-menu-form">
    <div style="font-weight:600;font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;color:var(--brand-dark)">
        <i data-lucide="plus-circle" style="width:18px;height:18px"></i>
        <span>Yeni Footer Menü Ekle</span>
    </div>
    <form action="{{ route('admin.menuler.footer.ekle') }}" method="POST">
        @csrf
        <div class="form-grid-inline">
            <div>
                <label class="form-label" style="font-size:11.5px">Menü Adı <span class="required">*</span></label>
                <input type="text" name="ad" value="{{ old('ad') }}"
                       required class="form-input" placeholder="Örn: Gizlilik Politikası">
            </div>
            <div>
                <label class="form-label" style="font-size:11.5px">Link <span class="required">*</span></label>
                <input type="text" name="link" value="{{ old('link') }}"
                       required class="form-input" placeholder="/gizlilik veya https://...">
            </div>
            <div>
                <label class="form-label" style="font-size:11.5px">Sıra</label>
                <input type="number" name="sira" min="0" value="0" class="form-input">
            </div>
            <button type="submit" class="btn btn-primary" style="height:38px">
                <i data-lucide="plus"></i>
                <span>Ekle</span>
            </button>
        </div>
    </form>
</div>

@if(count($menuler) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="layout-template" class="empty-state-icon"></i>
            <h4>Henüz footer menü yok</h4>
            <p>Yukarıdaki formdan ilk footer bağlantınızı ekleyin.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">Sıra</th>
                        <th>Adı</th>
                        <th>Link</th>
                        <th style="width:110px">Durum</th>
                        <th class="text-right" style="width:100px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($menuler as $m)
                        @php
                            $ad = $m->menu_isim ?? $m->adi ?? '—';
                            $link = $m->link ?? $m->menu_url ?? '—';
                            $aktif = (int)($m->durum ?? 1) === 1;
                        @endphp
                        <tr>
                            <td>
                                <span style="background:var(--bg-subtle);padding:3px 9px;border-radius:6px;font-size:12px;font-weight:600">
                                    {{ $m->sira ?? 0 }}
                                </span>
                            </td>
                            <td style="font-weight:600">{{ $ad }}</td>
                            <td style="font-family:monospace;font-size:12.5px;color:var(--text-secondary)">
                                {{ \Illuminate\Support\Str::limit($link, 60) }}
                            </td>
                            <td>
                                @if($aktif)
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width:11px;height:11px"></i>
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge badge-neutral">Pasif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    @if($link && $link !== '—')
                                        <a href="{{ str_starts_with($link, 'http') ? $link : url($link) }}"
                                           target="_blank" class="table-action" title="Aç">
                                            <i data-lucide="external-link"></i>
                                        </a>
                                    @endif
                                    <button type="button" class="table-action"
                                            style="color:var(--danger)"
                                            onclick="silFooterMenu({{ $m->id }}, '{{ addslashes($ad) }}')"
                                            title="Sil">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @foreach($menuler as $m)
        <form id="del-footer-{{ $m->id }}"
              action="{{ route('admin.menuler.header.sil', $m->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silFooterMenu(id, ad) {
    if (confirm('Bu footer menüyü silmek istediğinize emin misiniz?\n\n' + ad)) {
        document.getElementById('del-footer-' + id).submit();
    }
}
</script>

@endsection