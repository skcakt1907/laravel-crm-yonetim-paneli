@extends('admin._layout')

@section('title', 'Referanslar')

@push('head')
<style>
    .ref-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: all 0.2s;
    }
    .ref-card:hover {
        border-color: var(--brand-medium);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .ref-logo {
        position: relative;
        aspect-ratio: 1.5/1;
        background: var(--bg-subtle);
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
        overflow: hidden;
    }
    .ref-logo img {
        max-width: 100%; max-height: 100%;
        object-fit: contain;
    }
    .ref-logo .empty-bg {
        color: var(--brand-dark);
        opacity: 0.4;
    }
    .ref-logo .sira-tag {
        position: absolute; top: 8px; left: 8px;
        background: rgba(0,0,0,0.7);
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 99px;
    }
    .ref-logo .badge-overlay {
        position: absolute; top: 8px; right: 8px;
    }

    .ref-body {
        padding: 12px 14px;
        flex: 1;
        display: flex; flex-direction: column;
        gap: 4px;
    }
    .ref-title { font-weight: 700; font-size: 14px; color: var(--text); }
    .ref-desc {
        font-size: 12px; color: var(--text-muted);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ref-actions {
        display: flex; gap: 6px;
        padding: 8px 14px;
        border-top: 1px solid var(--border);
        background: var(--bg-subtle);
    }
    .ref-actions .btn { flex: 1; padding: 6px 10px; font-size: 12px; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Referanslar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="award"></i>
            Referanslar
            <span class="badge badge-brand">{{ $referanslar->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşteri referanslarınızı yönetin</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.referanslar.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Referans</span>
        </a>
    </div>
</div>

@php
    $toplam  = $referanslar->total();
    $aktif   = $referanslar->filter(fn($r) => (int)($r->durum ?? 0) === 1)->count();
    $pasif   = $referanslar->filter(fn($r) => (int)($r->durum ?? 0) === 0)->count();
    $linkli  = $referanslar->filter(fn($r) => !empty($r->link) || !empty($r->url))->count();
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="award"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Referans</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="eye"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Yayında</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktif }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(148,163,184,0.18);color:#94a3b8">
            <i data-lucide="eye-off"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value">{{ $pasif }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="link"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Linkli</div>
            <div class="stat-card-value" style="color:var(--brand-dark)">{{ $linkli }}</div>
        </div>
    </div>
</div>

@if($referanslar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="award" class="empty-state-icon"></i>
            <h4>Henüz referans yok</h4>
            <p>Müşteri referanslarınızı ekleyerek güveninizi artırın.</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.referanslar.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Referans</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
        @foreach($referanslar as $r)
            @php
                $baslik = $r->adi ?? $r->baslik ?? '—';
                $aciklama = strip_tags($r->aciklama ?? '');
                $resim = $r->resim ?? $r->logo ?? null;
                $aktifR = (int)($r->durum ?? 0) === 1;
                $sira = (int)($r->sira ?? 0);
                $link = $r->link ?? $r->url ?? '';

                // Resim path normalize
                if ($resim && !str_starts_with($resim, 'tema/')) {
                    $resimPath = asset('tema/uploads/referanslar/' . $resim);
                } else {
                    $resimPath = $resim ? asset($resim) : null;
                }
            @endphp
            <div class="ref-card">
                <div class="ref-logo">
                    @if($resimPath)
                        <img src="{{ $resimPath }}"
                             alt="{{ $baslik }}"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div style="display:none;align-items:center;justify-content:center;">
                            <i data-lucide="award" style="width:36px;height:36px" class="empty-bg"></i>
                        </div>
                    @else
                        <i data-lucide="award" style="width:36px;height:36px" class="empty-bg"></i>
                    @endif

                    @if($sira > 0)
                        <span class="sira-tag">#{{ $sira }}</span>
                    @endif

                    <div class="badge-overlay">
                        @if($aktifR)
                            <span class="badge badge-success" style="font-size:10px">
                                <i data-lucide="check" style="width:10px;height:10px"></i>
                            </span>
                        @else
                            <span class="badge badge-neutral" style="font-size:10px">
                                <i data-lucide="pause" style="width:10px;height:10px"></i>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="ref-body">
                    <div class="ref-title">{{ $baslik }}</div>
                    @if($aciklama)
                        <div class="ref-desc">{{ $aciklama }}</div>
                    @endif
                </div>

                <div class="ref-actions">
                    <a href="{{ route('admin.referanslar.duzenle', $r->id) }}" class="btn btn-primary btn-sm">
                        <i data-lucide="edit-3"></i>
                    </a>
                    @if($link)
                        <a href="{{ $link }}" target="_blank" class="btn btn-secondary btn-sm" title="Web siteyi aç">
                            <i data-lucide="external-link"></i>
                        </a>
                    @endif
                    <button type="button" class="btn btn-ghost btn-sm"
                            style="color:var(--danger)"
                            onclick="silReferans({{ $r->id }}, '{{ addslashes($baslik) }}')">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($referanslar->hasPages())
        <div style="margin-top:16px">{{ $referanslar->links() }}</div>
    @endif

    @foreach($referanslar as $r)
        <form id="del-ref-{{ $r->id }}"
              action="{{ route('admin.referanslar.sil', $r->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silReferans(id, baslik) {
    if (confirm('Bu referansı silmek istediğinize emin misiniz?\n\n' + baslik)) {
        document.getElementById('del-ref-' + id).submit();
    }
}
</script>

@endsection