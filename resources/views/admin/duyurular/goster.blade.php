@extends('admin._layout')

@section('title', 'Duyuru Detayı')

@push('head')
<style>
    .dyg-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-size:13.5px;font-weight:600;margin-bottom:18px}
    .dyg-back:hover{color:#8a8820}
    .dyg-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px;max-width:820px;margin-bottom:20px}
    .dyg-title{font-size:22px;font-weight:800;color:#1a1d24;margin:0 0 10px}
    .dyg-meta{font-size:13px;color:#94a3b8;display:flex;gap:14px;flex-wrap:wrap;margin-bottom:20px;padding-bottom:18px;border-bottom:1px solid #f1f3f5}
    .dyg-icerik{font-size:15px;color:#334155;line-height:1.7;white-space:pre-wrap}
    .dyg-tag{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:3px 9px;border-radius:20px}
    .dyg-tag.tum{background:rgba(16,185,129,.12);color:#059669}
    .dyg-tag.secili{background:rgba(59,130,246,.12);color:#2563eb}
    .dyg-tag.mail{background:rgba(184,182,46,.16);color:#8a8820}
    .dyg-alicilar{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:22px;max-width:820px}
    .dyg-ali-h{font-size:15px;font-weight:700;color:#1a1d24;margin:0 0 14px;display:flex;align-items:center;gap:8px}
    .dyg-ali-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f5f6f7}
    .dyg-ali-row:last-child{border-bottom:none}
    .dyg-ali-name{font-size:14px;color:#334155;font-weight:600}
    .dyg-ali-mail{font-size:12.5px;color:#94a3b8}
    .dyg-okundu{font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px}
    .dyg-okundu.e{background:rgba(16,185,129,.12);color:#059669}
    .dyg-okundu.h{background:rgba(148,163,184,.15);color:#64748b}
</style>
@endpush

@section('content')
<a href="{{ route('admin.duyurular.index') }}" class="dyg-back"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Duyurulara dön</a>

<div class="dyg-card">
    <h1 class="dyg-title">{{ $duyuru->baslik }}</h1>
    <div class="dyg-meta">
        <span><i data-lucide="calendar" style="width:13px;height:13px;vertical-align:-2px"></i> {{ \Carbon\Carbon::parse($duyuru->created_at)->format('d.m.Y H:i') }}</span>
        @if(!empty($duyuru->olusturan_adi))<span><i data-lucide="user" style="width:13px;height:13px;vertical-align:-2px"></i> {{ $duyuru->olusturan_adi }}</span>@endif
        @if($duyuru->hedef_tip === 'tum')
            <span class="dyg-tag tum"><i data-lucide="users" style="width:12px;height:12px"></i> Tüm Yöneticiler</span>
        @else
            <span class="dyg-tag secili"><i data-lucide="user-check" style="width:12px;height:12px"></i> Seçili</span>
        @endif
        @if($duyuru->mail_gonderildi)<span class="dyg-tag mail"><i data-lucide="mail" style="width:12px;height:12px"></i> Mail gönderildi</span>@endif
    </div>
    <div class="dyg-icerik">{{ $duyuru->icerik }}</div>
</div>

<div class="dyg-alicilar">
    <p class="dyg-ali-h"><i data-lucide="users" style="width:18px;height:18px"></i> Alıcılar ({{ $alicilar->count() }})</p>
    @forelse($alicilar as $a)
    <div class="dyg-ali-row">
        <div>
            <span class="dyg-ali-name">{{ $a->yonetici_adi ?? ('#'.$a->yonetici_id) }}</span>
            @if(!empty($a->yonetici_email))<span class="dyg-ali-mail"> · {{ $a->yonetici_email }}</span>@endif
        </div>
        @if($a->okundu)
            <span class="dyg-okundu e">Okundu</span>
        @else
            <span class="dyg-okundu h">Okunmadı</span>
        @endif
    </div>
    @empty
    <p style="color:#94a3b8;font-size:13px">Alıcı kaydı yok.</p>
    @endforelse
</div>
@endsection

@push('scripts')
<script>if(window.lucide)lucide.createIcons();</script>
@endpush