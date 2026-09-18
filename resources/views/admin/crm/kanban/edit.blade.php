@extends('admin._layout')

@section('title', 'Pano Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.index') }}">Kanban</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.show', $board->id) }}">{{ $board->adi }}</a>
    <span class="sep">/</span>
    <span class="current">Düzenle</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ {{ $board->adi }} — Düzenle</h1>
        <div class="page-subtitle">Pano ayarlarını güncelle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.kanban.show', $board->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Panoya Dön</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.crm.kanban.update', $board->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div style="max-width:680px">
        <div class="section">
            <div class="section-title">
                <i data-lucide="layout-grid"></i>
                <span>Pano Bilgileri</span>
            </div>

            <div class="form-group">
                <label class="form-label">Başlık <span class="required">*</span></label>
                <input type="text" name="adi" value="{{ old('adi', $board->adi) }}" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                @php
                    // Eğer controller $mevcutKategoriler göndermediyse, DB'den çek
                    $kategoriList = $mevcutKategoriler ?? \App\Models\Kanban\KanbanBoard::whereNotNull('kategori')
                        ->where('kategori', '!=', '')
                        ->distinct()
                        ->pluck('kategori')
                        ->sort()
                        ->values();
                    $mevcutKat = old('kategori', $board->kategori);
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
                    <small class="form-help">Yeni kategori adını yaz — kayıt sırasında oluşturulur</small>
                </div>

                {{-- Hidden input — gerçek değer buradan submit edilir --}}
                <input type="hidden" name="kategori" id="katHidden" value="{{ $mevcutKat }}">
            </div>

            <div class="form-group">
                <label class="form-label">Renk</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                    @foreach(['#b8b62e','#3b82f6','#8b5cf6','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#6366f1','#14b8a6'] as $renkOption)
                        <label style="cursor:pointer">
                            <input type="radio" name="renk" value="{{ $renkOption }}" {{ old('renk', $board->renk) == $renkOption ? 'checked' : '' }} style="display:none" class="renk-radio">
                            <span style="display:inline-block;width:30px;height:30px;border-radius:8px;background:{{ $renkOption }};border:2px solid transparent;transition:border-color 0.15s" class="renk-swatch"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Kapak Görseli</label>
                @if(!empty($board->kapak_url))
                    <div style="margin-bottom:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                        <img src="{{ $board->kapak_url }}" alt="kapak" style="width:160px;height:90px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--danger);cursor:pointer">
                            <input type="checkbox" name="kapak_kaldir" value="1" style="width:auto">
                            <span>Kapağı kaldır</span>
                        </label>
                    </div>
                @endif
                <input type="file" name="kapak" accept="image/*" class="form-input">
                <small class="form-help">Yeni görsel seçince mevcut kapak değişir. JPG/PNG/WEBP — maks. 5MB.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <textarea name="aciklama" rows="4" class="form-textarea">{{ old('aciklama', $board->aciklama) }}</textarea>
            </div>

            @if($board->olusturan_id == session('admin_id'))
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px;border:1px solid var(--border);border-radius:10px;background:var(--bg)">
                    <input type="checkbox" name="ozel" value="1" {{ old('ozel', $board->ozel ?? 0) ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer">
                    <span>
                        <span style="font-weight:600">🔒 Özel pano (sadece ben görürüm)</span>
                        <small class="form-help" style="display:block;margin-top:2px">İşaretlenmezse pano <strong>tüm yöneticilere</strong> açıktır. İşaretlersen yalnızca sen (ve eklediğin üyeler) görürsün.</small>
                    </span>
                </label>
            </div>
            @endif

            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Kaydet</span>
                </button>
                <a href="{{ route('admin.crm.kanban.show', $board->id) }}" class="btn btn-secondary">İptal</a>

                <button type="button" onclick="panoSil()" class="btn btn-danger" style="margin-left:auto">
                    <i data-lucide="trash-2"></i>
                    <span>Panoyu Sil</span>
                </button>
            </div>
        </div>
    </div>
</form>

{{-- ═══════════════════════════════════════════════════════════════
    İÇ İÇE FORM YASAK olduğu için sil form'u BURADA, ana form DIŞINDA.
═══════════════════════════════════════════════════════════════ --}}
<form id="pano-sil-form" action="{{ route('admin.crm.kanban.destroy', $board->id) }}" method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function panoSil() {
    if (!confirm('Panoyu silmek istediğine emin misin?\nBu işlem geri alınamaz!')) return;
    document.getElementById('pano-sil-form').submit();
}

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