@extends('admin._layout')

@section('title', 'Destek Talepleri')
@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Destek Talepleri</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🆘 Destek Talepleri
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $destekler->total() }}</span>
        </h1>
        <div class="page-subtitle">Müşterilerden gelen destek talepleri</div>
    </div>
    <div class="page-actions">
        <a href="{{ Route::has('admin.destek.talebi.olustur') ? route('admin.destek.talebi.olustur') : '#' }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Müşteri Adına Aç</span>
        </a>
    </div>
</div>

{{-- 4 Stat Card (tıklanabilir — listeyi filtreler) --}}
<style>
    a.stat-card { text-decoration: none; color: inherit; transition: transform .12s, box-shadow .12s; }
    a.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
</style>
@php $dUrl = route('admin.destek.index'); @endphp
<div class="stat-grid" style="margin-bottom:20px">
    <a href="{{ $dUrl }}" class="stat-card" title="Tüm talepleri göster"
       style="{{ !request('durum') && request('period', 'all') == 'all' ? 'outline:2px solid #3b82f6' : '' }}">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="life-buoy"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Talep</div>
            <div class="stat-card-value">{{ $stats['all']['total'] ?? 0 }}</div>
        </div>
    </a>

    <a href="{{ $dUrl }}?durum=bekleyen" class="stat-card" title="Cevap bekleyen talepleri göster"
       style="@if(($stats['all']['bekleyen'] ?? 0) > 0)border-color:rgba(245,158,11,0.3);@endif{{ request('durum') == 'bekleyen' ? 'outline:2px solid #f59e0b' : '' }}">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bekleyen</div>
            <div class="stat-card-value" style="@if(($stats['all']['bekleyen'] ?? 0) > 0)color:var(--warning)@endif">{{ $stats['all']['bekleyen'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Cevap bekliyor</div>
        </div>
    </a>

    <a href="{{ $dUrl }}?durum=cozulmus" class="stat-card" title="Çözülmüş talepleri göster"
       style="{{ request('durum') == 'cozulmus' ? 'outline:2px solid #10b981' : '' }}">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Çözülmüş</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $stats['all']['cozulmus'] ?? 0 }}</div>
        </div>
    </a>

    <a href="{{ $dUrl }}?period=this_month" class="stat-card" title="Bu ayki talepleri göster"
       style="{{ request('period') == 'this_month' ? 'outline:2px solid #8b5cf6' : '' }}">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay</div>
            <div class="stat-card-value">{{ $stats['this_month']['total'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                {{ $stats['this_month']['bekleyen'] ?? 0 }} bekliyor
            </div>
        </div>
    </a>
</div>

{{-- FİLTRELER --}}
<form method="GET" action="{{ route('admin.destek.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Dönem</label>
            <select name="period" class="form-select" style="height:36px;font-size:13px" onchange="this.form.submit()">
                <option value="all" {{ ($filter ?? '') == 'all' ? 'selected' : '' }}>Tümü ({{ $stats['all']['total'] ?? 0 }})</option>
                <option value="this_month" {{ ($filter ?? '') == 'this_month' ? 'selected' : '' }}>Bu Ay ({{ $stats['this_month']['total'] ?? 0 }})</option>
                <option value="picked" {{ ($filter ?? '') == 'picked' ? 'selected' : '' }}>Seçili Ay ({{ $stats['picked']['total'] ?? 0 }})</option>
            </select>
        </div>

        @if(($filter ?? '') == 'picked')
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" style="font-size:11px">Yıl</label>
                <select name="year" class="form-select" style="height:36px;font-size:13px">
                    @foreach($mevcutYillar ?? [] as $y)
                        <option value="{{ $y }}" {{ $pickYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" style="font-size:11px">Ay</label>
                <select name="month" class="form-select" style="height:36px;font-size:13px">
                    @php $aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']; @endphp
                    @foreach($aylar as $idx => $ay)
                        <option value="{{ $idx + 1 }}" {{ $pickMonth == ($idx + 1) ? 'selected' : '' }}>{{ $ay }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-sm" style="height:36px">
                    <i data-lucide="filter"></i>
                    <span>Filtrele</span>
                </button>
            </div>
        @endif
    </div>
</form>

@if($destekler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="life-buoy" class="empty-state-icon"></i>
            <h4>Destek talebi bulunamadı</h4>
            <p>Bu kriterlerde henüz destek talebi yok.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Konu / Müşteri</th>
                        <th>Departman</th>
                        <th>Öncelik</th>
                        <th>Son Cevap</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($destekler as $d)
                        @php
                            $durum = (int)($d->durum ?? 0);
                            $durumMap = [
                                0 => ['label' => 'Bekliyor', 'class' => 'badge-warning', 'icon' => '⏳'],
                                1 => ['label' => 'Cevaplandı', 'class' => 'badge-success', 'icon' => '✅'],
                                2 => ['label' => 'Kapatıldı', 'class' => 'badge-neutral', 'icon' => '🔒'],
                            ];
                            $dur = $durumMap[$durum] ?? $durumMap[0];

                            $oncelikMap = [
                                'Yüksek' => 'badge-danger', 'Acil' => 'badge-danger',
                                'Normal' => 'badge-neutral',
                                'Düşük' => 'badge-success',
                            ];
                            $oncCls = $oncelikMap[$d->oncelik ?? 'Normal'] ?? 'badge-neutral';

                            $musteriAdi = trim(($d->ad ?? '').' '.($d->soyad ?? '')) ?: ($d->email ?? '—');

                            $sonCevapStr = null;
                            if (!empty($d->son_cevap)) {
                                try { $sonCevapStr = \Carbon\Carbon::parse($d->son_cevap)->diffForHumans(); } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr @if($durum === 0)style="background:rgba(245,158,11,0.03)"@endif>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $d->id }}</td>
                            <td>
                                <a href="{{ route('admin.destek.detay', $d->id) }}" style="font-weight:600;color:var(--text);text-decoration:none">
                                    {{ \Illuminate\Support\Str::limit($d->baslik ?? '—', 50) }}
                                </a>
                                <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">
                                    👤 {{ $musteriAdi }}
                                    @if(!empty($d->email))
                                        <span style="color:var(--text-muted)">· {{ $d->email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary)">{{ $d->departman ?? $d->hizmet ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $oncCls }}" style="font-size:10.5px">{{ $d->oncelik ?? 'Normal' }}</span>
                            </td>
                            <td style="font-size:11px;color:var(--text-muted);white-space:nowrap">
                                {{ $sonCevapStr ?? '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $dur['class'] }}" style="font-size:10.5px">
                                    {{ $dur['icon'] }} {{ $dur['label'] }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.destek.detay', $d->id) }}" class="table-action" title="Cevapla">
                                        <i data-lucide="message-square"></i>
                                    </a>
                                    <form action="{{ route('admin.destek.sil', $d->id) }}" method="POST" onsubmit="return confirm('Talep silinsin mi?');" style="margin:0;display:inline">
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

    @if($destekler->hasPages())
        <div style="margin-top:16px">{{ $destekler->links() }}</div>
    @endif
@endif

@endsection