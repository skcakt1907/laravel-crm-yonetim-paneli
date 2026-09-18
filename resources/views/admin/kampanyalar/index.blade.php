@extends('admin._layout')

@section('title', 'Kampanyalar')

@push('head')
<style>
    .k-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: all 0.2s ease;
        height: 100%;
    }
    .k-card:hover {
        border-color: var(--brand-medium);
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .k-card-media {
        position: relative;
        aspect-ratio: 16/9;
        background: var(--bg-subtle);
        overflow: hidden;
    }
    .k-card-media img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }
    .k-card-media .empty-bg {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--brand-soft), rgba(184,182,46,0.04));
        color: var(--brand-dark);
        font-size: 48px;
    }
    .k-card-media .badge-overlay {
        position: absolute; top: 10px; right: 10px;
        display: flex; gap: 6px;
    }
    .k-card-media .indirim-tag {
        position: absolute; top: 10px; left: 10px;
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: #fff;
        font-weight: 700;
        font-size: 13px;
        padding: 4px 10px;
        border-radius: 99px;
        box-shadow: 0 4px 12px rgba(239,68,68,0.4);
    }

    .k-card-body {
        padding: 14px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .k-card-title {
        font-weight: 700;
        font-size: 15px;
        color: var(--text);
        line-height: 1.3;
    }
    .k-card-desc {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
    }
    .k-card-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 11.5px;
        color: var(--text-muted);
        margin-top: auto;
        padding-top: 10px;
        border-top: 1px dashed var(--border);
    }

    .k-card-actions {
        display: flex;
        gap: 6px;
        padding: 10px 14px;
        border-top: 1px solid var(--border);
        background: var(--bg-subtle);
    }
    .k-card-actions .btn { flex: 1; padding: 7px 10px; font-size: 12.5px; }

    /* Fırsat paketler */
    .firsat-toggle {
        background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(245,158,11,0.02));
        border: 1px solid rgba(245,158,11,0.25);
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Kampanyalar</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="megaphone"></i>
            Kampanyalar
            <span class="badge badge-brand">{{ count($kampanyalar ?? []) }}</span>
        </h1>
        <div class="page-subtitle">İndirimler, fırsatlar ve özel teklifleri yönetin</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.kampanyalar.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Kampanya</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $toplam   = count($kampanyalar ?? []);
    $aktif    = 0;
    $suresiBitenler = 0;
    $aktifSimdi = 0;
    $bugunCarbon = now();

    foreach (($kampanyalar ?? []) as $k) {
        if ((int)($k->durum ?? 0) === 1) $aktif++;

        $bitisCarbon = null;
        $baslangicCarbon = null;
        try { if (!empty($k->baslangic_tarihi)) $baslangicCarbon = \Carbon\Carbon::parse($k->baslangic_tarihi); } catch (\Throwable $e) {}
        try { if (!empty($k->bitis_tarihi)) $bitisCarbon = \Carbon\Carbon::parse($k->bitis_tarihi); } catch (\Throwable $e) {}

        if ($bitisCarbon && $bitisCarbon->isPast()) {
            $suresiBitenler++;
        } elseif ((int)($k->durum ?? 0) === 1) {
            if ((!$baslangicCarbon || $baslangicCarbon->lte($bugunCarbon))
                && (!$bitisCarbon || $bitisCarbon->gte($bugunCarbon))) {
                $aktifSimdi++;
            }
        }
    }
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="megaphone"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Kampanya</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="zap"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Şu An Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifSimdi }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Yayında & süre içinde</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Yayında</div>
            <div class="stat-card-value" style="color:var(--brand-dark)">{{ $aktif }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Durum: Aktif</div>
        </div>
    </div>

    <div class="stat-card" style="@if($suresiBitenler > 0)border-color:rgba(239,68,68,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Süresi Doldu</div>
            <div class="stat-card-value" style="@if($suresiBitenler > 0)color:var(--danger)@endif">{{ $suresiBitenler }}</div>
        </div>
    </div>
</div>

{{-- KAMPANYALAR GRID --}}
@if(count($kampanyalar ?? []) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="megaphone" class="empty-state-icon"></i>
            <h4>Henüz kampanya yok</h4>
            <p>İlk kampanyanızı ekleyerek müşterilerinize özel fırsatlar sunun.</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.kampanyalar.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Kampanya</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($kampanyalar as $k)
            @php
                $baslik = $k->baslik ?? $k->adi ?? $k->ad ?? '—';
                $aciklama = strip_tags($k->aciklama ?? '');
                $resim = $k->resim ?? null;
                $indirim = (float)($k->indirim ?? 0);
                $aktifK = (int)($k->durum ?? 0) === 1;

                // Tarihler
                $baslangicFmt = null;
                $bitisFmt = null;
                $kalanGun = null;
                $durum = null;

                try { if (!empty($k->baslangic_tarihi)) $baslangicFmt = \Carbon\Carbon::parse($k->baslangic_tarihi)->format('d.m.Y'); }
                catch (\Throwable $e) {}

                try {
                    if (!empty($k->bitis_tarihi)) {
                        $bitisCarbon = \Carbon\Carbon::parse($k->bitis_tarihi);
                        $bitisFmt = $bitisCarbon->format('d.m.Y');
                        $kalanGun = (int) floor(now()->diffInDays($bitisCarbon, false));

                        if ($kalanGun < 0)        $durum = 'bitti';
                        elseif ($kalanGun <= 7)   $durum = 'son-gunler';
                        else                       $durum = 'aktif';
                    }
                } catch (\Throwable $e) {}
            @endphp

            <div class="k-card">
                <div class="k-card-media">
                    @if($resim)
                        <img src="{{ asset('tema/uploads/kampanyalar/' . $resim) }}"
                             alt="{{ $baslik }}"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="empty-bg" style="display:none">
                            <i data-lucide="image"></i>
                        </div>
                    @else
                        <div class="empty-bg">
                            <i data-lucide="megaphone"></i>
                        </div>
                    @endif

                    @if($indirim > 0)
                        <span class="indirim-tag">
                            <i data-lucide="percent" style="width:11px;height:11px;display:inline;vertical-align:middle"></i>
                            %{{ rtrim(rtrim(number_format($indirim, 2, ',', '.'), '0'), ',') }}
                        </span>
                    @endif

                    <div class="badge-overlay">
                        @if(!$aktifK)
                            <span class="badge badge-neutral">
                                <i data-lucide="pause" style="width:11px;height:11px"></i>
                                Pasif
                            </span>
                        @elseif($durum === 'bitti')
                            <span class="badge badge-danger">
                                <i data-lucide="x-circle" style="width:11px;height:11px"></i>
                                Bitti
                            </span>
                        @elseif($durum === 'son-gunler')
                            <span class="badge badge-warning">
                                <i data-lucide="alert-triangle" style="width:11px;height:11px"></i>
                                {{ $kalanGun }} gün
                            </span>
                        @elseif($durum === 'aktif')
                            <span class="badge badge-success">
                                <i data-lucide="check" style="width:11px;height:11px"></i>
                                Aktif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="k-card-body">
                    <div class="k-card-title">{{ $baslik }}</div>
                    @if($aciklama)
                        <div class="k-card-desc">
                            {{ \Illuminate\Support\Str::limit($aciklama, 100) }}
                        </div>
                    @endif

                    <div class="k-card-meta">
                        @if($baslangicFmt || $bitisFmt)
                            <span style="display:inline-flex;align-items:center;gap:4px">
                                <i data-lucide="calendar" style="width:11px;height:11px"></i>
                                {{ $baslangicFmt ?? '—' }}
                                @if($bitisFmt) → {{ $bitisFmt }} @endif
                            </span>
                        @endif

                        @if(!empty($k->link))
                            <span style="display:inline-flex;align-items:center;gap:4px;margin-left:auto">
                                <i data-lucide="link" style="width:11px;height:11px"></i>
                                Link
                            </span>
                        @endif
                    </div>
                </div>

                <div class="k-card-actions">
                    <a href="{{ route('admin.kampanyalar.duzenle', $k->id) }}" class="btn btn-primary btn-sm">
                        <i data-lucide="edit-3"></i>
                        <span>Düzenle</span>
                    </a>
                    @if(!empty($k->link))
                        <a href="{{ $k->link }}" target="_blank" class="btn btn-secondary btn-sm" title="Linki aç">
                            <i data-lucide="external-link"></i>
                        </a>
                    @endif
                    <button type="button" class="btn btn-ghost btn-sm"
                            style="color:var(--danger);flex:0 0 auto;padding:7px 10px"
                            onclick="silKampanya({{ $k->id }}, '{{ addslashes($baslik) }}')">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- SİL FORMLARI (HTML iç içe form yasak) --}}
    @foreach($kampanyalar as $k)
        <form id="del-kampanya-{{ $k->id }}"
              action="{{ route('admin.kampanyalar.sil', $k->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

{{-- FIRSAT PAKETLER (yazilimlar tablosundan, read-only) --}}
@if(!empty($firsatPaketler) && count($firsatPaketler) > 0)
    <div class="section firsat-toggle" style="margin-top:24px">
        <div class="section-title">
            <i data-lucide="tag"></i>
            <span>İndirimli Paketler ({{ count($firsatPaketler) }})</span>
            <span style="margin-left:auto;font-size:11px;color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0">
                Web paketlerdeki aktif fırsatlar
            </span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
            @foreach($firsatPaketler as $p)
                @php
                    $pIndirim = (float)($p->indirim ?? 0);
                    $pFiyat = (float)($p->fiyat ?? $p->tutar ?? 0);
                    $pResim = $p->resim ?? null;
                @endphp
                <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px;display:flex;gap:12px;align-items:center">
                    @if($pResim)
                        <img src="{{ asset('tema/uploads/paketler/' . $pResim) }}"
                             alt="{{ $p->adi }}"
                             style="width:48px;height:48px;border-radius:8px;object-fit:cover;flex-shrink:0"
                             onerror="this.style.display='none'">
                    @endif
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:13.5px;line-height:1.3;color:var(--text)">
                            {{ \Illuminate\Support\Str::limit($p->adi ?? '—', 40) }}
                        </div>
                        @if($pFiyat > 0)
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
                                ₺{{ number_format($pFiyat, 0, ',', '.') }}
                            </div>
                        @endif
                    </div>
                    @if($pIndirim > 0)
                        <span style="background:rgba(239,68,68,0.12);color:var(--danger);font-weight:700;font-size:11.5px;padding:3px 8px;border-radius:6px;flex-shrink:0">
                            %{{ rtrim(rtrim(number_format($pIndirim, 2, ',', '.'), '0'), ',') }}
                        </span>
                    @elseif(!empty($p->firsat))
                        <span style="background:rgba(245,158,11,0.12);color:var(--warning);font-weight:700;font-size:11.5px;padding:3px 8px;border-radius:6px;flex-shrink:0">
                            Fırsat
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
        <small class="form-help" style="margin-top:12px;display:block">
            <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
            Bu paketler "Paketler" modülünden yönetilir. Burada sadece gösterim amaçlıdır.
        </small>
    </div>
@endif

<script>
function silKampanya(id, baslik) {
    if (confirm('Bu kampanyayı silmek istediğinize emin misiniz?\n\n' + baslik)) {
        document.getElementById('del-kampanya-' + id).submit();
    }
}
</script>

@endsection