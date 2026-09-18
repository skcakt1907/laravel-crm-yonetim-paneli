@extends('admin._layout')

@section('title', 'Mesajlar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Mesajlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">💬 Mesajlar
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $mesajlar->total() }}</span>
        </h1>
        <div class="page-subtitle">Sistemden gelen mesajlar, müşteri iletileri</div>
    </div>
</div>

@php
    // Stat hesapları - güvenli (DB query)
    $totalMesaj = \DB::table('mesajlar')->count();
    $okunmamis = \DB::table('mesajlar')->where('durum', 0)->count();
    $okunmus = \DB::table('mesajlar')->where('durum', 1)->count();

    // Bugün ve bu ay - tarih kolonu adı belirsiz, ikisini de dene
    $bugun = 0;
    $buAy = 0;
    try {
        // 'tarih' veya 'created_at' kolonu varsa
        $hasTarih = \Schema::hasColumn('mesajlar', 'tarih');
        $hasCreated = \Schema::hasColumn('mesajlar', 'created_at');
        $tarihKol = $hasTarih ? 'tarih' : ($hasCreated ? 'created_at' : null);
        if ($tarihKol) {
            $bugun = \DB::table('mesajlar')->whereDate($tarihKol, now()->toDateString())->count();
            $buAy = \DB::table('mesajlar')->whereYear($tarihKol, now()->year)->whereMonth($tarihKol, now()->month)->count();
        }
    } catch (\Throwable $e) {}
@endphp

{{-- 4 Stat Card --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="inbox"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Mesaj</div>
            <div class="stat-card-value">{{ $totalMesaj }}</div>
        </div>
    </div>

    <div class="stat-card" style="@if($okunmamis > 0)border-color:rgba(245,158,11,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="mail"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Okunmamış</div>
            <div class="stat-card-value" style="@if($okunmamis > 0)color:var(--warning)@endif">{{ $okunmamis }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Yeni mesaj</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="mail-check"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Okunmuş</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $okunmus }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay</div>
            <div class="stat-card-value">{{ $buAy }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ $bugun }} bugün</div>
        </div>
    </div>
</div>

@if($mesajlar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="mail" class="empty-state-icon"></i>
            <h4>Henüz mesaj yok</h4>
            <p>Sistemden gelen mesajlar burada listelenecek.</p>
        </div>
    </div>
@else
    {{-- Gmail tarzı liste (okunmamışlar bold) --}}
    <div class="section" style="padding:0;overflow:hidden">
        <table class="data-table" style="margin:0">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Gönderen</th>
                    <th>Konu</th>
                    <th>Tarih</th>
                    <th>Durum</th>
                    <th class="text-right" style="width:120px">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mesajlar as $m)
                    @php
                        $isOkunmamis = (int)($m->durum ?? 0) === 0;
                        $gonderen = $m->gonderen ?? $m->ad ?? $m->isim ?? '—';
                        $konu = $m->konu ?? $m->baslik ?? '—';

                        $tarihFmt = null;
                        $tarihHumans = null;
                        $tarihVal = $m->tarih ?? $m->created_at ?? null;
                        if (!empty($tarihVal)) {
                            try {
                                $tc = \Carbon\Carbon::parse($tarihVal);
                                $tarihFmt = $tc->format('d.m.Y H:i');
                                $tarihHumans = $tc->diffForHumans();
                            } catch (\Throwable $e) {}
                        }
                    @endphp
                    <tr @if($isOkunmamis)style="background:rgba(184,182,46,0.04)"@endif>
                        <td style="color:var(--text-muted);font-size:12px">
                            @if($isOkunmamis)
                                <span title="Okunmamış" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--brand);margin-right:4px"></span>
                            @endif
                            #{{ $m->id }}
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                    {{ strtoupper(mb_substr($gonderen, 0, 1, 'UTF-8')) }}
                                </div>
                                <div style="font-weight:{{ $isOkunmamis ? '700' : '500' }};color:var(--text)">{{ $gonderen }}</div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.mesajlar.detay', $m->id) }}" style="color:var(--text);text-decoration:none;font-weight:{{ $isOkunmamis ? '700' : '400' }}">
                                {{ \Illuminate\Support\Str::limit($konu, 60) }}
                            </a>
                        </td>
                        <td style="font-size:11.5px;color:var(--text-muted);white-space:nowrap">
                            {{ $tarihHumans ?? '—' }}
                            @if($tarihFmt)
                                <div style="font-size:10px">{{ $tarihFmt }}</div>
                            @endif
                        </td>
                        <td>
                            @if($isOkunmamis)
                                <span class="badge badge-warning" style="font-size:10.5px">📬 Okunmamış</span>
                            @else
                                <span class="badge badge-success" style="font-size:10.5px">✅ Okundu</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="table-actions">
                                <a href="{{ route('admin.mesajlar.detay', $m->id) }}" class="table-action" title="Aç">
                                    <i data-lucide="eye"></i>
                                </a>
                                <form action="{{ route('admin.mesajlar.sil', $m->id) }}" method="POST" onsubmit="return confirm('Mesaj silinsin mi?');" style="margin:0;display:inline">
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

    @if($mesajlar->hasPages())
        <div style="margin-top:16px">
            {{ $mesajlar->withQueryString()->links() }}
        </div>
    @endif
@endif

@endsection