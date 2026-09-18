@extends('admin._layout')

@section('title', 'Diller')

@push('head')
<style>
    .dil-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 18px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 14px;
        position: relative;
    }
    .dil-card:hover {
        border-color: var(--brand-medium);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .dil-card.anadil {
        background: linear-gradient(135deg, var(--brand-soft), transparent);
        border-color: var(--brand);
    }

    .dil-flag {
        font-size: 42px;
        line-height: 1;
        flex-shrink: 0;
        width: 56px; height: 56px;
        display: flex; align-items: center; justify-content: center;
        background: var(--bg-subtle);
        border-radius: var(--radius-md);
    }
    .dil-info {
        flex: 1;
        min-width: 0;
    }
    .dil-name {
        font-weight: 700;
        font-size: 16px;
        color: var(--text);
        margin-bottom: 4px;
    }
    .dil-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--text-muted);
        flex-wrap: wrap;
    }
    .dil-code {
        font-family: monospace;
        background: var(--brand-soft);
        color: var(--brand-dark);
        padding: 2px 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 11.5px;
        text-transform: uppercase;
    }
    body.theme-dark .dil-code { color: var(--brand); }

    .dil-actions {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }

    .anadil-badge {
        position: absolute;
        top: 8px; right: 8px;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #000;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 99px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
</style>
@endpush

@php
    // $diller hem Paginator hem Collection olabilir - güvenli hesapla
    $isPaginated = method_exists($diller, 'total');
    $toplam      = $isPaginated ? $diller->total() : count($diller);
    $aktifSay    = 0;
    $anadilSay   = 0;
    foreach ($diller as $d) {
        if (!isset($d->durum) || (int)$d->durum === 1) $aktifSay++;
        if (isset($d->anadil) && (int)$d->anadil === 1) $anadilSay++;
    }
@endphp

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Diller</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="languages"></i>
            Dil Yönetimi
            <span class="badge badge-brand">{{ $toplam }}</span>
        </h1>
        <div class="page-subtitle">Site içeriğinin desteklediği dilleri yönetin</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.diller.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Dil</span>
        </a>
    </div>
</div>

{{-- STAT --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="languages"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Dil</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifSay }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="star"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Anadil</div>
            <div class="stat-card-value" style="color:var(--brand-dark)">{{ $anadilSay }}</div>
        </div>
    </div>
</div>

{{-- DİL KARTLARI --}}
@if(empty($diller) || (is_object($diller) && method_exists($diller, 'isEmpty') ? $diller->isEmpty() : count($diller) === 0))
    <div class="section">
        <div class="empty-state">
            <i data-lucide="languages" class="empty-state-icon"></i>
            <h4>Henüz dil tanımlanmamış</h4>
            <p>İlk dilinizi ekleyin (Türkçe, İngilizce vb.)</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.diller.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Dil</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
        @foreach($diller as $d)
            @php
                $dilAd  = $d->ad ?? $d->adi ?? $d->isim ?? $d->name ?? $d->dil_adi ?? '—';
                $dilKod = $d->kod ?? $d->dil_kodu ?? $d->code ?? '—';
                $bayrak = $d->bayrak ?? null;
                $aktif  = !isset($d->durum) || (int)$d->durum === 1;
                $anadil = isset($d->anadil) && (int)$d->anadil === 1;

                // Bayrak fallback
                $bayrakMap = [
                    'tr' => '🇹🇷', 'en' => '🇬🇧', 'ar' => '🇸🇦',
                    'de' => '🇩🇪', 'fr' => '🇫🇷', 'es' => '🇪🇸',
                    'ru' => '🇷🇺', 'it' => '🇮🇹', 'nl' => '🇳🇱',
                    'jp' => '🇯🇵', 'zh' => '🇨🇳', 'cn' => '🇨🇳', 'pt' => '🇵🇹',
                ];
                $bayrakGoster = $bayrak ?: ($bayrakMap[strtolower($dilKod)] ?? '🌐');
            @endphp
            <div class="dil-card {{ $anadil ? 'anadil' : '' }}">
                @if($anadil)
                    <span class="anadil-badge">
                        <i data-lucide="star" style="width:10px;height:10px"></i>
                        ANADİL
                    </span>
                @endif

                <div class="dil-flag">{{ $bayrakGoster }}</div>

                <div class="dil-info">
                    <div class="dil-name">{{ $dilAd }}</div>
                    <div class="dil-meta">
                        <span class="dil-code">{{ $dilKod }}</span>
                        @if($aktif)
                            <span style="color:var(--success);display:inline-flex;align-items:center;gap:3px">
                                <i data-lucide="check" style="width:11px;height:11px"></i>
                                Aktif
                            </span>
                        @else
                            <span style="color:var(--text-muted);display:inline-flex;align-items:center;gap:3px">
                                <i data-lucide="pause" style="width:11px;height:11px"></i>
                                Pasif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="dil-actions">
                    {{-- Durum toggle --}}
                    <button type="button" class="table-action"
                            style="color:{{ $aktif ? 'var(--warning)' : 'var(--success)' }}"
                            onclick="dilDurum({{ $d->id }})"
                            title="{{ $aktif ? 'Pasif yap' : 'Aktif yap' }}">
                        <i data-lucide="{{ $aktif ? 'pause' : 'play' }}"></i>
                    </button>

                    {{-- Sil (anadil silinemez) --}}
                    @if(!$anadil)
                        <button type="button" class="table-action"
                                style="color:var(--danger)"
                                onclick="silDil({{ $d->id }}, '{{ addslashes($dilAd) }}')"
                                title="Sil">
                            <i data-lucide="trash-2"></i>
                        </button>
                    @else
                        <button type="button" class="table-action"
                                style="color:var(--text-muted);cursor:not-allowed;opacity:0.4"
                                disabled
                                title="Anadil silinemez">
                            <i data-lucide="lock"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($isPaginated && $diller->hasPages())
        <div style="margin-top:16px">{{ $diller->links() }}</div>
    @endif

    {{-- FORMLAR --}}
    @foreach($diller as $d)
        <form id="durum-dil-{{ $d->id }}"
              action="{{ route('admin.diller.durum', $d->id) }}"
              method="POST" style="display:none">
            @csrf
        </form>
        @php $anadilCheck = isset($d->anadil) && (int)$d->anadil === 1; @endphp
        @if(!$anadilCheck)
            <form id="del-dil-{{ $d->id }}"
                  action="{{ route('admin.diller.sil', $d->id) }}"
                  method="POST" style="display:none">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
@endif

<script>
function dilDurum(id) {
    document.getElementById('durum-dil-' + id).submit();
}

function silDil(id, ad) {
    if (confirm('Bu dili silmek istediğinize emin misiniz?\n\n' + ad + '\n\nBu dile ait çeviriler de etkilenebilir.')) {
        document.getElementById('del-dil-' + id).submit();
    }
}
</script>

@endsection