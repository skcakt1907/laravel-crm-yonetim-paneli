@extends('admin._layout')

@section('title', 'Tablolar Kilit Ayarları')

@section('content')

<div class="page-header">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 class="page-title">⚙️ Tablolar Kilit Ayarları</h1>
            <p class="page-subtitle">PIN değiştir, yetkili adminleri yönet.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.tablolar.kilit.ayarlar.kaydet') }}" method="POST" style="max-width:880px;">
    @csrf

    {{-- YETKİLİ ADMINLER --}}
    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title">
                <i data-lucide="users" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>
                Yetkili Adminler
            </div>
            <span class="text-sm text-muted">{{ count($yetkililer) }} kişi yetkili — Sen otomatik dahilsin</span>
        </div>

        <div style="max-height:340px; overflow-y:auto; padding:12px; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:6px;">
            @foreach($adminler as $a)
                @php $checked = in_array($a->id, $yetkililer); @endphp
                <label class="admin-item {{ $checked ? 'selected' : '' }}" data-admin-item style="display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); cursor:pointer; transition:all .15s; font-size:13px;">
                    <input type="checkbox" name="yetkili_ids[]" value="{{ $a->id }}" {{ $checked ? 'checked' : '' }} style="width:auto; margin:0; flex-shrink:0;">
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:600; font-size:13px; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $a->adi }}
                            @if($a->id == session('admin_id'))
                                <span style="color:var(--brand); font-size:10px; margin-left:4px; font-weight:700;">(SEN)</span>
                            @endif
                        </div>
                        <div style="color:var(--text-muted); font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ '@'.$a->kullaniciadi }}</div>
                    </div>
                </label>
            @endforeach
        </div>

        <p class="text-muted" style="font-size:12px; margin-top:10px;">
            💡 İşaretlediğin adminler PIN ile tablolar modülüne girebilir.
        </p>
    </div>

    {{-- PIN DEĞİŞTİR (DANGER ZONE) --}}
    <div class="card mb-4" style="border-color:rgba(239,68,68,.25); background:rgba(239,68,68,.02);">
        <div class="card-header">
            <div class="card-title" style="color:var(--danger);">
                <i data-lucide="key" style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px;"></i>
                PIN Değiştir
            </div>
            <span class="text-sm text-muted">Boş bırakırsan PIN değişmez</span>
        </div>

        <div class="form-group">
            <label class="form-label">Yeni PIN</label>
            <input type="password" name="pin" class="form-input" placeholder="Boş bırakırsan değişmez" minlength="4" maxlength="20" autocomplete="new-password">
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Yeni PIN Tekrar</label>
            <input type="password" name="pin_tekrar" class="form-input" placeholder="••••••" minlength="4" maxlength="20" autocomplete="new-password">
        </div>

        <p class="text-muted" style="font-size:12px; margin-top:10px;">
            ⚠️ PIN değiştirirsen herkes (sen dahil) yeniden giriş yapmak zorunda kalır.
        </p>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
        <a href="{{ route('admin.tablolar.kilit.cikis') }}" class="btn btn-danger"
           onclick="return confirm('Tablolar modülünden çıkış yapacaksın. PIN tekrar sorulacak. Devam?');">
            <i data-lucide="log-out"></i>
            Tablolar'dan Çık (PIN tekrar sor)
        </a>

        <div style="display:flex; gap:10px;">
            <a href="{{ route('admin.tablolar.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i> İptal
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> Ayarları Kaydet
            </button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<style>
.admin-item:hover {
    background: var(--brand-soft) !important;
    border-color: var(--brand-medium) !important;
}
.admin-item.selected {
    background: var(--brand-soft) !important;
    border-color: var(--brand) !important;
}
</style>
<script>
document.querySelectorAll('[data-admin-item]').forEach(function(item) {
    const checkbox = item.querySelector('input[type="checkbox"]');
    checkbox.addEventListener('change', function() {
        if (this.checked) item.classList.add('selected');
        else item.classList.remove('selected');
    });
});
</script>
@endpush