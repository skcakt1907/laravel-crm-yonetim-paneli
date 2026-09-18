@extends('admin._layout')

@section('title', 'Menü Düzenle')

@push('head')
<style>
    .toggle-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 16px; gap: 16px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.2);
        border-radius: var(--radius-md);
        margin-bottom: 10px;
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

    .alt-list-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 6px;
        margin-bottom: 6px;
        font-size: 13px;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.menuler.header') }}">Menüler</a>
    <span class="sep">/</span>
    <span class="current">{{ \Illuminate\Support\Str::limit($menu->menu_isim ?? 'Düzenle', 40) }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="edit-3"></i>
            Menü Düzenle
        </h1>
        <div class="page-subtitle">{{ $menu->menu_isim ?? '—' }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.menuler.header') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.menuler.header.duzenle.post', $menu->id) }}" method="POST">
    @csrf

    <div class="form-grid">
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Menü Bilgileri</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Menü Adı <span class="required">*</span></label>
                        <input type="text" name="menu_isim"
                               value="{{ old('menu_isim', $menu->menu_isim ?? '') }}"
                               required class="form-input"
                               placeholder="Örn: Hakkımızda">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sıralama</label>
                        <input type="number" min="0" name="sira"
                               value="{{ old('sira', (int)($menu->sira ?? 0)) }}"
                               class="form-input">
                        <small class="form-help">Düşük sayı önce gösterilir</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Link / URL <span class="required">*</span></label>
                        <input type="text" name="link"
                               value="{{ old('link', $menu->link ?? '') }}"
                               required class="form-input"
                               placeholder="/sayfa veya https://..."
                               style="font-family:monospace;font-size:13px">
                        <small class="form-help">Tıklandığında gidilecek adres</small>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Alternatif URL</label>
                        <input type="text" name="menu_url"
                               value="{{ old('menu_url', $menu->menu_url ?? '') }}"
                               class="form-input"
                               placeholder="opsiyonel">
                        <small class="form-help">Bazı temalarda kullanılan alternatif adres</small>
                    </div>
                </div>
            </div>

            {{-- ALT MENÜLER (read-only) --}}
            @if(!empty($altmenuler) && count($altmenuler) > 0)
                <div class="section" style="margin-top:16px">
                    <div class="section-title">
                        <i data-lucide="corner-down-right"></i>
                        <span>Alt Menüler ({{ count($altmenuler) }})</span>
                    </div>
                    @foreach($altmenuler as $alt)
                        <div class="alt-list-item">
                            <i data-lucide="corner-down-right" style="width:14px;height:14px;color:var(--text-muted)"></i>
                            <div style="flex:1;min-width:0">
                                <div style="font-weight:600">{{ $alt->menu_isim ?? '—' }}</div>
                                <div style="font-size:11.5px;color:var(--text-muted);font-family:monospace">{{ $alt->link ?? '—' }}</div>
                            </div>
                            @if((int)($alt->durum ?? 1) === 1)
                                <span class="badge badge-success" style="font-size:10px">Aktif</span>
                            @else
                                <span class="badge badge-neutral" style="font-size:10px">Pasif</span>
                            @endif
                        </div>
                    @endforeach
                    <small class="form-help" style="margin-top:10px;display:block">
                        <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                        Alt menüleri Navbar listesinden yönetin (chevron'a tıklayın)
                    </small>
                </div>
            @endif
        </div>

        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Görünüm Ayarları</span>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Aktif</div>
                        <div class="desc">Sitede görünür</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="durum" value="1"
                               {{ old('durum', (int)($menu->durum ?? 1)) ? 'checked' : '' }}>
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="toggle-card">
                    <div>
                        <div class="lbl-strong">Yeni Sekmede Aç</div>
                        <div class="desc">Link yeni pencerede açılır</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="sekme" value="1"
                               {{ old('sekme', (int)($menu->sekme ?? 0)) ? 'checked' : '' }}>
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
                    <p style="margin:0 0 6px"><strong>ID:</strong> #{{ $menu->id }}</p>
                    <p style="margin:0 0 6px"><strong>Tip:</strong> {{ ($menu->ustid ?? 0) > 0 ? 'Alt Menü' : 'Ana Menü' }}</p>
                    @if(isset($altmenuler))
                        <p style="margin:0"><strong>Alt menü sayısı:</strong> {{ count($altmenuler) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span class="required">*</span> zorunlu alan
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.menuler.header') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="button" class="btn btn-ghost"
                    style="color:var(--danger)"
                    onclick="silMenu()">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Değişiklikleri Kaydet</span>
            </button>
        </div>
    </div>
</form>

{{-- SİL FORMU --}}
<form id="del-menu-form"
      action="{{ route('admin.menuler.header.sil', $menu->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function silMenu() {
    @if(!empty($altmenuler) && count($altmenuler) > 0)
        if (confirm('Bu menüyü silmek istediğinize emin misiniz?\n\n{{ addslashes($menu->menu_isim ?? '') }}\n\n⚠️ {{ count($altmenuler) }} alt menü de silinecek!')) {
            document.getElementById('del-menu-form').submit();
        }
    @else
        if (confirm('Bu menüyü silmek istediğinize emin misiniz?\n\n{{ addslashes($menu->menu_isim ?? '') }}')) {
            document.getElementById('del-menu-form').submit();
        }
    @endif
}
</script>

@endsection