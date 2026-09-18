@extends('admin._layout')

@section('title', 'Hizmetler')

@push('head')
<style>
    .musteri-chip {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--brand-soft); color: var(--brand-dark);
        padding: 3px 10px; border-radius: 999px;
        font-size: 12px; font-weight: 600;
    }
    .musteri-chip i { width: 12px; height: 12px; }
    .durum-badge { font-size: 11px; padding: 3px 9px; border-radius: 4px; font-weight: 600; }
    .durum-aktif { background: rgba(16,185,129,0.15); color: #059669; }
    .durum-beklemede { background: rgba(245,158,11,0.15); color: #d97706; }
    .durum-tamamlandi { background: rgba(59,130,246,0.15); color: #2563eb; }
    .durum-iptal { background: rgba(239,68,68,0.15); color: #dc2626; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Hizmetler</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="wrench"></i>
            Hizmetler
            <span class="badge badge-brand">{{ $hizmetler->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşterilere atanmış hizmet kayıtları</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.hizmetler.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Hizmet</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

{{-- ARAMA --}}
<form method="GET" action="{{ route('admin.hizmetler.index') }}" class="section" style="margin-bottom:16px;padding:14px 16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <input type="text" name="q" value="{{ $arama ?? '' }}" class="form-input"
           placeholder="Müşteri adı, e-posta veya hizmet adı ile ara..."
           style="flex:1;min-width:280px">
    <button type="submit" class="btn btn-primary">
        <i data-lucide="search"></i>
        <span>Ara</span>
    </button>
    @if(!empty($arama))
        <a href="{{ route('admin.hizmetler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>Temizle</span>
        </a>
    @endif
</form>

@if($hizmetler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="wrench" class="empty-state-icon"></i>
            <h4>{{ !empty($arama) ? 'Sonuç bulunamadı' : 'Henüz hizmet kaydı yok' }}</h4>
            <p>{{ !empty($arama) ? 'Farklı bir arama terimi deneyin.' : 'Yukarıdaki "Yeni Hizmet" butonu ile başlayın.' }}</p>
            @if(empty($arama))
                <div style="margin-top:16px">
                    <a href="{{ route('admin.hizmetler.ekle') }}" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        <span>Yeni Hizmet</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Müşteri</th>
                        <th>Hizmet Adı</th>
                        <th style="width:110px">Tutar</th>
                        <th style="width:110px">Durum</th>
                        <th style="width:140px">Tarih</th>
                        <th style="text-align:right;width:120px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hizmetler as $h)
                        @php
                            $durum = trim((string) ($h->durum ?? 'aktif'));
                            $musteriAd = trim(($h->musteri_ad ?? '') . ' ' . ($h->musteri_soyad ?? ''));
                            if (empty($musteriAd)) $musteriAd = '—';
                        @endphp
                        <tr>
                            <td style="font-family:monospace;font-size:12px;color:var(--text-muted)">#{{ $h->id }}</td>
                            <td>
                                <span class="musteri-chip">
                                    <i data-lucide="user"></i>
                                    {{ $musteriAd }}
                                </span>
                                @if(!empty($h->musteri_email))
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:3px">{{ $h->musteri_email }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ \Illuminate\Support\Str::limit($h->baslik ?? '—', 50) }}</strong>
                                @if(!empty($h->dosya))
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:3px">
                                        <i data-lucide="paperclip" style="width:11px;height:11px;display:inline;vertical-align:middle"></i>
                                        Dosya ekli
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if(!empty($h->tutar) && is_numeric($h->tutar) && (float) $h->tutar > 0)
                                    <strong>₺{{ number_format((float) $h->tutar, 2, ',', '.') }}</strong>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="durum-badge durum-{{ $durum }}">
                                    @if($durum === 'aktif') ✓ Aktif
                                    @elseif($durum === 'beklemede') ⏳ Beklemede
                                    @elseif($durum === 'tamamlandi') ✓ Tamamlandı
                                    @elseif($durum === 'iptal') ✗ İptal
                                    @else {{ $durum }}
                                    @endif
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                @if(!empty($h->tarih))
                                    @php
                                        try { $tarihFmt = \Carbon\Carbon::parse($h->tarih)->format('d.m.Y H:i'); }
                                        catch (\Throwable $e) { $tarihFmt = $h->tarih; }
                                    @endphp
                                    {{ $tarihFmt }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="text-align:right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.hizmetler.duzenle', $h->id) }}"
                                       class="table-action" title="Düzenle">
                                        <i data-lucide="edit-3"></i>
                                    </a>
                                    <button type="button" class="table-action"
                                            title="Sil" style="color:var(--danger)"
                                            onclick="silHizmet({{ $h->id }}, {{ json_encode($h->baslik ?? '') }});">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($hizmetler->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:13px;color:var(--text-muted)">
                    {{ $hizmetler->firstItem() ?? 0 }} – {{ $hizmetler->lastItem() ?? 0 }} / Toplam
                    <strong style="color:var(--text)">{{ $hizmetler->total() }}</strong>
                </div>
                {{ $hizmetler->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- SİL FORMLARI --}}
    @foreach($hizmetler as $h)
        <form id="del-hizmet-{{ $h->id }}"
              action="{{ route('admin.hizmetler.sil', $h->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silHizmet(id, baslik) {
    if (confirm('Bu hizmeti silmek istediğinize emin misiniz?\n\nHizmet: ' + baslik + '\n\nBu işlem geri alınamaz.')) {
        document.getElementById('del-hizmet-' + id).submit();
    }
}
</script>

@endsection