@extends('admin._layout')

@section('title', $kayit->baslik . ' — Şifre Kasası')

@section('content')
<div class="breadcrumb">
    <a href="{{ route('admin.crm.ozel-kayitlar.index') }}">Şifre Kasası</a>
    <span class="sep">/</span>
    <span class="current">{{ $kayit->baslik }}</span>
</div>

<div class="profile-header" style="margin-bottom:18px">
    <div class="profile-avatar">{{ strtoupper(mb_substr($kayit->baslik ?: 'M', 0, 1)) }}</div>
    <div class="profile-info">
        <h2>{{ $kayit->baslik }}
            <span class="badge badge-brand" style="margin-left:8px">{{ $kayit->hesaplar->count() }} hesap</span>
        </h2>
        <div class="meta">
            @if($kayit->firma){{ $kayit->firma }}@endif
            @if($kayit->musteri) · <i data-lucide="link" style="width:12px;height:12px"></i> CRM: {{ $kayit->musteri->adi }}@endif
        </div>
        <div class="meta-row">
            @if($kayit->email)<span><i data-lucide="mail" style="width:13px;height:13px"></i> {{ $kayit->email }}</span>@endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.crm.ozel-kayitlar.edit', $kayit->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="edit-2"></i> <span>Bilgileri Düzenle</span></a>
    </div>
</div>

{{-- Yeni hesap ekle --}}
<div class="section" style="padding:18px 20px;margin-bottom:16px">
    <div class="section-title" style="margin-bottom:14px"><i data-lucide="plus-circle"></i> <span>Yeni Hesap / Şifre Ekle</span></div>
    <form action="{{ route('admin.crm.ozel-kayitlar.hesap.ekle', $kayit->id) }}" method="POST">
        @csrf
        {{-- Bölüm seçimi — platform listesi buna göre değişir --}}
        <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">Bölüm <span class="required">*</span></label>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @foreach(\App\Models\CRM\SosyalMedyaHesap::BOLUMLER as $kod => $ad)
                    <label class="sm-bolum-sec" data-bolum="{{ $kod }}"
                           style="display:flex;align-items:center;gap:7px;cursor:pointer;padding:9px 14px;border:1px solid var(--border);border-radius:var(--radius-md);font-size:13px;font-weight:600">
                        <input type="radio" name="bolum" value="{{ $kod }}" {{ $loop->first ? 'checked' : '' }}
                               onchange="smBolumDegisti(this.value)">
                        <i data-lucide="{{ $kod === 'web_sitesi' ? 'globe' : 'share-2' }}" style="width:15px;height:15px"></i>
                        <span>{{ $ad }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="form-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            <div class="form-group">
                <label class="form-label">Platform <span class="required">*</span></label>
                <div class="sm-dropdown" id="smPlatformDropdown">
                    <button type="button" class="sm-dropdown-toggle form-input" onclick="smDropdownToggle(event)">
                        <span class="sm-dropdown-label is-placeholder" data-placeholder="Seçiniz...">Seçiniz...</span>
                        <i data-lucide="chevron-down" class="sm-dropdown-caret"></i>
                    </button>
                    <div class="sm-dropdown-menu">
                        @foreach(\App\Models\CRM\SosyalMedyaHesap::PLATFORMLAR as $bolumKodu => $liste)
                            @foreach($liste as $ad => $ikon)
                                <div class="sm-dropdown-item" data-bolum="{{ $bolumKodu }}" data-value="{{ $ad }}"
                                     onclick="smDropdownPick(this)"
                                     style="{{ $bolumKodu === 'sosyal_medya' ? '' : 'display:none' }}">
                                    <i data-lucide="{{ $ikon }}" style="width:15px;height:15px"></i> {{ $ad }}
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                {{-- Seçilen değer buraya yazılır; "Diğer" ise gizlenir, serbest input devreye girer --}}
                <input type="hidden" name="platform" id="smPlatformValue" required>
                <input type="text" id="smPlatformDiger" class="form-input" placeholder="Platform adını yazın..." style="display:none;margin-top:8px"
                       oninput="document.getElementById('smPlatformValue').value=this.value">
            </div>
            <div class="form-group">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" class="form-input" placeholder="@kullanici veya hesap adı">
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <input type="text" name="sifre" class="form-input" placeholder="Şifre" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" data-form-type="other">
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="save"></i> <span>Hesabı Ekle</span></button>
        </div>
    </form>
</div>

{{-- Mevcut hesaplar — BÖLÜMLERE AYRILMIŞ --}}
@php
    $bolumler = \App\Models\CRM\SosyalMedyaHesap::BOLUMLER;
    $gruplu   = $kayit->hesaplar->groupBy(fn($h) => $h->bolum ?: 'sosyal_medya');
@endphp

@forelse($bolumler as $bolumKodu => $bolumAdi)
    @php $liste = $gruplu->get($bolumKodu, collect()); @endphp
    @continue($liste->isEmpty())

    <div class="section-title" style="margin:22px 0 12px">
        <i data-lucide="{{ $bolumKodu === 'web_sitesi' ? 'globe' : 'share-2' }}"></i>
        <span>{{ $bolumAdi }}</span>
        <span class="badge badge-brand" style="margin-left:6px">{{ $liste->count() }}</span>
    </div>

    @foreach($liste as $h)
    <div class="section" style="padding:0;margin-bottom:12px;border-left:4px solid {{ $bolumKodu === 'web_sitesi' ? 'var(--info)' : 'var(--brand)' }}">
        <form action="{{ route('admin.crm.ozel-kayitlar.hesap.guncelle', ['id' => $kayit->id, 'hesapId' => $h->id]) }}" method="POST">
            @csrf @method('PUT')
            <div style="padding:14px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);flex-wrap:wrap;gap:8px">
                <div style="font-size:15px;font-weight:700">
                    <i data-lucide="{{ $bolumKodu === 'web_sitesi' ? 'globe' : 'at-sign' }}" style="width:15px;height:15px"></i>
                    <input type="text" name="platform" value="{{ $h->platform }}" required
                           style="border:none;background:transparent;font-size:15px;font-weight:700;color:var(--text);width:auto;min-width:120px">
                </div>
                <div style="display:flex;gap:6px;align-items:center">
                    {{-- Hesabın bölümü buradan da değiştirilebilir --}}
                    <select name="bolum" class="form-select" style="width:auto;font-size:12px;padding:6px 9px" title="Bölüm">
                        @foreach($bolumler as $k => $a)
                            <option value="{{ $k }}" {{ ($h->bolum ?: 'sosyal_medya') === $k ? 'selected' : '' }}>{{ $a }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm" title="Bu hesabı kaydet"><i data-lucide="save"></i> <span>Kaydet</span></button>
                </div>
            </div>
            <div style="padding:14px 18px;display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Kullanıcı Adı</label>
                    <input type="text" name="kullanici_adi" class="form-input" value="{{ $h->kullanici_adi }}">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" style="font-size:11px">Şifre</label>
                    <div style="position:relative">
                        <input type="password" name="sifre" class="form-input sm-sifre" value="{{ $h->sifre }}" style="padding-right:38px" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" data-form-type="other">
                        <button type="button" class="sm-goster" onclick="sifreToggle(this)" title="Göster/Gizle"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted)"><i data-lucide="eye"></i></button>
                    </div>
                </div>
            </div>
        </form>
        <div style="padding:0 18px 14px;display:flex;justify-content:flex-end">
            <form action="{{ route('admin.crm.ozel-kayitlar.hesap.sil', ['id' => $kayit->id, 'hesapId' => $h->id]) }}" method="POST" onsubmit="return confirm('Bu hesap silinsin mi?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" style="color:var(--danger);border:1px solid var(--danger)"><i data-lucide="trash-2"></i> <span>Hesabı Sil</span></button>
            </form>
        </div>
    </div>
    @endforeach
@empty
@endforelse

@if($kayit->hesaplar->isEmpty())
    <div class="section">
        <div class="empty-state" style="padding:28px 0">
            <i data-lucide="key" class="empty-state-icon"></i>
            <h4>Henüz hesap eklenmemiş</h4>
            <p>Yukarıdaki formdan ilk hesabı ekle — sosyal medya ya da web sitesi giriş bilgisi.</p>
        </div>
    </div>
@endif

<style>
.sm-dropdown { position:relative; }
.sm-dropdown-toggle {
    display:flex; align-items:center; justify-content:space-between; gap:8px;
    width:100%; text-align:left; cursor:pointer; background:var(--input-bg,#fff);
}
.sm-dropdown-toggle .sm-dropdown-label { color:var(--text); }
.sm-dropdown-toggle .sm-dropdown-label.is-placeholder { color:var(--text-muted,#94a3b8); }
.sm-dropdown-caret { width:16px; height:16px; flex-shrink:0; color:var(--text-muted,#94a3b8); transition:transform .18s ease; }
.sm-dropdown.open .sm-dropdown-caret { transform:rotate(180deg); }
.sm-dropdown-menu {
    position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:50;
    background:var(--card,#fff); border:1px solid var(--border,#e2e8f0); border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,.12); padding:6px; max-height:280px; overflow-y:auto;
    opacity:0; visibility:hidden; transform:translateY(-6px); transition:opacity .15s ease, transform .15s ease, visibility .15s;
}
.sm-dropdown.open .sm-dropdown-menu { opacity:1; visibility:visible; transform:translateY(0); }
.sm-dropdown-item {
    display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:7px;
    font-size:14px; color:var(--text); cursor:pointer; transition:background .12s ease;
}
.sm-dropdown-item:hover { background:var(--brand-soft,rgba(184,182,46,.14)); }
.sm-dropdown-item i { color:var(--text-muted,#94a3b8); flex-shrink:0; }
</style>

<script>
function sifreToggle(btn) {
    var inp = btn.parentElement.querySelector('input');
    if (!inp) return;
    inp.type = (inp.type === 'password') ? 'text' : 'password';
    if (window.lucide) {
        btn.querySelector('i').setAttribute('data-lucide', inp.type === 'password' ? 'eye' : 'eye-off');
        lucide.createIcons();
    }
}

function smDropdownToggle(e) {
    e.stopPropagation();
    document.getElementById('smPlatformDropdown').classList.toggle('open');
}

function smDropdownPick(el) {
    var dd     = document.getElementById('smPlatformDropdown');
    var label  = dd.querySelector('.sm-dropdown-label');
    var hidden = document.getElementById('smPlatformValue');
    var diger  = document.getElementById('smPlatformDiger');
    var val    = el.getAttribute('data-value');

    label.textContent = val;
    label.classList.remove('is-placeholder');
    dd.classList.remove('open');

    if (val === 'Diğer') {
        diger.style.display = 'block';
        diger.value = '';
        hidden.value = '';   // serbest input dolunca güncellenecek
        diger.focus();
    } else {
        diger.style.display = 'none';
        diger.value = '';
        hidden.value = val;
    }
}

/**
 * Bölüm değişince platform listesi filtrelenir ve seçim sıfırlanır.
 * Sosyal Medya → Instagram/Facebook/...  ·  Web Sitesi → WordPress/cPanel/FTP/...
 */
function smBolumDegisti(bolum) {
    var dd    = document.getElementById('smPlatformDropdown');
    var label = dd.querySelector('.sm-dropdown-label');

    dd.querySelectorAll('.sm-dropdown-item').forEach(function (it) {
        it.style.display = it.getAttribute('data-bolum') === bolum ? '' : 'none';
    });

    // Önceki seçim başka bölüme aitse temizle
    label.textContent = label.getAttribute('data-placeholder') || 'Seçiniz...';
    label.classList.add('is-placeholder');
    document.getElementById('smPlatformValue').value = '';
    var diger = document.getElementById('smPlatformDiger');
    diger.style.display = 'none';
    diger.value = '';

    // Seçili bölüm kutusunu vurgula
    document.querySelectorAll('.sm-bolum-sec').forEach(function (l) {
        var secili = l.getAttribute('data-bolum') === bolum;
        l.style.borderColor = secili ? 'var(--brand)' : 'var(--border)';
        l.style.background  = secili ? 'var(--brand-soft)' : 'transparent';
    });
}

// Açılışta varsayılan bölümü uygula
document.addEventListener('DOMContentLoaded', function () {
    var secili = document.querySelector('input[name="bolum"]:checked');
    if (secili) smBolumDegisti(secili.value);
});

// Dışarı tıklayınca kapat
document.addEventListener('click', function (e) {
    var dd = document.getElementById('smPlatformDropdown');
    if (dd && !dd.contains(e.target)) dd.classList.remove('open');
});
</script>
@endsection