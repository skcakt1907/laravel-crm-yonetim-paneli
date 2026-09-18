@extends('admin._layout')

@section('title', $sp->ad . ' — Düzenle')

@section('content')

{{-- SAYFA BAŞLIĞI --}}
<div class="page-header">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 class="page-title">{{ $sp->ikon }} {{ $sp->ad }}</h1>
            <p class="page-subtitle">Tablo ayarlarını güncelle.</p>
        </div>
    </div>
    <div class="page-actions">
        @if($sp->olusturan_id == session('admin_id') || session('admin_rol') == 1)
            <button type="button" class="btn btn-secondary"
                    onclick="deleteSpreadsheet({{ $sp->id }}, '{{ addslashes($sp->ad) }}')">
                <i data-lucide="trash-2"></i> Sil
            </button>
        @endif
        <button type="submit" form="edit-form" class="btn btn-primary">
            <i data-lucide="save"></i> Kaydet
        </button>
    </div>
</div>

<form id="edit-form" action="{{ route('admin.tablolar.update', $sp->id) }}" method="POST" style="max-width:860px;">
@csrf
@method('PUT')

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
        <input type="text" name="ad" class="form-input" required maxlength="150"
               value="{{ old('ad', $sp->ad) }}" placeholder="Örn: 2026 DN Kreatif Muhasebe">
    </div>

    <div class="form-group">
        <label class="form-label">Açıklama <span style="color:var(--text-muted); font-weight:400;">(isteğe bağlı)</span></label>
        <textarea name="aciklama" class="form-input" rows="2"
                  placeholder="Bu tablo neyi tutuyor?">{{ old('aciklama', $sp->aciklama) }}</textarea>
    </div>

    <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">İkon</label>
        <input type="text" name="ikon" id="ikon-input" class="form-input" maxlength="10"
               value="{{ old('ikon', $sp->ikon) }}"
               style="max-width:120px; text-align:center; font-size:22px;">
        <div style="display:grid; grid-template-columns:repeat(8,1fr); gap:4px; max-width:340px; margin-top:10px;">
            @foreach(['📊','💰','📅','📝','🧾','💼','🏪','📞','✅','🎯','🛒','📈','🚀','⭐','📦','🎁','📁','🏦','💵','💸','📑','💳','🌟','🎨'] as $emo)
                <button type="button"
                        style="padding:7px; font-size:18px; background:var(--bg-subtle); border:1px solid transparent; border-radius:var(--radius-sm); cursor:pointer; transition:all .15s;"
                        class="emoji-pick-btn"
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
                   {{ $sp->herkes_gorur ? 'checked' : '' }}
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
                           {{ in_array($a->id, $yetkiliIds ?? []) ? 'checked' : '' }}
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

{{-- META BİLGİLER --}}
<div class="card mb-4">
    <div class="card-header">
        <div class="card-title">
            <i data-lucide="clock" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>
            Bilgiler
        </div>
    </div>
    <div class="info-grid">
        <div class="info-item">
            <span class="lbl">Oluşturulma</span>
            <span class="val">{{ $sp->created_at?->format('d.m.Y H:i') ?? '—' }}</span>
        </div>
        <div class="info-item">
            <span class="lbl">Son Güncelleme</span>
            <span class="val">{{ $sp->updated_at?->diffForHumans() ?? '—' }}</span>
        </div>
        <div class="info-item">
            <span class="lbl">Oluşturan</span>
            <span class="val">{{ optional($sp->olusturan)->adi ?? 'Bilinmiyor' }}</span>
        </div>
        <div class="info-item">
            <span class="lbl">Tablo ID</span>
            <span class="val">#{{ $sp->id }}</span>
        </div>
    </div>
</div>

{{-- BUTONLAR --}}
<div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary">
        <i data-lucide="x"></i> İptal
    </a>
    <button type="submit" class="btn btn-primary">
        <i data-lucide="save"></i> Kaydet
    </button>
</div>

</form>

<form id="delete-form" method="POST" style="display:none;">
    @csrf @method('DELETE')
</form>

@endsection

@push('scripts')
<style>
.emoji-pick-btn:hover {
    background: var(--brand-soft) !important;
    border-color: var(--brand) !important;
}
</style>
<script>
function deleteSpreadsheet(id, ad) {
    if (!confirm('"' + ad + '" tablosunu silmek istediğine emin misin?\nBu işlem geri alınamaz.')) return;
    var form = document.getElementById('delete-form');
    form.action = '/admin/tablolar/' + id;
    form.submit();
}
</script>
@endpush