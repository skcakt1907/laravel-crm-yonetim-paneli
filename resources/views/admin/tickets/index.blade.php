@extends('admin._layout')

@section('title', 'Çalışan Ticketları')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Çalışan Ticketları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🎫 Çalışan Ticketları
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $tickets->total() }}</span>
        </h1>
        <div class="page-subtitle">İç ekibin birbirine açtığı görev/sorun ticketları</div>
    </div>
    <div class="page-actions">
        @if(\Route::has('admin.tickets.bildirimler'))
            <a href="{{ route('admin.tickets.bildirimler') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="bell"></i>
                <span>Bildirimler</span>
                @if(($bekleyen_sayisi ?? 0) > 0)
                    <span class="badge badge-warning" style="font-size:10px">{{ $bekleyen_sayisi }}</span>
                @endif
            </a>
        @endif
        <a href="{{ route('admin.tickets.olustur') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Ticket</span>
        </a>
    </div>
</div>

{{-- 4 Stat Card --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card" style="@if(($bekleyen_sayisi ?? 0) > 0)border-color:rgba(245,158,11,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bekleyen</div>
            <div class="stat-card-value" style="@if(($bekleyen_sayisi ?? 0) > 0)color:var(--warning)@endif">{{ $bekleyen_sayisi ?? 0 }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Çözülmüş</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $cozulmus_sayisi ?? 0 }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="user-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Benim Oluşturduklarım</div>
            <div class="stat-card-value">{{ $benim_olusturduklarim_sayisi ?? 0 }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="inbox"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bana Atananlar</div>
            <div class="stat-card-value">{{ $bana_atananlar_sayisi ?? 0 }}</div>
        </div>
    </div>
</div>

{{-- FİLTRELER --}}
<form method="GET" action="{{ route('admin.tickets.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Görünüm</label>
            <select name="filtre" class="form-select" style="height:36px;font-size:13px" onchange="this.form.submit()">
                <option value="tumu" {{ ($filtre ?? '') == 'tumu' ? 'selected' : '' }}>Tümü</option>
                <option value="benim_olusturduklarim" {{ ($filtre ?? '') == 'benim_olusturduklarim' ? 'selected' : '' }}>Benim Oluşturduklarım</option>
                <option value="bana_atananlar" {{ ($filtre ?? '') == 'bana_atananlar' ? 'selected' : '' }}>Bana Atananlar</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Durum</label>
            <select name="durum" class="form-select" style="height:36px;font-size:13px" onchange="this.form.submit()">
                <option value="tumu" {{ ($durum_filtre ?? '') == 'tumu' ? 'selected' : '' }}>Tümü</option>
                <option value="bekleyen" {{ ($durum_filtre ?? '') == 'bekleyen' ? 'selected' : '' }}>⏳ Bekleyen</option>
                <option value="cozulmus" {{ ($durum_filtre ?? '') == 'cozulmus' ? 'selected' : '' }}>✅ Çözülmüş</option>
                <option value="iptal" {{ ($durum_filtre ?? '') == 'iptal' ? 'selected' : '' }}>❌ İptal</option>
            </select>
        </div>
    </div>
</form>

@if($tickets->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="ticket" class="empty-state-icon"></i>
            <h4>Ticket bulunamadı</h4>
            <p>Bu kriterlerde ticket yok.</p>
            <a href="{{ route('admin.tickets.olustur') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>İlk Ticket'ı Oluştur</span>
            </a>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Başlık</th>
                        <th>Oluşturan</th>
                        <th>Atanan</th>
                        <th>Öncelik</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $t)
                        @php
                            $tDurum = (int)($t->durum ?? 0);
                            $durumMap = [
                                0 => ['label' => 'Bekliyor', 'class' => 'badge-warning', 'icon' => '⏳'],
                                1 => ['label' => 'Çözüldü', 'class' => 'badge-success', 'icon' => '✅'],
                                2 => ['label' => 'İptal', 'class' => 'badge-danger', 'icon' => '❌'],
                            ];
                            $dur = $durumMap[$tDurum] ?? $durumMap[0];

                            $oncelikMap = [
                                'acil' => 'badge-danger', 'yuksek' => 'badge-warning',
                                'normal' => 'badge-neutral', 'dusuk' => 'badge-success',
                            ];
                            $oncCls = $oncelikMap[$t->oncelik ?? 'normal'] ?? 'badge-neutral';

                            $tarihFmt = null;
                            if (!empty($t->tarih)) {
                                try { $tarihFmt = \Carbon\Carbon::parse($t->tarih)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr @if($tDurum === 0)style="background:rgba(245,158,11,0.03)"@endif>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $t->id }}</td>
                            <td>
                                <a href="{{ route('admin.tickets.detay', $t->id) }}" style="font-weight:600;color:var(--text);text-decoration:none">
                                    {{ \Illuminate\Support\Str::limit($t->baslik ?? '—', 50) }}
                                </a>
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary)">{{ $t->olusturan_adi ?? '—' }}</td>
                            <td style="font-size:12px;color:var(--text-secondary)">{{ $t->atananlar_metni ?? $t->atanan_adi ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $oncCls }}" style="font-size:10.5px">{{ ucfirst($t->oncelik ?? 'normal') }}</span>
                            </td>
                            <td style="font-size:11.5px;color:var(--text-muted);white-space:nowrap">
                                {{ $tarihFmt ?? '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $dur['class'] }}" style="font-size:10.5px">
                                    {{ $dur['icon'] }} {{ $dur['label'] }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.tickets.detay', $t->id) }}" class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <form action="{{ route('admin.tickets.sil', $t->id) }}" method="POST" onsubmit="return confirm('Ticket silinsin mi?');" style="margin:0;display:inline">
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

    @if($tickets->hasPages())
        <div style="margin-top:16px">{{ $tickets->links() }}</div>
    @endif
@endif

@endsection