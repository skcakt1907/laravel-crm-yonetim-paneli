@extends('admin._layout')

@section('title', 'İşlem Geçmişi')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">İşlem Geçmişi</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🕘 İşlem Geçmişi
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $kayitlar->total() }}</span>
        </h1>
        <div class="page-subtitle">Panelde yapılan yazma işlemleri — her biri <b>geri alınabilir</b>. (AI asistanı altyapısı)</div>
    </div>
</div>

@php
    $toplam   = \DB::table('islem_gecmisi')->count();
    $aiSayi   = \DB::table('islem_gecmisi')->where('kaynak', 'ai')->count();
    $geriSayi = \DB::table('islem_gecmisi')->where('geri_alindi', 1)->count();
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6"><i data-lucide="history"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam İşlem</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.15);color:#8b5cf6"><i data-lucide="sparkles"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-label">AI Kaynaklı</div>
            <div class="stat-card-value">{{ $aiSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.15);color:#f59e0b"><i data-lucide="undo-2"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-label">Geri Alınan</div>
            <div class="stat-card-value">{{ $geriSayi }}</div>
        </div>
    </div>
</div>

{{-- Filtre --}}
<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
    <input type="text" name="ara" value="{{ request('ara') }}" placeholder="Açıklamada ara…"
           class="form-control" style="max-width:280px">
    <select name="kaynak" class="form-control" style="max-width:160px">
        <option value="">Tüm kaynaklar</option>
        <option value="manuel" @selected(request('kaynak')==='manuel')>Manuel</option>
        <option value="ai" @selected(request('kaynak')==='ai')>AI</option>
    </select>
    <button class="btn btn-primary" type="submit"><i data-lucide="search"></i><span>Filtrele</span></button>
    @if(request('ara') || request('kaynak'))
        <a href="{{ route('admin.islem-gecmisi.index') }}" class="btn"><span>Temizle</span></a>
    @endif
</form>

<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th style="width:150px">Tarih</th>
                    <th style="width:140px">Kullanıcı</th>
                    <th>İşlem</th>
                    <th style="width:80px" class="text-center">Etki</th>
                    <th style="width:90px">Kaynak</th>
                    <th style="width:120px">Durum</th>
                    <th style="width:120px" class="text-right">Geri Al</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kayitlar as $k)
                    <tr>
                        <td>{{ $k->id }}</td>
                        <td style="white-space:nowrap;font-size:13px">
                            {{ \Carbon\Carbon::parse($k->tarih)->format('d.m.Y H:i') }}
                        </td>
                        <td>{{ $k->kullanici_adi }}</td>
                        <td>
                            <span style="font-weight:600">{{ $k->aciklama }}</span>
                            <div style="color:var(--muted,#94a3b8);font-size:12px">{{ $k->aksiyon }}</div>
                        </td>
                        <td class="text-center">{{ $k->etkilenen_adet }}</td>
                        <td>
                            @if($k->kaynak === 'ai')
                                <span class="badge" style="background:rgba(139,92,246,.15);color:#8b5cf6">🤖 AI</span>
                            @else
                                <span class="badge" style="background:rgba(148,163,184,.18);color:#64748b">✋ Manuel</span>
                            @endif
                        </td>
                        <td>
                            @if($k->geri_alindi)
                                <span class="badge badge-danger" title="{{ $k->geri_alma_tarihi }}">↩ Geri alındı</span>
                            @elseif($k->geri_alinabilir)
                                <span class="badge badge-success">✅ Aktif</span>
                            @else
                                <span class="badge" style="background:rgba(148,163,184,.18);color:#64748b">— Kalıcı</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(!$k->geri_alindi && $k->geri_alinabilir)
                                <form method="post" action="{{ route('admin.islem-gecmisi.geri-al', $k->id) }}"
                                      onsubmit="return confirm('Bu işlem geri alınacak — etkilenen {{ $k->etkilenen_adet }} kayıt eski hâline dönecek. Emin misin?');"
                                      style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#1a1a0e;font-weight:700">
                                        <i data-lucide="undo-2"></i><span>Geri Al</span>
                                    </button>
                                </form>
                            @else
                                <span style="color:var(--muted,#94a3b8)">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:36px;color:var(--muted,#94a3b8)">
                            Henüz kayıtlı işlem yok. Panelde bir değişiklik yapıldığında (ya da AI asistanı bir işlem uyguladığında) burada görünecek.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $kayitlar->links() }}</div>
</div>

@endsection
