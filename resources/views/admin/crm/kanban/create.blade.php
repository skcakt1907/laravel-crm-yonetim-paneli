@extends('admin._layout')

@section('title', 'Yeni Pano')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.index') }}">Kanban</a>
    <span class="sep">/</span>
    <span class="current">Yeni Pano</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Pano</h1>
        <div class="page-subtitle">Yeni bir kanban panosu oluştur</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.kanban.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.crm.kanban.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div style="max-width:680px">
        <div class="section">
            <div class="section-title">
                <i data-lucide="layout-grid"></i>
                <span>Pano Bilgileri</span>
            </div>

            <div class="form-group">
                <label class="form-label">Başlık <span class="required">*</span></label>
                <input type="text" name="adi" value="{{ old('adi') }}" required class="form-input" placeholder="Örn: Müşteri Geliştirme">
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                @php
                    $kategoriList = $mevcutKategoriler ?? collect();
                    $mevcutKat = old('kategori', request('kategori'));
                    $isYeni = $mevcutKat && !$kategoriList->contains($mevcutKat);
                @endphp

                <select id="katSelect" class="form-select" onchange="katSelectChange(this)">
                    <option value="">— Kategorisiz —</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" {{ $mevcutKat === $kat ? 'selected' : '' }}>
                            📁 {{ $kat }}
                        </option>
                    @endforeach
                    <option value="__yeni__" {{ $isYeni ? 'selected' : '' }}>➕ Yeni kategori ekle...</option>
                </select>

                <div id="yeniKatWrap" style="margin-top:8px;{{ $isYeni ? '' : 'display:none' }}">
                    <input type="text" id="yeniKatInput" placeholder="Yeni kategori adı..." class="form-input"
                           value="{{ $isYeni ? $mevcutKat : '' }}" autocomplete="off">
                    <small class="form-help">Yeni kategori adını yaz — pano oluşturulurken kategori de oluşturulur</small>
                </div>

                <input type="hidden" name="kategori" id="katHidden" value="{{ $mevcutKat }}">

                <div class="form-help" style="margin-top:6px">Aynı kategoride birden çok pano olabilir (gruplama için)</div>
            </div>

            <div class="form-group">
                <label class="form-label">Renk</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                    @foreach(['#b8b62e','#3b82f6','#8b5cf6','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#6366f1','#14b8a6'] as $renkOption)
                        <label style="cursor:pointer">
                            <input type="radio" name="renk" value="{{ $renkOption }}" {{ old('renk') == $renkOption ? 'checked' : '' }} style="display:none" class="renk-radio">
                            <span style="display:inline-block;width:30px;height:30px;border-radius:8px;background:{{ $renkOption }};border:2px solid transparent;transition:border-color 0.15s" class="renk-swatch"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Kapak Görseli <span style="color:var(--text-muted);font-size:11px">(opsiyonel)</span></label>
                <input type="file" name="kapak" accept="image/*" class="form-input">
                <small class="form-help">JPG/PNG/WEBP — maks. 5MB. Panonun listede görünen kapağı olur (boş bırakılırsa renk/gradient kullanılır).</small>
            </div>

            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <textarea name="aciklama" rows="4" class="form-textarea" placeholder="Bu pano ne için kullanılacak?">{{ old('aciklama') }}</textarea>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border:1px solid var(--border);border-radius:10px;background:var(--bg)">
                    <input type="checkbox" name="ozel" value="1" {{ old('ozel') ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer">
                    <span>
                        <span style="font-weight:600">🔒 Özel pano (sadece ben görürüm)</span>
                        <small class="form-help" style="display:block;margin-top:2px">İşaretlenmezse pano <strong>tüm yöneticilere</strong> açıktır. İşaretlersen yalnızca sen (ve eklediğin üyeler) görürsün.</small>
                    </span>
                </label>
            </div>

            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Pano Oluştur</span>
                </button>
                <a href="{{ route('admin.crm.kanban.index') }}" class="btn btn-secondary">İptal</a>
            </div>
        </div>
    </div>
</form>

<script>
// Kategori dropdown — "Yeni kategori" seçilince input açılır
function katSelectChange(sel) {
    const wrap = document.getElementById('yeniKatWrap');
    const yeniInput = document.getElementById('yeniKatInput');
    const hidden = document.getElementById('katHidden');

    if (sel.value === '__yeni__') {
        wrap.style.display = '';
        yeniInput.focus();
        hidden.value = yeniInput.value || '';
    } else {
        wrap.style.display = 'none';
        hidden.value = sel.value;
    }
}

// Yeni kategori input'una yazılan değer hidden'a aktar
document.getElementById('yeniKatInput')?.addEventListener('input', function() {
    document.getElementById('katHidden').value = this.value.trim();
});

document.querySelectorAll('.renk-radio').forEach(radio => {
    if (radio.checked) {
        radio.nextElementSibling.style.borderColor = '#fff';
    }
    radio.addEventListener('change', () => {
        document.querySelectorAll('.renk-swatch').forEach(s => s.style.borderColor = 'transparent');
        radio.nextElementSibling.style.borderColor = '#fff';
    });
});
</script>

@endsection