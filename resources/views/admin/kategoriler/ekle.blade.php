@extends('admin._layout')

@section('title', '➕ Yeni Kategori')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.kategoriler.index') }}">Kategoriler</a>
    <span class="sep">/</span>
    <span class="current">Yeni</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Yeni Kategori</h1>
        <div class="page-subtitle">Web paket kategorisi oluştur</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.kategoriler.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

<form action="{{ route('admin.kategoriler.eklePost') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="section">
        <div class="section-title">
            <i data-lucide="folder-plus"></i>
            <span>Kategori Bilgileri</span>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">
                    Kategori Adı <span class="required">*</span>
                </label>
                <input type="text" name="adi" value="{{ old('adi') }}" required
                       class="form-input" placeholder="Örn: Wordpress Web Site Tasarımı">
            </div>

            <div class="form-group">
                <label class="form-label">SEO Slug</label>
                <input type="text" name="seo" value="{{ old('seo') }}"
                       class="form-input" placeholder="wordpress-web-site-tasarimi">
                <small class="form-hint">URL'de görünecek kısa form. Boş bırakırsan otomatik üretilir.</small>
            </div>

            <div class="form-group form-group-full">
                <label class="form-label">Açıklama</label>
                <textarea name="aciklama" rows="4" class="form-textarea"
                          placeholder="Kategori hakkında kısa açıklama...">{{ old('aciklama') }}</textarea>
            </div>
        </div>

        <div class="form-group" style="margin-top:16px;padding:14px;background:var(--bg-subtle);border-radius:var(--radius)">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0">
                <input type="checkbox" name="durum" value="1" {{ old('durum', 1) ? 'checked' : '' }}>
                <span><strong>⚡ Aktif</strong> — Sitede görünür</span>
            </label>
        </div>
    </div>

    {{-- Çeviri tabs (eğer partial varsa) --}}
    @if(View::exists('admin.partials.ceviri-tabs'))
        @include('admin.partials.ceviri-tabs', [
            'tablo' => 'web_kategori',
            'kayit_id' => null,
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
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Kaydet</span>
        </button>
    </div>
</form>

@endsection