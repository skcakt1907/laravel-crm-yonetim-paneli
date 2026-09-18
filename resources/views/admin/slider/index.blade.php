@extends('admin._layout')

@section('title', 'Slider')

@push('head')
<style>
    .sl-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: all 0.2s;
    }
    .sl-card:hover {
        border-color: var(--brand-medium);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .sl-media {
        position: relative;
        aspect-ratio: 16/9;
        background: var(--bg-subtle);
        overflow: hidden;
    }
    .sl-media img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .sl-media .empty-bg {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--brand-soft), rgba(184,182,46,0.04));
        color: var(--brand-dark);
        font-size: 42px;
    }
    .sl-media .badge-overlay {
        position: absolute; top: 10px; right: 10px;
        display: flex; gap: 6px;
    }
    .sl-media .sira-tag {
        position: absolute; top: 10px; left: 10px;
        background: rgba(0,0,0,0.7);
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        padding: 3px 9px;
        border-radius: 99px;
        display: flex; align-items: center; gap: 4px;
    }

    .sl-body {
        padding: 14px 16px;
        flex: 1;
        display: flex; flex-direction: column;
        gap: 6px;
    }
    .sl-title { font-weight: 700; font-size: 14.5px; color: var(--text); }
    .sl-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }
    .sl-meta {
        display: flex; align-items: center; gap: 10px;
        font-size: 11px; color: var(--text-muted);
        margin-top: auto; padding-top: 8px;
        border-top: 1px dashed var(--border);
    }

    .sl-actions {
        display: flex; gap: 6px;
        padding: 10px 14px;
        border-top: 1px solid var(--border);
        background: var(--bg-subtle);
    }
    .sl-actions .btn { flex: 1; padding: 7px 10px; font-size: 12.5px; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Slider</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="image"></i>
            Slider
            <span class="badge badge-brand">{{ $sliderlar->total() }}</span>
        </h1>
        <div class="page-subtitle">Anasayfa slider yönetimi</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.slider.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Slide</span>
        </a>
    </div>
</div>

{{-- STAT --}}
@php
    $toplam  = $sliderlar->total();
    $aktif   = $sliderlar->filter(fn($s) => (int)($s->durum ?? 0) === 1)->count();
    $pasif   = $sliderlar->filter(fn($s) => (int)($s->durum ?? 0) === 0)->count();
    $linkli  = $sliderlar->filter(fn($s) => !empty($s->url))->count();
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="image"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Slide</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="eye"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
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

@if($sliderlar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="image" class="empty-state-icon"></i>
            <h4>Henüz slide yok</h4>
            <p>İlk slider görselinizi ekleyin.</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.slider.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Slide</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($sliderlar as $s)
            @php
                $adi = $s->adi ?? '—';
                $aciklama = strip_tags($s->aciklama ?? '');
                $resim = $s->resim ?? null;
                $aktifS = (int)($s->durum ?? 0) === 1;
                $sira = (int)($s->sira ?? 0);
                $url = $s->url ?? '';
                $yeniSekme = (int)($s->sekme ?? 0) === 1;
            @endphp
            <div class="sl-card">
                <div class="sl-media">
                    @if($resim)
                        <img src="{{ asset('tema/uploads/slider/' . $resim) }}"
                             alt="{{ $adi }}"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="empty-bg" style="display:none">
                            <i data-lucide="image"></i>
                        </div>
                    @else
                        <div class="empty-bg">
                            <i data-lucide="image"></i>
                        </div>
                    @endif

                    <span class="sira-tag">
                        <i data-lucide="hash" style="width:11px;height:11px"></i>
                        {{ $sira }}
                    </span>

                    <div class="badge-overlay">
                        @if($aktifS)
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
                </div>

                <div class="sl-body">
                    <div class="sl-title">{{ $adi }}</div>
                    @if($aciklama)
                        <div class="sl-desc">{{ \Illuminate\Support\Str::limit($aciklama, 80) }}</div>
                    @endif

                    @if($url)
                        <div class="sl-meta">
                            <span style="display:inline-flex;align-items:center;gap:4px;flex:1;min-width:0">
                                <i data-lucide="link" style="width:11px;height:11px;flex-shrink:0"></i>
                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:monospace">
                                    {{ \Illuminate\Support\Str::limit($url, 40) }}
                                </span>
                            </span>
                            @if($yeniSekme)
                                <span title="Yeni sekmede açılır" style="color:var(--brand-dark)">
                                    <i data-lucide="external-link" style="width:11px;height:11px"></i>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="sl-actions">
                    <a href="{{ route('admin.slider.duzenle', $s->id) }}" class="btn btn-primary btn-sm">
                        <i data-lucide="edit-3"></i>
                        <span>Düzenle</span>
                    </a>
                    @if($url)
                        <a href="{{ $url }}" target="_blank" class="btn btn-secondary btn-sm" title="Linki aç">
                            <i data-lucide="external-link"></i>
                        </a>
                    @endif
                    <button type="button" class="btn btn-ghost btn-sm"
                            style="color:var(--danger);flex:0 0 auto;padding:7px 10px"
                            onclick="silSlider({{ $s->id }}, '{{ addslashes($adi) }}')">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if($sliderlar->hasPages())
        <div style="margin-top:16px">{{ $sliderlar->links() }}</div>
    @endif

    @foreach($sliderlar as $s)
        <form id="del-slider-{{ $s->id }}"
              action="{{ route('admin.slider.sil', $s->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silSlider(id, baslik) {
    if (confirm('Bu slide\'ı silmek istediğinize emin misiniz?\n\n' + baslik)) {
        document.getElementById('del-slider-' + id).submit();
    }
}
</script>

@endsection