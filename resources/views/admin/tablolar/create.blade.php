@extends('admin._layout')

@section('title', 'Yeni Tablo Oluştur')

@section('content')

{{-- SAYFA BAŞLIĞI --}}
<div class="page-header">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 class="page-title">Yeni Tablo Oluştur</h1>
            <p class="page-subtitle">Şablon seç veya boş başla.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.tablolar.store') }}" method="POST" style="max-width: 860px;">
@csrf

{{-- ŞABLON SEÇİMİ --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">
            <i data-lucide="layout-template" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>
            Şablon Seç
        </div>
        <span class="text-sm text-muted">İstediğin tabloyu hazır olarak başlat</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px;">
        @foreach($sablonlar as $key => $sablon)
            <label class="template-card {{ $key === 'tek_sayfa_hepsi' ? 'template-featured template-selected' : '' }}"
                   data-template="{{ $key }}"
                   style="border:2px solid var(--border); border-radius:var(--radius-lg); padding:16px; cursor:pointer; transition:all .2s; position:relative; display:flex; flex-direction:column; gap:8px; min-height:130px; background:var(--surface);">
                <input type="radio" name="sablon" value="{{ $key }}"
                       {{ $key === 'tek_sayfa_hepsi' ? 'checked' : '' }}
                       style="position:absolute; opacity:0; pointer-events:none;">
                <div style="font-size:34px; line-height:1;">{{ $sablon['ikon'] }}</div>
                <div style="font-weight:700; font-size:14px; color:var(--text);">{{ $sablon['ad'] }}</div>
                <div style="font-size:12px; color:var(--text-muted); line-height:1.4; flex:1;">{{ $sablon['aciklama'] }}</div>
                @if($key === 'bos')
                    <span class="badge badge-neutral" style="width:fit-content; font-size:10px;">Boş başla</span>
                @elseif($key === 'tek_sayfa_hepsi')
                    <span class="badge badge-warning" style="width:fit-content; font-size:10px;">⭐ Önerilen</span>
                @else
                    <span class="badge badge-success" style="width:fit-content; font-size:10px;">Hazır şablon</span>
                @endif
            </label>
        @endforeach
    </div>
</div>

{{-- TEMEL BİLGİLER --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">
            <i data-lucide="info" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>
            Temel Bilgiler
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Tablo Adı <span style="color:var(--danger)">*</span></label>
        <input type="text" name="ad" class="form-input" required maxlength="150" id="ad-input"
               placeholder="Örn: 2026 DN Kreatif Muhasebe" value="{{ old('ad') }}">
    </div>

    <div class="form-group">
        <label class="form-label">Açıklama <span style="color:var(--text-muted); font-weight:400;">(isteğe bağlı)</span></label>
        <textarea name="aciklama" class="form-input" rows="2"
                  placeholder="Bu tablo neyi tutuyor?">{{ old('aciklama') }}</textarea>
    </div>

    <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">İkon</label>
        <input type="text" name="ikon" id="ikon-input" class="form-input" maxlength="10"
               placeholder="📊" value="{{ old('ikon', '🌟') }}"
               style="max-width:120px; text-align:center; font-size:22px;">
        <div style="display:grid; grid-template-columns:repeat(8,1fr); gap:4px; max-width:340px; margin-top:10px;">
            @foreach(['📊','💰','📅','📝','🧾','💼','🏪','📞','✅','🎯','🛒','📈','🚀','⭐','📦','🎁','📁','🏦','💵','💸','📑','💳','🌟','🎨'] as $emo)
                <button type="button" class="emoji-pick-btn"
                        style="padding:7px; font-size:18px; background:var(--bg-subtle); border:1px solid transparent; border-radius:var(--radius-sm); cursor:pointer; transition:all .15s;"
                        onclick="document.getElementById('ikon-input').value='{{ $emo }}'">{{ $emo }}</button>
            @endforeach
        </div>
    </div>
</div>

{{-- YETKİLER --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">
            <i data-lucide="lock" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>
            Yetkiler
        </div>
    </div>

    <div class="form-group">
        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px;">
            <input type="checkbox" name="herkes_gorur" value="1"
                   style="width:auto; padding:0; margin:0; flex-shrink:0;">
            <span><strong>Tüm adminler görebilir</strong>
                <span class="text-muted"> — işaretleme yoksa aşağıdakiler</span>
            </span>
        </label>
    </div>

    <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Erişebilecek adminler</label>
        <div style="max-height:200px; overflow-y:auto; padding:10px; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:6px;">
            @foreach($adminler as $a)
                <label style="display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:var(--radius-sm); background:var(--surface); border:1px solid var(--border); cursor:pointer; font-size:13px;">
                    <input type="checkbox" name="yetkili_ids[]" value="{{ $a->id }}"
                           style="width:auto; padding:0; margin:0; flex-shrink:0;">
                    {{ $a->adi ?: ('Admin #' . $a->id) }}
                </label>
            @endforeach
        </div>
        <p class="text-muted" style="font-size:12px; margin-top:6px;">
            <i data-lucide="info" style="width:12px;height:12px;display:inline;vertical-align:middle;"></i>
            Sen (oluşturan) zaten otomatik erişebilirsin.
        </p>
    </div>
</div>

{{-- BUTONLAR --}}
<div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary">
        <i data-lucide="x"></i> İptal
    </a>
    <button type="submit" class="btn btn-primary">
        <i data-lucide="check"></i> Oluştur ve Düzenle
    </button>
</div>

</form>

@endsection

@push('scripts')
<style>
.template-card:hover {
    border-color: var(--brand-medium) !important;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.template-selected {
    border-color: var(--brand) !important;
    background: var(--brand-soft) !important;
    box-shadow: 0 0 0 3px var(--brand-medium) !important;
}
.template-selected::after {
    content: '✓';
    position: absolute; top:10px; right:12px;
    width:24px; height:24px;
    background: var(--brand); color:#000;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:13px;
}
.emoji-pick-btn:hover {
    background: var(--brand-soft) !important;
    border-color: var(--brand) !important;
}
</style>
<script>
document.querySelectorAll('.template-card').forEach(function(card) {
    card.addEventListener('click', function() {
        document.querySelectorAll('.template-card').forEach(function(c) {
            c.classList.remove('template-selected');
        });
        card.classList.add('template-selected');
        var radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;

        var ikonMap = {
            'bos': '📄', 'sozlesmeler': '📅', 'tahsil_edilen': '💸',
            'gider_maas': '💰', 'kasa_hareketleri': '🏦', 'tek_sayfa_hepsi': '🌟'
        };
        var tplKey = card.dataset.template;
        if (ikonMap[tplKey]) document.getElementById('ikon-input').value = ikonMap[tplKey];

        var adMap = {
            'sozlesmeler': 'Sözleşmeler Tablosu', 'tahsil_edilen': 'Tahsil Edilen Aylık Gelir',
            'gider_maas': 'Aylık Gider + Maaş', 'kasa_hareketleri': 'Günlük Kasa Hareketleri',
            'tek_sayfa_hepsi': 'DN Kreatif Aylık Tablo'
        };
        var adInput = document.getElementById('ad-input');
        if (adMap[tplKey] && !adInput.value.trim()) adInput.placeholder = 'Örn: ' + adMap[tplKey];
    });
});
</script>
@endpush