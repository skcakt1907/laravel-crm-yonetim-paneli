@extends('admin._layout')

@section('title', '🗂️ Kategoriler')

@push('head')
<style>
    .kat-table { width:100%; border-collapse:collapse; background:var(--surface);
        border-radius:var(--radius-lg); overflow:hidden; }
    .kat-table thead tr { background:var(--bg-subtle); }
    .kat-table th { text-align:left; padding:12px 14px; font-size:11px; text-transform:uppercase;
        letter-spacing:.05em; color:var(--text-muted); font-weight:700; }
    .kat-table tbody tr { border-top:1px solid var(--border); transition:background .15s; }
    .kat-table tbody tr:hover { background:var(--bg-subtle); }
    .kat-table td { padding:12px 14px; font-size:13px; color:var(--text); vertical-align:middle; }
    .kat-actions { display:inline-flex; gap:4px; align-items:center; }
    .kat-icon-btn { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center;
        border-radius:6px; background:transparent; border:1px solid var(--border); cursor:pointer;
        transition:all .15s; color:var(--text-muted); }
    .kat-icon-btn:hover { background:var(--bg-subtle); color:var(--text); }
    .kat-icon-btn.danger:hover { background:rgba(239,68,68,.12); color:var(--danger); border-color:var(--danger); }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Kategoriler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🗂️ Kategoriler</h1>
        <div class="page-subtitle">Web paket kategorileri yönetimi</div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.kategoriler.ekle'))
            <a href="{{ route('admin.kategoriler.ekle') }}" class="btn btn-primary">
                <i data-lucide="plus"></i>
                <span>Yeni Kategori</span>
            </a>
        @endif
    </div>
</div>

@if(empty($kategoriler) || $kategoriler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="folder-x" class="empty-state-icon"></i>
            <h4>Kategori yok</h4>
            <p>Henüz hiç kategori eklenmemiş.</p>
            @if(Route::has('admin.kategoriler.ekle'))
                <a href="{{ route('admin.kategoriler.ekle') }}" class="btn btn-primary btn-sm" style="margin-top:12px">
                    <i data-lucide="plus"></i>
                    <span>İlk Kategoriyi Ekle</span>
                </a>
            @endif
        </div>
    </div>
@else
    <div class="section" style="padding:0;overflow-x:auto">
        <table class="kat-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Adı</th>
                    <th style="width:120px">Durum</th>
                    <th style="text-align:right;width:120px">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kategoriler as $item)
                    @php
                        $adi = $item->kategori_adi
                            ?? $item->adi
                            ?? $item->ad
                            ?? $item->baslik
                            ?? $item->kod
                            ?? $item->name
                            ?? '—';
                        $durum = (int)($item->durum ?? 0);
                    @endphp
                    <tr>
                        <td style="color:var(--text-muted);font-size:11px">#{{ $item->id }}</td>
                        <td><strong>{{ $adi }}</strong></td>
                        <td>
                            @if($durum === 1)
                                <span class="badge badge-success">✓ Aktif</span>
                            @else
                                <span class="badge badge-neutral">⏸ Pasif</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <div class="kat-actions">
                                @if(Route::has('admin.kategoriler.duzenle'))
                                    <a href="{{ route('admin.kategoriler.duzenle', $item->id) }}"
                                       class="kat-icon-btn" title="Düzenle">
                                        <i data-lucide="edit-2" style="width:14px;height:14px"></i>
                                    </a>
                                @endif
                                @if(Route::has('admin.kategoriler.sil'))
                                    <button type="button"
                                            onclick="document.getElementById('kat-sil-{{ $item->id }}').submit()"
                                            class="kat-icon-btn danger" title="Sil">
                                        <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(method_exists($kategoriler, 'hasPages') && $kategoriler->hasPages())
        <div style="margin-top:16px">
            {{ $kategoriler->withQueryString()->links() }}
        </div>
    @endif

    {{-- Gizli silme formları (iç içe form yasak) --}}
    @if(Route::has('admin.kategoriler.sil'))
        @foreach($kategoriler as $item)
            <form id="kat-sil-{{ $item->id }}"
                  action="{{ route('admin.kategoriler.sil', $item->id) }}"
                  method="POST" style="display:none"
                  onsubmit="return confirm('Bu kategoriyi silmek istediğine emin misin?')">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endif
@endif

@endsection