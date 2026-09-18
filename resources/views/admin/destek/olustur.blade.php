@extends('admin._layout')

@section('title', 'Müşteri Adına Destek Talebi Aç')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.destek.index') }}">Destek</a>
    <span class="sep">/</span>
    <span class="current">Yeni Talep</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Müşteri Adına Talep Aç</h1>
        <div class="page-subtitle">Telefonda gelen veya başka kanaldan gelen talepleri sisteme manuel ekle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.destek.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px">
        <strong>Form Hataları:</strong>
        <ul style="margin:6px 0 0 18px;font-size:13px">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.destek.olustur.post') }}" method="POST">
    @csrf

    <div class="form-grid">
        {{-- SOL --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="user"></i>
                    <span>Müşteri Seçimi</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Üye <span class="required">*</span></label>
                    <input type="text" id="uye-search" placeholder="🔍 Müşteri ara..." class="form-input" autocomplete="off" style="margin-bottom:6px">
                    <select name="uyeid" id="uyeid-select" required class="form-select" size="6" style="height:auto;min-height:160px">
                        @foreach($uyeler ?? [] as $u)
                            <option value="{{ $u->id }}" data-search="{{ strtolower(($u->ad ?? '').' '.($u->soyad ?? '').' '.($u->email ?? '')) }}">
                                {{ $u->ad }} {{ $u->soyad }} — {{ $u->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="message-square"></i>
                    <span>Talep İçeriği</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Başlık <span class="required">*</span></label>
                    <input type="text" name="baslik" value="{{ old('baslik') }}" required maxlength="255" class="form-input" placeholder="Talebin kısa konusu">
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Mesaj <span class="required">*</span></label>
                    <textarea name="mesaj" rows="6" required class="form-textarea" placeholder="Talebin detayları...">{{ old('mesaj') }}</textarea>
                </div>
            </div>
        </div>

        {{-- SAĞ --}}
        <div>
            <div class="section">
                <div class="section-title">
                    <i data-lucide="settings"></i>
                    <span>Detaylar</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Departman</label>
                    <select name="departman" class="form-select">
                        <option value="Genel">Genel</option>
                        <option value="Teknik">Teknik</option>
                        <option value="Faturalama">Faturalama</option>
                        <option value="Satış">Satış</option>
                        <option value="Hosting">Hosting</option>
                        <option value="Domain">Domain</option>
                        <option value="Diğer">Diğer</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Öncelik</label>
                    <select name="oncelik" class="form-select">
                        <option value="Düşük">Düşük</option>
                        <option value="Normal" selected>Normal</option>
                        <option value="Yüksek">Yüksek</option>
                        <option value="Acil">Acil</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.destek.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Talep Oluştur</span>
        </button>
    </div>
</form>

<script>
const searchInput = document.getElementById('uye-search');
const select = document.getElementById('uyeid-select');
if (searchInput && select) {
    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        Array.from(select.options).forEach(opt => {
            if (!opt.value) { opt.hidden = false; return; }
            opt.hidden = q && !(opt.dataset.search || '').includes(q);
        });
    });
}
</script>

@endsection