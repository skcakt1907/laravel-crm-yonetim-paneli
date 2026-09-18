@extends('admin._layout')

@section('title', 'Kanban Boards')

@push('head')
<style>
    .board-card {
        border-radius: var(--radius-lg);
        overflow: hidden;
        border: 1px solid var(--border);
        transition: transform 0.15s, box-shadow 0.15s;
        cursor: pointer;
        position: relative;
        background: var(--surface);
    }
    .board-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
    }

    .board-card-cover {
        display: block;
        height: 100px;
        position: relative;
        text-decoration: none;
        overflow: hidden;
    }

    .board-card-body {
        padding: 12px 14px;
    }

    .board-card-title {
        font-weight: 700;
        font-size: 14px;
        color: var(--text);
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .board-card-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        color: var(--text-muted);
    }

    .board-card-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        gap: 4px;
        z-index: 10;
    }
    .board-card-action {
        background: rgba(0,0,0,0.5);
        color: rgba(255,255,255,0.9);
        border: none;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        text-decoration: none;
        cursor: pointer;
        backdrop-filter: blur(4px);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: background 0.15s;
    }
    .board-card-action:hover { background: rgba(0,0,0,0.75); }
    .board-card-action.delete { background: rgba(160,0,0,0.5); color: rgba(255,180,180,0.95); }
    .board-card-action.delete:hover { background: rgba(210,0,0,0.75); }

    .add-board-card {
        border-radius: var(--radius-lg);
        border: 2px dashed var(--border);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 170px;
        text-decoration: none;
        color: var(--text-muted);
        transition: all 0.15s;
        background: transparent;
    }
    .add-board-card:hover {
        border-color: var(--brand);
        color: var(--brand);
        background: var(--brand-soft);
    }

    .kategori-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .kategori-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .kategori-name {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-secondary);
    }
    .kategori-count {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 500;
    }
    .kategori-divider {
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .board-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Kanban Boards</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">📋 Kanban Boards <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ count($boards ?? []) }}</span></h1>
        <div class="page-subtitle">Trello tarzı görev yönetimi · sürükle bırak, kart, checklist</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.kanban.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Pano</span>
        </a>
    </div>
</div>

@php
$boardBgs = [
  'linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%)',
  'linear-gradient(135deg,#0d0d0d 0%,#1a0533 50%,#2d1b69 100%)',
  'linear-gradient(135deg,#0a1628 0%,#1e3a5f 50%,#0d7377 100%)',
  'linear-gradient(135deg,#1a0a00 0%,#3d1500 50%,#8b2500 100%)',
  'linear-gradient(135deg,#0a0a1a 0%,#1a2a0a 50%,#2a4a0a 100%)',
  'linear-gradient(135deg,#1a0a2e 0%,#2d1b4e 50%,#4a2080 100%)',
  'linear-gradient(135deg,#001a1a 0%,#003333 50%,#006666 100%)',
  'linear-gradient(135deg,#1a1a00 0%,#333300 50%,#666600 100%)',
  'linear-gradient(135deg,#1a000a 0%,#3d0015 50%,#800030 100%)',
  'linear-gradient(135deg,#001033 0%,#002266 50%,#0044cc 100%)',
];
$boardColors = ['#b8b62e','#3b82f6','#8b5cf6','#10b981','#f97316','#ef4444','#06b6d4','#ec4899','#6366f1','#14b8a6'];
$allBoards = $boards ?? collect();
$globalIdx = 0;
@endphp

@if($allBoards->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="layout-grid" class="empty-state-icon"></i>
            <h4>Henüz pano yok</h4>
            <p>İlk panoyu oluşturmak için yukarıdaki "Yeni Pano" butonuna tıkla.</p>
        </div>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:32px">
    @foreach(($kategoriler ?? $allBoards->groupBy(fn($b)=>$b->kategori?:'Kategorisiz')->sortKeys()) as $kat => $katBoards)
        @php $katColor = $boardColors[$loop->index % count($boardColors)]; @endphp

        <div>
            <div class="kategori-header">
                <div class="kategori-dot" style="background:{{ $katColor }};box-shadow:0 0 8px {{ $katColor }}80"></div>
                <span class="kategori-name">{{ $kat }}</span>
                <span class="kategori-count">{{ count($katBoards) }} pano</span>
                <div class="kategori-divider"></div>
                <a href="{{ route('admin.crm.kanban.create') }}?kategori={{ urlencode($kat) }}"
                   class="btn btn-ghost btn-sm" style="color:{{ $katColor }};border:1px solid {{ $katColor }}40">
                    <i data-lucide="plus"></i>
                    <span>Yeni Pano</span>
                </a>
                @if($kat !== 'Kategorisiz')
                    <button type="button"
                            onclick="ykKategoriYenidenAdlandir('{{ e($kat) }}')"
                            class="btn btn-ghost btn-sm" style="color:var(--text-muted)"
                            title="Kategoriyi yeniden adlandır">
                        <i data-lucide="edit-2" style="width:13px;height:13px"></i>
                    </button>
                    <button type="button"
                            onclick="ykKategoriSil('{{ e($kat) }}', {{ count($katBoards) }})"
                            class="btn btn-ghost btn-sm" style="color:var(--danger)"
                            title="Kategoriyi sil (panolar 'Kategorisiz' altına alınır)">
                        <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                    </button>
                @endif
            </div>

            <div class="board-grid">
                @foreach($katBoards as $b)
                    @php
                        $bg = $boardBgs[$globalIdx % count($boardBgs)];
                        $accentColor = $b->renk ?: $boardColors[$globalIdx % count($boardColors)];
                        $listCount = count($b->lists ?? []);
                        $cardCount = ($b->lists ?? collect())->sum(fn($l) => count($l->cards ?? []));
                        $globalIdx++;
                    @endphp
                    <div class="board-card">
                        <div class="board-card-actions">
                            <a href="{{ route('admin.crm.kanban.edit', $b->id) }}" class="board-card-action" title="Düzenle">
                                <i data-lucide="edit-2" style="width:11px;height:11px"></i>
                            </a>
                            <form action="{{ route('admin.crm.kanban.destroy', $b->id) }}" method="POST" style="margin:0" onsubmit="return confirm('{{ addslashes($b->adi ?? '') }} panosunu silmek istediğine emin misin?\nBu işlem geri alınamaz!')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="board-card-action delete" title="Sil">
                                    <i data-lucide="trash-2" style="width:11px;height:11px"></i>
                                </button>
                            </form>
                        </div>

                        @php $kapakUrl = $b->kapak_url ?? null; @endphp
                        <a href="{{ route('admin.crm.kanban.show', $b->id) }}" class="board-card-cover"
                           style="{{ $kapakUrl ? 'background:#0a0a0a center/cover no-repeat url(\''.$kapakUrl.'\')' : 'background:'.$bg }}">
                            @if(!$kapakUrl)
                                <div style="position:absolute;width:80px;height:80px;border-radius:50%;background:{{ $accentColor }}25;top:-20px;right:-20px"></div>
                                <div style="position:absolute;width:50px;height:50px;border-radius:50%;background:{{ $accentColor }}18;bottom:10px;left:20px"></div>
                                <div style="position:absolute;width:30px;height:30px;border-radius:50%;background:{{ $accentColor }}30;top:30px;left:60px"></div>
                            @endif
                            <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:{{ $accentColor }}"></div>
                        </a>

                        <div class="board-card-body">
                            <div class="board-card-title" title="{{ $b->adi ?? '—' }}">{{ $b->adi ?? '—' }}</div>
                            <div class="board-card-meta">
                                <span>📂 {{ $listCount }} liste · 🃏 {{ $cardCount }} kart</span>
                                <a href="{{ route('admin.crm.kanban.show', $b->id) }}" style="color:{{ $accentColor }};font-weight:700;font-size:11px;text-decoration:none">Aç →</a>
                            </div>
                        </div>
                    </div>
                @endforeach

                <a href="{{ route('admin.crm.kanban.create') }}?kategori={{ urlencode($kat) }}" class="add-board-card">
                    <div style="font-size:28px;margin-bottom:6px">＋</div>
                    <div style="font-size:13px;font-weight:600">Yeni Pano</div>
                </a>
            </div>
        </div>
    @endforeach
    </div>
@endif

{{-- Yeni Kategori Ekle --}}
<div style="margin-top:32px;border-top:1px solid var(--border);padding-top:24px">
    <button id="ykKatBtn" onclick="ykKatToggle(true)" class="btn btn-secondary" style="border-style:dashed">
        <i data-lucide="folder-plus"></i>
        <span>Yeni Kategori Ekle</span>
    </button>

    <div id="ykKatForm" style="display:none;margin-top:12px;max-width:520px">
        <form action="{{ route('admin.crm.kanban.store') }}" method="POST" class="section">
            @csrf
            <div class="section-title">
                <i data-lucide="folder-plus"></i>
                <span>Yeni Kategori</span>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori Adı <span class="required">*</span></label>
                <input type="text" name="kategori" placeholder="ör: Web Tasarım, Sosyal Medya..." required class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">İlk Pano Adı <span class="required">*</span></label>
                <input type="text" name="adi" placeholder="ör: DN Web Tasarım & Yazılım" required class="form-input">
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary" style="flex:1">
                    <i data-lucide="plus"></i>
                    <span>Kategori & Pano Oluştur</span>
                </button>
                <button type="button" onclick="ykKatToggle(false)" class="btn btn-secondary">İptal</button>
            </div>
        </form>
    </div>
</div>

{{-- CSRF token (fetch için) --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
function ykKatToggle(open) {
    document.getElementById('ykKatBtn').style.display = open ? 'none' : '';
    document.getElementById('ykKatForm').style.display = open ? 'block' : 'none';
    if (open) document.querySelector('#ykKatForm input[name="kategori"]')?.focus();
}

const _KCSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

function ykKategoriSil(kategori, panoSayisi) {
    const msg = panoSayisi > 0
        ? `"${kategori}" kategorisini silmek istediğine emin misin?\n\n${panoSayisi} pano "Kategorisiz" altına taşınacak (panolar SİLİNMEZ).`
        : `"${kategori}" kategorisini silmek istediğine emin misin?`;
    if (!confirm(msg)) return;

    const fd = new FormData();
    fd.append('kategori', kategori);
    fd.append('_method', 'DELETE');
    fd.append('_token', _KCSRF);

    fetch('{{ route("admin.crm.kanban.kategori.sil") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _KCSRF, 'Accept': 'application/json' },
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) { location.reload(); }
        else { alert('Hata: ' + (d.message || 'Silinemedi')); }
    }).catch(err => alert('Hata: ' + err.message));
}

function ykKategoriYenidenAdlandir(eski) {
    const yeni = prompt(`"${eski}" kategorisinin yeni adı nedir?`, eski);
    if (!yeni || yeni.trim() === '' || yeni.trim() === eski) return;

    const fd = new FormData();
    fd.append('eski_kategori', eski);
    fd.append('yeni_kategori', yeni.trim());
    fd.append('_token', _KCSRF);

    fetch('{{ route("admin.crm.kanban.kategori.yeniden-adlandir") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _KCSRF, 'Accept': 'application/json' },
        body: fd
    }).then(r => r.json()).then(d => {
        if (d.success) { location.reload(); }
        else { alert('Hata: ' + (d.message || 'Güncellenemedi')); }
    }).catch(err => alert('Hata: ' + err.message));
}
</script>

@endsection