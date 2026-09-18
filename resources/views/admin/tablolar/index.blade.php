@extends('admin._layout')

@section('title', 'Tablolar')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">📊 Tablolar</h1>
        <p class="page-subtitle">Excel tabloları oluştur, düzenle ve yönet.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.tablolar.kilit.ayarlar') }}" class="btn btn-secondary btn-sm" title="Kilit Ayarları">
            <i data-lucide="lock"></i>
            Kilit Ayarları
        </a>
        <a href="{{ route('admin.tablolar.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            Yeni Tablo
        </a>
    </div>
</div>

@if($spreadsheets->isEmpty())
    <div class="card" style="text-align:center; padding: 64px 24px;">
        <div style="font-size: 56px; margin-bottom: 16px; line-height: 1;">📊</div>
        <h3 style="margin-bottom: 8px; color: var(--text);">Henüz tablo yok</h3>
        <p class="text-muted" style="margin-bottom: 24px; font-size: 14px;">
            İlk Excel tablonu oluştur — formüller, hesaplamalar, hepsi içinde.
        </p>
        <a href="{{ route('admin.tablolar.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            İlk Tabloyu Oluştur
        </a>
    </div>
@else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:16px;">
        @foreach($spreadsheets as $sp)
        <div class="card hoverable sp-card" style="position:relative; padding:20px;">

            {{-- Hover: ayarlar + sil --}}
            <div class="sp-actions" style="position:absolute; top:10px; right:10px; display:flex; gap:6px; opacity:0; transition:opacity .2s; z-index:2;">
                <a href="{{ route('admin.tablolar.edit', $sp->id) }}" title="Ayarlar"
                    style="width:28px; height:28px; border-radius:var(--radius-md); background:var(--bg-subtle); border:1px solid var(--border); color:var(--text-secondary); display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; text-decoration:none;"
                    onclick="event.stopPropagation();">
                    <i data-lucide="settings" style="width:13px; height:13px;"></i>
                </a>
                @if($sp->olusturan_id == session('admin_id') || session('admin_rol') == 1)
                <button type="button" title="Sil"
                    style="width:28px; height:28px; border-radius:var(--radius-md); background:var(--danger-soft); border:1px solid rgba(239,68,68,.25); color:var(--danger); display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0;"
                    onclick="event.preventDefault(); event.stopPropagation(); deleteSpreadsheet({{ $sp->id }}, '{{ addslashes($sp->ad) }}')">
                    <i data-lucide="trash-2" style="width:13px; height:13px;"></i>
                </button>
                @endif
            </div>

            {{-- KARTA TIKLAYINCA: SHOW (Luckysheet editör) açılır --}}
            <a href="{{ route('admin.tablolar.show', $sp->id) }}"
               style="text-decoration:none; color:inherit; display:flex; flex-direction:column; gap:10px;">
                <div style="font-size:40px; line-height:1;">{{ $sp->ikon }}</div>
                <div>
                    <div style="font-weight:600; font-size:15px; color:var(--text); margin-bottom:4px;">{{ $sp->ad }}</div>
                    @if($sp->aciklama)
                        <div style="font-size:13px; color:var(--text-muted); line-height:1.4;">{{ \Illuminate\Support\Str::limit($sp->aciklama, 80) }}</div>
                    @endif
                </div>
                <div style="padding-top:10px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; font-size:11px; color:var(--text-muted);">
                    <span style="display:flex; align-items:center; gap:4px;">
                        <i data-lucide="clock" style="width:12px; height:12px;"></i>
                        {{ $sp->updated_at?->diffForHumans() ?? '—' }}
                    </span>
                    <span style="display:flex; align-items:center; gap:4px; color:var(--brand); font-weight:600;">
                        <i data-lucide="table" style="width:12px; height:12px;"></i>
                        Tabloyu Aç
                    </span>
                </div>
            </a>

        </div>
        @endforeach
    </div>
@endif

<form id="delete-form" method="POST" style="display:none">
    @csrf @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
document.querySelectorAll('.sp-card').forEach(function(card) {
    var actions = card.querySelector('.sp-actions');
    if (!actions) return;
    card.addEventListener('mouseenter', function() { actions.style.opacity = '1'; });
    card.addEventListener('mouseleave', function() { actions.style.opacity = '0'; });
});

function deleteSpreadsheet(id, ad) {
    if (!confirm('"' + ad + '" tablosunu silmek istediğine emin misin?\nBu işlem geri alınamaz.')) return;
    var form = document.getElementById('delete-form');
    form.action = '/admin/tablolar/' + id;
    form.submit();
}
</script>
@endpush