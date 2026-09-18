@extends('admin._layout')

@section('title', 'Data Center — Listeler')

@push('head')
<style>
    .lst-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }
    .lst-card { background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:14px; padding:16px; }
    .lst-card .top { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
    .lst-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
    .lst-card .ad { font-weight:700; font-size:15px; color:var(--text,#111); }
    .lst-card .uye { font-size:12px; color:var(--text-muted,#6b7280); }
    .lst-card .ack { font-size:12.5px; color:var(--text-muted,#6b7280); margin:6px 0 10px; min-height:18px; }
    .lst-card .acts { display:flex; gap:8px; }
    .lst-new { background:var(--surface,#fff); border:1px dashed var(--border,#cbd5e1); border-radius:14px; padding:16px; margin-bottom:18px; }
    .lst-new .row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
    .lst-new .fld { display:flex; flex-direction:column; gap:4px; }
    .lst-new label { font-size:12px; color:var(--text-muted,#6b7280); }
    .lst-new input[type=text] { padding:8px 10px; border:1px solid var(--border,#e5e7eb); border-radius:8px; min-width:200px; }
    .lst-edit { display:none; margin-top:8px; }
    .lst-edit input { width:100%; padding:7px 9px; border:1px solid var(--border,#e5e7eb); border-radius:8px; margin-bottom:6px; font-size:13px; }
    @media(max-width:600px){ .lst-new input[type=text]{min-width:0;width:100%;} .lst-new .fld{width:100%;} }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="list"></i> Data Center — Listeler</h1>
        <div class="page-subtitle">Müşterileri segmentlere ayır. Bir müşteri aynı anda birden çok listede olabilir.</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.data-center.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> <span>Data Center</span></a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:14px">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:14px">{{ $errors->first() }}</div>@endif

{{-- Yeni liste --}}
<div class="lst-new">
    <form method="POST" action="{{ route('admin.data-center.liste-kaydet') }}" class="row">
        @csrf
        <div class="fld">
            <label>Liste Adı *</label>
            <input type="text" name="ad" placeholder="Örn: Restorana Gidenler" required>
        </div>
        <div class="fld">
            <label>Açıklama</label>
            <input type="text" name="aciklama" placeholder="(opsiyonel)">
        </div>
        <div class="fld">
            <label>Renk</label>
            <input type="color" name="renk" value="#c8a44d" style="width:48px;height:38px;border:1px solid var(--border,#e5e7eb);border-radius:8px;padding:2px">
        </div>
        <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> <span>Liste Oluştur</span></button>
    </form>
</div>

{{-- Listeler --}}
@if($listeler->isEmpty())
    <div style="text-align:center;color:var(--text-muted,#6b7280);padding:40px">Henüz liste yok. Yukarıdan ilk listeni oluştur.</div>
@else
<div class="lst-grid">
    @foreach($listeler as $l)
    <div class="lst-card">
        <div class="top">
            <span class="lst-dot" style="background:{{ $l->renk ?: '#c8a44d' }}"></span>
            <span class="ad">{{ $l->ad }}</span>
        </div>
        <div class="uye">👥 {{ $l->musteriler_count }} müşteri</div>
        <div class="ack">{{ $l->aciklama }}</div>
        <div class="acts">
            <button type="button" class="btn btn-secondary btn-sm" onclick="this.closest('.lst-card').querySelector('.lst-edit').style.display='block'">Düzenle</button>
            <form method="POST" action="{{ route('admin.data-center.liste-sil', $l->id) }}" onsubmit="return confirm('“{{ $l->ad }}” listesi silinsin mi? (Müşteriler silinmez, sadece bu listeyle ilişkileri kopar.)')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Sil</button>
            </form>
        </div>
        {{-- inline düzenle --}}
        <form class="lst-edit" method="POST" action="{{ route('admin.data-center.liste-guncelle', $l->id) }}">
            @csrf @method('PUT')
            <input type="text" name="ad" value="{{ $l->ad }}" required>
            <input type="text" name="aciklama" value="{{ $l->aciklama }}" placeholder="Açıklama">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="color" name="renk" value="{{ $l->renk ?: '#c8a44d' }}" style="width:44px;height:34px;padding:2px;border:1px solid var(--border,#e5e7eb);border-radius:8px">
                <button type="submit" class="btn btn-primary btn-sm">Kaydet</button>
            </div>
        </form>
    </div>
    @endforeach
</div>
@endif

@endsection
