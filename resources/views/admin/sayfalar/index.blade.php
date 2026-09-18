@extends('admin._layout')

@section('title', 'Sayfalar')

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Sayfalar</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="file-text"></i>
            Sayfalar
            <span class="badge badge-brand">{{ $sayfalar->total() }}</span>
        </h1>
        <div class="page-subtitle">Statik sayfalar — Hakkımızda, İletişim, vb.</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.sayfalar.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Sayfa</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $toplam     = $sayfalar->total();
    $yayinda    = $sayfalar->filter(fn($s) => (int)($s->durum ?? 0) === 1)->count();
    $taslak     = $sayfalar->filter(fn($s) => (int)($s->durum ?? 0) === 0)->count();
    $anasayfada = $sayfalar->filter(fn($s) => (int)($s->anasayfa ?? 0) === 1)->count();
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="file-text"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Sayfa</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="globe"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Yayında</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $yayinda }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="file"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Taslak</div>
            <div class="stat-card-value" style="color:var(--warning)">{{ $taslak }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="home"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Anasayfada</div>
            <div class="stat-card-value" style="color:var(--brand-dark)">{{ $anasayfada }}</div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if($sayfalar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="file-text" class="empty-state-icon"></i>
            <h4>Henüz sayfa yok</h4>
            <p>İlk statik sayfanızı ekleyin (Hakkımızda, İletişim vb.)</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.sayfalar.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Sayfa</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:64px"></th>
                        <th>Başlık / Slug</th>
                        <th style="width:140px">Anasayfa</th>
                        <th style="width:110px">Durum</th>
                        <th class="text-right" style="width:140px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sayfalar as $s)
                        @php
                            $adi = $s->adi ?? '—';
                            $seo = $s->seo ?? '';
                            $resim = $s->resim ?? null;
                            $aktif = (int)($s->durum ?? 0) === 1;
                            $anasayfada = (int)($s->anasayfa ?? 0) === 1;
                        @endphp
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $s->id }}</td>
                            <td>
                                @if($resim)
                                    <img src="{{ asset('tema/uploads/sayfalar/' . $resim) }}"
                                         alt="{{ $adi }}"
                                         style="width:48px;height:36px;object-fit:cover;border-radius:6px;border:1px solid var(--border)"
                                         onerror="this.style.display='none'">
                                @else
                                    <div style="width:48px;height:36px;border-radius:6px;background:var(--bg-subtle);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
                                        <i data-lucide="file-text" style="width:18px;height:18px"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.sayfalar.duzenle', $s->id) }}"
                                   style="font-weight:600;color:var(--text);text-decoration:none">
                                    {{ $adi }}
                                </a>
                                @if($seo)
                                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;font-family:monospace">
                                        /{{ $seo }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($anasayfada)
                                    <span class="badge badge-brand">
                                        <i data-lucide="home" style="width:11px;height:11px"></i>
                                        Anasayfada
                                    </span>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td>
                                @if($aktif)
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width:11px;height:11px"></i>
                                        Yayında
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i data-lucide="pause" style="width:11px;height:11px"></i>
                                        Taslak
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.sayfalar.duzenle', $s->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-3"></i>
                                    </a>
                                    <button type="button" class="table-action"
                                            style="color:var(--danger)"
                                            onclick="silSayfa({{ $s->id }}, '{{ addslashes($adi) }}')"
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

    @if($sayfalar->hasPages())
        <div style="margin-top:16px">{{ $sayfalar->links() }}</div>
    @endif

    {{-- SİL FORMLARI (HTML iç içe yasak) --}}
    @foreach($sayfalar as $s)
        <form id="del-sayfa-{{ $s->id }}"
              action="{{ route('admin.sayfalar.sil', $s->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silSayfa(id, baslik) {
    if (confirm('Bu sayfayı silmek istediğinize emin misiniz?\n\n' + baslik + '\n\nBu işlem geri alınamaz.')) {
        document.getElementById('del-sayfa-' + id).submit();
    }
}
</script>

@endsection