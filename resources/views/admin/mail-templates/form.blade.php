@extends('admin._layout')

@section('title', $template ? 'Şablonu Düzenle' : 'Yeni Mail Şablonu')

@push('head')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .var-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .var-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        padding: 4px 10px;
        border-radius: 6px;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all .12s ease;
    }
    .var-chip:hover {
        border-color: var(--brand);
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')

@php $isEdit = (bool)$template; @endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.mail-templates.index') }}">Mail Şablonları</a>
    <span class="sep">/</span>
    <span class="current">{{ $isEdit ? $template->name : 'Yeni' }}</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="{{ $isEdit ? 'edit-3' : 'plus-circle' }}"></i>
            {{ $isEdit ? 'Şablonu Düzenle' : 'Yeni Mail Şablonu' }}
        </h1>
        <div class="page-subtitle">
            @if($isEdit)
                <code style="font-family:'JetBrains Mono',monospace">{{ $template->slug }}</code>
            @else
                Sipariş, kayıt veya bildirim mailleri için yeni içerik oluştur
            @endif
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.mail-templates.index') }}" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ $isEdit ? route('admin.mail-templates.update', $template->id) : route('admin.mail-templates.store') }}"
      method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="form-grid">
        {{-- SOL: Ana içerik --}}
        <div>
            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="info"></i>
                    <span>Şablon Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Şablon Adı <span class="required">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}" required
                           class="form-input" maxlength="150"
                           placeholder="Örn: Sipariş Onayı">
                    <small class="form-help">Yöneticiler için açıklayıcı ad — slug otomatik üretilir</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Mail Konusu <span class="required">*</span></label>
                    <input type="text" name="subject" value="{{ old('subject', $template->subject ?? '') }}" required
                           class="form-input" maxlength="200"
                           placeholder="Örn: Siparişiniz Onaylandı — #@{{order_id}}">
                    <small class="form-help">Mail kutusunda görünecek konu satırı</small>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="file-text"></i>
                    <span>Mail İçeriği</span>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <textarea name="body" id="bodyEditor" rows="14"
                              class="form-textarea">{{ old('body', $template->body ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- SAĞ: Durum + Değişkenler --}}
        <div>
            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="settings"></i>
                    <span>Yayın Durumu</span>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="checkbox" name="aktif" value="1"
                               {{ old('aktif', $isEdit ? $template->aktif : 1) ? 'checked' : '' }}
                               style="width:18px;height:18px">
                        <div>
                            <div style="font-weight:600;font-size:14px">Şablon Aktif</div>
                            <div style="font-size:12px;color:var(--text-muted)">Pasif şablonlar sistem tarafından kullanılmaz</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="braces"></i>
                    <span>Değişkenler</span>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin:0 0 8px">
                    Mail içeriğinde aşağıdaki değişkenleri kullanabilirsin:
                </p>
                <div class="var-list">
                    <span class="var-chip" onclick="copyVar(this)">@{{ad}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{soyad}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{tam_ad}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{musteri_adi}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{email}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{telefon}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{firma}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{firma_email}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{firma_telefon}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{site_url}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{tarih}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{saat}}</span>
                    <span class="var-chip" onclick="copyVar(this)">@{{yil}}</span>
                </div>
                <small class="form-help">Tıklayarak panoya kopyala. Not: <code>@{{tutar}}</code> ve <code>@{{order_id}}</code> yalnızca fatura/sipariş maillerinde dolar; toplu mailde boş kalır.</small>
            </div>

            @if($isEdit)
            <div class="section" style="background:rgba(184,182,46,.05);border-color:var(--brand-soft)">
                <div class="section-title">
                    <i data-lucide="clock"></i>
                    <span>Bilgi</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="lbl">ID</span>
                        <span class="val">#{{ $template->id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="lbl">Slug</span>
                        <span class="val" style="font-family:'JetBrains Mono',monospace;font-size:11px">{{ $template->slug }}</span>
                    </div>
                    @if(!empty($template->created_at))
                    <div class="info-item">
                        <span class="lbl">Oluşturuldu</span>
                        @php
                            $crTarih = '—';
                            try { $crTarih = \Carbon\Carbon::parse($template->created_at)->format('d.m.Y H:i'); } catch(\Throwable $e){}
                        @endphp
                        <span class="val">{{ $crTarih }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.mail-templates.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>{{ $isEdit ? 'Güncelle' : 'Şablonu Kaydet' }}</span>
        </button>
    </div>
</form>

<script>
function copyVar(el){
    var txt = el.textContent.trim();
    navigator.clipboard.writeText(txt).then(function(){
        var oldBg = el.style.background;
        el.style.background = '#10b981';
        el.style.color = '#fff';
        setTimeout(function(){
            el.style.background = '';
            el.style.color = '';
        }, 600);
    });
}

// TinyMCE init
if (typeof tinymce !== 'undefined') {
    tinymce.init({
        selector: '#bodyEditor',
        height: 380,
        menubar: false,
        plugins: 'lists link image table code preview',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link image table | code preview',
        branding: false,
        promotion: false,
        skin: (document.body.classList.contains('theme-dark') ? 'oxide-dark' : 'oxide'),
        content_css: (document.body.classList.contains('theme-dark') ? 'dark' : 'default')
    });
}
</script>

@endsection