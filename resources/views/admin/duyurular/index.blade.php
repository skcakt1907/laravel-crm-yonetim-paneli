@extends('admin._layout')

@section('title', 'Duyurular')

@push('head')
<style>
    .duy-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:24px}
    .duy-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .duy-btn{display:inline-flex;align-items:center;gap:8px;background:#b8b62e;color:#1a1d24;font-weight:700;
        padding:11px 20px;border-radius:12px;text-decoration:none;border:none;cursor:pointer;font-size:14px;transition:.2s}
    .duy-btn:hover{background:#a4a229;transform:translateY(-1px)}
    .duy-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}
    .duy-item{display:flex;align-items:flex-start;gap:14px;padding:18px 20px;border-bottom:1px solid #f1f3f5;transition:.15s}
    .duy-item:last-child{border-bottom:none}
    .duy-item:hover{background:#fafbf5}
    .duy-icon{width:42px;height:42px;border-radius:10px;background:rgba(184,182,46,.14);color:#8a8820;
        display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .duy-body{flex:1;min-width:0}
    .duy-title{font-weight:700;font-size:15px;color:#1a1d24;margin:0 0 4px}
    .duy-meta{font-size:12.5px;color:#94a3b8;display:flex;gap:12px;flex-wrap:wrap}
    .duy-excerpt{font-size:13.5px;color:#475569;margin:6px 0 0;line-height:1.5;
        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .duy-tag{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:3px 9px;border-radius:20px}
    .duy-tag.tum{background:rgba(16,185,129,.12);color:#059669}
    .duy-tag.secili{background:rgba(59,130,246,.12);color:#2563eb}
    .duy-tag.mail{background:rgba(184,182,46,.16);color:#8a8820}
    .duy-actions{display:flex;gap:6px;flex-shrink:0}
    .duy-act{width:34px;height:34px;border-radius:9px;border:1px solid #e5e7eb;background:#fff;color:#64748b;
        display:flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;transition:.15s}
    .duy-act:hover{border-color:#b8b62e;color:#8a8820}
    .duy-act.del:hover{border-color:#ef4444;color:#ef4444}
    .duy-empty{padding:56px 20px;text-align:center;color:#94a3b8}
    .duy-empty i{width:48px;height:48px;margin-bottom:12px;opacity:.5}
</style>
@endpush

@section('content')
<div class="duy-head">
    <h1><i data-lucide="megaphone"></i> Duyurular</h1>
    <a href="{{ route('admin.duyurular.olustur') }}" class="duy-btn">
        <i data-lucide="plus" style="width:18px;height:18px"></i> Yeni Duyuru
    </a>
</div>

@if(session('success'))
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#059669;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#dc2626;padding:12px 16px;border-radius:12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>
@endif

<div class="duy-card">
    @forelse($duyurular as $d)
    <div class="duy-item">
        <div class="duy-icon"><i data-lucide="megaphone" style="width:20px;height:20px"></i></div>
        <div class="duy-body">
            <p class="duy-title">{{ $d->baslik }}</p>
            <div class="duy-meta">
                <span>{{ \Carbon\Carbon::parse($d->created_at)->format('d.m.Y H:i') }}</span>
                @if(!empty($d->olusturan_adi))<span>· {{ $d->olusturan_adi }}</span>@endif
                @if($d->hedef_tip === 'tum')
                    <span class="duy-tag tum"><i data-lucide="users" style="width:12px;height:12px"></i> Tüm Yöneticiler</span>
                @else
                    <span class="duy-tag secili"><i data-lucide="user-check" style="width:12px;height:12px"></i> Seçili</span>
                @endif
                @if($d->mail_gonderildi)<span class="duy-tag mail"><i data-lucide="mail" style="width:12px;height:12px"></i> Mail</span>@endif
            </div>
            <p class="duy-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($d->icerik), 160) }}</p>
        </div>
        <div class="duy-actions">
            <a href="{{ route('admin.duyurular.goster', $d->id) }}" class="duy-act" title="Görüntüle"><i data-lucide="eye" style="width:16px;height:16px"></i></a>
            <form action="{{ route('admin.duyurular.sil', $d->id) }}" method="POST" onsubmit="return confirm('Bu duyuru silinsin mi?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="duy-act del" title="Sil"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="duy-empty">
        <i data-lucide="megaphone"></i>
        <p>Henüz duyuru oluşturulmadı.</p>
    </div>
    @endforelse
</div>

@if($duyurular instanceof \Illuminate\Pagination\LengthAwarePaginator && $duyurular->hasPages())
<div style="margin-top:20px">{{ $duyurular->links() }}</div>
@endif
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush