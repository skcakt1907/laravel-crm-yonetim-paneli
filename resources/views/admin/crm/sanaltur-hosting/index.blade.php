@extends('admin._layout')
@section('title', 'Sanal Tur Hosting Takip')
@section('content')
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Sanal Tur Hosting Takip</span>
</div>
<div class="page-header">
    <div>
        <h1 class="page-title">🎦 Sanal Tur Hosting Takip
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $stats['toplam'] ?? 0 }}</span>
        </h1>
        <div class="page-subtitle">Sanal tur hosting kayıtlarının tek listesi. Bitiş tarihlerini takip et.</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.sanaltur-hosting.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Kayıt Ekle</span>
        </a>
    </div>
</div>
<style>
    a.stat-card, a.mini-stat { text-decoration: none; color: inherit; transition: transform .12s, box-shadow .12s; }
    a.stat-card:hover, a.mini-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
</style>
@php $stUrl = route('admin.crm.sanaltur-hosting.index'); @endphp
<div class="stat-grid" style="margin-bottom:20px">
    <a href="{{ $stUrl }}" class="stat-card" title="Tümünü göster"
       style="{{ !request('kalan') && !request('sgrup') ? 'outline:2px solid #3b82f6' : '' }}">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="video"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Kayıt</div>
            <div class="stat-card-value">{{ number_format($stats['toplam'] ?? 0) }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Sanal tur hosting</div>
        </div>
    </a>
    <a href="{{ $stUrl }}?kalan=30" class="stat-card" title="30 gün içinde bitecekler"
       style="@if(($stats['gun30'] ?? 0) > 0)border-color:rgba(245,158,11,0.3);@endif{{ request('kalan') == '30' ? 'outline:2px solid #f59e0b' : '' }}">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">30 Gün Altı</div>
            <div class="stat-card-value" style="@if(($stats['gun30'] ?? 0) > 0)color:var(--warning)@endif">{{ number_format($stats['gun30'] ?? 0) }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Yakında yenilenmeli</div>
        </div>
    </a>
    <a href="{{ $stUrl }}?kalan=7" class="stat-card" title="7 gün içinde bitecekler"
       style="@if(($stats['gun7'] ?? 0) > 0)border-color:rgba(239,68,68,0.3);@endif{{ request('kalan') == '7' ? 'outline:2px solid #ef4444' : '' }}">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="flame"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">7 Gün Altı</div>
            <div class="stat-card-value" style="@if(($stats['gun7'] ?? 0) > 0)color:var(--danger)@endif">{{ number_format($stats['gun7'] ?? 0) }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Acil aksiyon!</div>
        </div>
    </a>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:var(--brand)">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Müşteri</div>
            <div class="stat-card-value">{{ number_format($stats['musteri'] ?? 0) }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Kayıt sahipleri</div>
        </div>
    </div>
</div>
<div class="mini-stat-grid" style="margin-bottom:20px">
    <a href="{{ $stUrl }}?sgrup=aktif" class="mini-stat success" title="Aktif kayıtlar"
       style="padding:10px 14px;{{ request('sgrup') == 'aktif' ? 'outline:2px solid #10b981' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">🟢</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Aktif</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['aktif'] ?? 0 }}</div>
        </div>
    </a>
    <a href="{{ $stUrl }}?sgrup=pasif" class="mini-stat danger" title="Pasif kayıtlar"
       style="padding:10px 14px;{{ request('sgrup') == 'pasif' ? 'outline:2px solid #ef4444' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">🔴</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Pasif</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['pasif'] ?? 0 }}</div>
        </div>
    </a>
</div>
<form method="GET" action="{{ route('admin.crm.sanaltur-hosting.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Arama</label>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Domain, e-posta, firma..." class="form-input" style="height:36px;font-size:13px">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Durum</label>
            <select name="status" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                <option value="1" {{ ($status ?? '') === '1' ? 'selected' : '' }}>🟢 Aktif</option>
                <option value="0" {{ ($status ?? '') === '0' ? 'selected' : '' }}>🔴 Pasif</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:end">
            <button type="submit" class="btn btn-primary btn-sm" style="height:36px">
                <i data-lucide="filter"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['search','status']))
                <a href="{{ route('admin.crm.sanaltur-hosting.index') }}" class="btn btn-ghost btn-sm" style="height:36px" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>
@if($domains->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="video" class="empty-state-icon"></i>
            <h4>Kayıt bulunamadı</h4>
            <p>
                @if(request()->hasAny(['search','status']))
                    Filtreyle eşleşen kayıt yok. <a href="{{ route('admin.crm.sanaltur-hosting.index') }}" style="color:var(--brand)">Filtreyi temizle</a>
                @else
                    Henüz sanal tur hosting kaydı yok. İlk kaydı ekle.
                @endif
            </p>
            <a href="{{ route('admin.crm.sanaltur-hosting.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>Yeni Kayıt Ekle</span>
            </a>
        </div>
    </div>
@else
    @php
        $statusMap = [
            '1' => ['label' => 'Aktif', 'class' => 'badge-success', 'icon' => '🟢'],
            1   => ['label' => 'Aktif', 'class' => 'badge-success', 'icon' => '🟢'],
            '0' => ['label' => 'Pasif', 'class' => 'badge-danger',  'icon' => '🔴'],
            0   => ['label' => 'Pasif', 'class' => 'badge-danger',  'icon' => '🔴'],
        ];
    @endphp
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Müşteri</th>
                        <th>Bitiş Tarihi</th>
                        <th>Kalan</th>
                        <th class="text-right">Tutar</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domains as $d)
                        @php
                            $statusKey = $d->status ?? '';
                            $st = $statusMap[$statusKey] ?? ['label' => $statusKey ?: '—', 'class' => 'badge-neutral', 'icon' => '❓'];
                            $kalanGun = null;
                            if (!empty($d->bitis)) {
                                try { $kalanGun = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($d->bitis)->startOfDay(), false); } catch (\Throwable $e) {}
                            }
                            $musteriAdi = $d->uye_firma ?: trim(($d->uye_ad ?? '').' '.($d->uye_soyad ?? '')) ?: ($d->uye_email ?? '—');
                            $isUrgent = $kalanGun !== null && $kalanGun <= 7 && $kalanGun >= 0;
                            $isExpired = $kalanGun !== null && $kalanGun < 0;
                        @endphp
                        <tr @if($isExpired)style="background:rgba(239,68,68,0.04)"@elseif($isUrgent)style="background:rgba(245,158,11,0.04)"@endif>
                            <td>
                                <a href="{{ route('admin.crm.sanaltur-hosting.show', ['id' => $d->id]) }}" style="font-weight:700;color:var(--text);text-decoration:none;font-size:13.5px">
                                    🎦 {{ $d->domain ?? '—' }}
                                </a>
                            </td>
                            <td>
                                @if(!empty($d->uye_id))
                                    <a href="{{ url('/admin/uyeler?search='.urlencode($d->uye_email ?? '')) }}" style="color:var(--text);text-decoration:none;font-size:12.5px;font-weight:600">
                                        {{ Str::limit($musteriAdi, 26) }}
                                    </a>
                                    @if(!empty($d->uye_email))
                                        <div style="font-size:10.5px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px">
                                            {{ $d->uye_email }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">— bağlı değil</span>
                                @endif
                            </td>
                            <td style="font-size:12px;white-space:nowrap">
                                @if(!empty($d->bitis))
                                    <span style="color:{{ $isExpired ? 'var(--danger)' : ($isUrgent ? 'var(--warning)' : 'var(--text-secondary)') }};font-weight:{{ $isExpired || $isUrgent ? '700' : '500' }}">
                                        {{ \Carbon\Carbon::parse($d->bitis)->format('d.m.Y') }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="font-size:11.5px;white-space:nowrap">
                                @if($kalanGun === null)
                                    <span style="color:var(--text-muted)">—</span>
                                @elseif($kalanGun < 0)
                                    <span class="badge badge-danger" style="font-size:10.5px">⚠️ {{ abs($kalanGun) }}g geçti</span>
                                @elseif($kalanGun == 0)
                                    <span class="badge badge-danger" style="font-size:10.5px">🔥 Bugün!</span>
                                @elseif($kalanGun <= 7)
                                    <span class="badge badge-danger" style="font-size:10.5px">🔥 {{ $kalanGun }}g kaldı</span>
                                @elseif($kalanGun <= 30)
                                    <span class="badge badge-warning" style="font-size:10.5px">⏰ {{ $kalanGun }}g kaldı</span>
                                @else
                                    <span style="color:var(--text-muted)">{{ $kalanGun }} gün</span>
                                @endif
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <span style="font-weight:600;color:var(--brand);font-size:12.5px">
                                    ₺{{ number_format((float)($d->fiyat ?? 0), 2, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $st['class'] }}" style="font-size:10.5px">
                                    {{ $st['icon'] }} {{ $st['label'] }}
                                </span>
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <div class="table-actions">
                                    <a href="{{ route('admin.crm.sanaltur-hosting.show', ['id' => $d->id]) }}" class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <a href="https://{{ $d->domain }}" target="_blank" class="table-action" title="Domain'i aç">
                                        <i data-lucide="external-link"></i>
                                    </a>
                                    <a href="{{ route('admin.crm.sanaltur-hosting.edit', ['id' => $d->id]) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.crm.sanaltur-hosting.destroy', ['id' => $d->id]) }}" method="POST" style="display:inline;margin:0" onsubmit="return confirm('Bu kaydı silmek istediğine emin misin?');">
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
    @if($domains->hasPages())
        <div style="margin-top:16px">
            {{ $domains->withQueryString()->links() }}
        </div>
    @endif
@endif
@endsection
