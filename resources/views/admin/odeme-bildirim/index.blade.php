@extends('admin._layout')

@section('title', 'Ödeme Bildirimleri')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Ödeme Bildirimleri</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">💸 Ödeme Bildirimleri
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $bildirimler->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşterilerin gönderdiği ödeme/havale dekontlarını incele ve onayla</div>
    </div>
</div>

@php
    $totalBildirim = \DB::table('odeme_bildirimleri')->count();
    $bekleyen = \DB::table('odeme_bildirimleri')->where('durum', 0)->count();
    $onaylanan = \DB::table('odeme_bildirimleri')->where('durum', 1)->count();
    $reddedilen = \DB::table('odeme_bildirimleri')->where('durum', 2)->count();
    $toplamTutar = (float) \DB::table('odeme_bildirimleri')->where('durum', 1)->sum('tutar');
@endphp

{{-- 4 Stat Card --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card" style="@if($bekleyen > 0)border-color:rgba(245,158,11,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bekleyen</div>
            <div class="stat-card-value" style="@if($bekleyen > 0)color:var(--warning)@endif">{{ $bekleyen }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">İnceleme bekliyor</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Onaylanan</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $onaylanan }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Reddedilen</div>
            <div class="stat-card-value">{{ $reddedilen }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:var(--brand)">
            <i data-lucide="banknote"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Onaylı Toplam</div>
            <div class="stat-card-value" style="font-size:18px;color:var(--brand)">₺{{ number_format($toplamTutar, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

@if($bildirimler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="wallet" class="empty-state-icon"></i>
            <h4>Henüz ödeme bildirimi yok</h4>
            <p>Müşteriler havale yaptığında dekont buraya düşer.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Müşteri / Ad</th>
                        <th class="text-right">Tutar</th>
                        <th>Açıklama</th>
                        <th>Dekont</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bildirimler as $b)
                        @php
                            $durum = (int) ($b->durum ?? 0);
                            $durumMap = [
                                0 => ['label' => 'Bekliyor', 'class' => 'badge-warning', 'icon' => '⏳'],
                                1 => ['label' => 'Onaylandı', 'class' => 'badge-success', 'icon' => '✅'],
                                2 => ['label' => 'Reddedildi', 'class' => 'badge-danger', 'icon' => '❌'],
                            ];
                            $d = $durumMap[$durum] ?? $durumMap[0];
                            $musteriAdi = $b->ad ?? $b->isim ?? '—';
                        @endphp
                        <tr @if($durum === 0)style="background:rgba(245,158,11,0.03)"@endif>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $b->id }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--brand),#8a8a1f);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                        {{ strtoupper(mb_substr($musteriAdi, 0, 1, 'UTF-8')) }}
                                    </div>
                                    <div style="font-weight:600;color:var(--text);font-size:13px">{{ $musteriAdi }}</div>
                                </div>
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <span style="font-weight:700;color:var(--brand);font-size:14px">
                                    ₺{{ number_format((float)($b->tutar ?? 0), 2, ',', '.') }}
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary);max-width:240px">
                                @if(!empty($b->aciklama))
                                    <span title="{{ $b->aciklama }}">{{ \Illuminate\Support\Str::limit($b->aciklama, 60) }}</span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($b->dekont))
                                    <a href="{{ asset('tema/uploads/dekont/'.$b->dekont) }}" target="_blank" class="badge badge-brand" style="font-size:10.5px;text-decoration:none">
                                        <i data-lucide="paperclip" style="width:10px;height:10px"></i>
                                        Dekont
                                    </a>
                                @else
                                    <span style="color:var(--text-muted);font-size:11px">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary);white-space:nowrap">
                                @php
                                    $tarihStr = null;
                                    $saatStr = null;
                                    if (!empty($b->tarih)) {
                                        try {
                                            $c = \Carbon\Carbon::parse($b->tarih);
                                            $tarihStr = $c->format('d.m.Y');
                                            $saatStr = $c->format('H:i');
                                        } catch (\Throwable $e) {
                                            $tarihStr = '—';
                                        }
                                    }
                                @endphp
                                @if($tarihStr)
                                    {{ $tarihStr }}
                                    @if($saatStr)
                                        <div style="font-size:10px;color:var(--text-muted)">{{ $saatStr }}</div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $d['class'] }}" style="font-size:10.5px">
                                    {{ $d['icon'] }} {{ $d['label'] }}
                                </span>
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <div class="table-actions">
                                    <a href="{{ route('admin.odeme-bildirim.detay', $b->id) }}" class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    @if($durum !== 1)
                                        <form action="{{ route('admin.odeme-bildirim.durum', ['id' => $b->id, 'durum' => 1]) }}" method="POST" style="margin:0;display:inline" onsubmit="return confirm('Bu bildirimi ONAYLA?');">
                                            @csrf
                                            <button type="submit" class="table-action" style="color:var(--success)" title="Onayla">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($durum !== 2)
                                        <form action="{{ route('admin.odeme-bildirim.durum', ['id' => $b->id, 'durum' => 2]) }}" method="POST" style="margin:0;display:inline" onsubmit="return confirm('Bu bildirimi REDDET?');">
                                            @csrf
                                            <button type="submit" class="table-action" style="color:var(--warning)" title="Reddet">
                                                <i data-lucide="x"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.odeme-bildirim.sil', $b->id) }}" method="POST" onsubmit="return confirm('Bu bildirimi sil?');" style="margin:0;display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($bildirimler->hasPages())
        <div style="margin-top:16px">
            {{ $bildirimler->links() }}
        </div>
    @endif
@endif

@endsection