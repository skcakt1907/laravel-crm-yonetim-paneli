@extends('admin._layout')

@section('title', '✏️ Kategori Düzenle')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.kategoriler.index') }}">Kategoriler</a>
    <span class="sep">/</span>
    <span class="current">{{ $kategori->adi ?? $kategori->kategori_adi ?? 'Düzenle' }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            ✏️ {{ $kategori->adi ?? $kategori->kategori_adi ?? 'Kategori' }}
        </h1>
        <div class="page-subtitle">
            ID #{{ $kategori->id }} · {{ ($kategori->durum ?? 0) == 1 ? 'Aktif' : 'Pasif' }}
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.kategoriler.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.kategoriler.duzenlePost', $kategori->id) }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="section">
        <div class="section-title">
            <i data-lucide="folder"></i>
            <span>Kategori Bilgileri</span>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">
                    Kategori Adı <span class="required">*</span>
                </label>
                <input type="text" name="adi"
                       value="{{ old('adi', $kategori->adi ?? $kategori->kategori_adi ?? '') }}"
                       required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">SEO Slug</label>
                <input type="text" name="seo"
                       value="{{ old('seo', $kategori->seo ?? '') }}"
                       class="form-input">
                <small class="form-hint">URL'de görünecek kısa form.</small>
            </div>

            <div class="form-group form-group-full">
                <label class="form-label">Açıklama</label>
                <textarea name="aciklama" rows="4" class="form-textarea">{{ old('aciklama', $kategori->aciklama ?? '') }}</textarea>
            </div>
        </div>

        <div class="form-group" style="margin-top:16px;padding:14px;background:var(--bg-subtle);border-radius:var(--radius)">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0">
                <input type="checkbox" name="durum" value="1"
                       {{ old('durum', $kategori->durum ?? 1) ? 'checked' : '' }}>
                <span><strong>⚡ Aktif</strong> — Sitede görünür</span>
            </label>
        </div>
    </div>

    {{-- Çeviri tabs --}}
    @if(View::exists('admin.partials.ceviri-tabs'))
        @include('admin.partials.ceviri-tabs', [
            'tablo' => 'web_kategori',
            'kayit_id' => $kategori->id ?? null,
            'fields' => [
                'adi'      => ['label' => '📛 Kategori Adı', 'type' => 'text'],
                'aciklama' => ['label' => '📄 Açıklama', 'type' => 'textarea', 'rows' => 4],
            ],
        ])
    @endif

    <div class="form-actions">
        <a href="{{ route('admin.kategoriler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        @if(Route::has('admin.kategoriler.sil'))
            <button type="button"
                    onclick="document.getElementById('kategoriSilForm').submit()"
                    class="btn btn-danger">
                <i data-lucide="trash-2"></i>
                <span>Sil</span>
            </button>
        @endif
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Güncelle</span>
        </button>
    </div>
</form>

{{-- Gizli silme formu (iç içe form yasak) --}}
@if(Route::has('admin.kategoriler.sil'))
    <form id="kategoriSilForm"
          action="{{ route('admin.kategoriler.sil', $kategori->id) }}"
          method="POST" style="display:none"
          onsubmit="return confirm('Bu kategoriyi silmek istediğine emin misin?\n\nBağlı paketler varsa onlar da etkilenir!')">
        @csrf @method('DELETE')
    </form>
@endif

@endsection