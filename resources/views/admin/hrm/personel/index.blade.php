@extends('admin._layout')

@section('title', 'Personel')

@push('head')
<style>
    .hp-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .hp-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .hp-filtre{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px 16px;margin-bottom:18px;display:flex;gap:10px;align-items:center}
    .hp-filtre input{flex:1;border:1.5px solid #e5e7eb;border-radius:9px;padding:9px 13px;font-size:14px;font-family:inherit}
    .hp-filtre input:focus{outline:none;border-color:#b8b62e}
    .hp-filtre button{background:#b8b62e;color:#1a1d24;border:none;border-radius:9px;padding:9px 20px;font-weight:700;cursor:pointer}
    .hp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
    .hp-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;text-decoration:none;color:inherit;transition:.18s;display:block}
    .hp-card:hover{border-color:#b8b62e;box-shadow:0 6px 20px rgba(184,182,46,.12);transform:translateY(-2px)}
    .hp-card-top{display:flex;align-items:center;gap:13px;margin-bottom:14px}
    .hp-avatar{width:48px;height:48px;border-radius:12px;background:#b8b62e;color:#1a1d24;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;flex-shrink:0}
    .hp-ad{font-weight:700;font-size:15px;color:#1a1d24}
    .hp-rol{font-size:12px;font-weight:600;padding:2px 9px;border-radius:20px;display:inline-block;margin-top:3px}
    .hp-info{font-size:13px;color:#64748b;display:flex;flex-direction:column;gap:5px}
    .hp-info div{display:flex;align-items:center;gap:7px}
    .hp-info i{width:14px;height:14px;color:#94a3b8}
    .hp-empty{padding:50px;text-align:center;color:#94a3b8;grid-column:1/-1}
</style>
@endpush

@section('content')
<div class="hp-head">
    <h1><i data-lucide="users"></i> Personel</h1>
</div>

@if(session('success'))<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#059669;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>@endif

<form method="GET" class="hp-filtre">
    <input type="text" name="q" value="{{ $arama }}" placeholder="Personel ara (ad, kullanıcı adı, e-posta)...">
    <button type="submit">Ara</button>
</form>

<div class="hp-grid">
    @forelse($personeller as $p)
    <a href="{{ route('admin.hrm.personel.goster', $p->id) }}" class="hp-card">
        <div class="hp-card-top">
            <div class="hp-avatar">{{ mb_strtoupper(mb_substr($p->adi ?? $p->kullaniciadi ?? '?', 0, 1)) }}</div>
            <div>
                <div class="hp-ad">{{ $p->adi ?? $p->kullaniciadi }}</div>
                @if(!empty($p->rol_adi))
                <span class="hp-rol" style="background:{{ $p->rol_renk ?? '#64748b' }}22;color:{{ in_array($p->rol_renk,['warning','primary'])? '#8a8820' : ($p->rol_renk ?? '#64748b') }}">{{ $p->rol_adi }}</span>
                @endif
            </div>
        </div>
        <div class="hp-info">
            @if(!empty($p->email))<div><i data-lucide="mail"></i> {{ $p->email }}</div>@endif
            @if(!empty($p->pozisyon))<div><i data-lucide="briefcase"></i> {{ $p->pozisyon }}{{ !empty($p->departman) ? ' · '.$p->departman : '' }}</div>@endif
            @if(!empty($p->ise_baslama_tarihi))<div><i data-lucide="calendar"></i> İşe başlama: {{ \Carbon\Carbon::parse($p->ise_baslama_tarihi)->format('d.m.Y') }}</div>@endif
        </div>
    </a>
    @empty
    <div class="hp-empty"><i data-lucide="users" style="width:42px;height:42px;opacity:.4"></i><p>Personel bulunamadı.</p></div>
    @endforelse
</div>

@if($personeller instanceof \Illuminate\Pagination\LengthAwarePaginator && $personeller->hasPages())
<div style="margin-top:20px">{{ $personeller->links() }}</div>
@endif
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush
