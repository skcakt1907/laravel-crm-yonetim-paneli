@extends('admin._layout')

@section('title', 'Fırsatlar')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Fırsatlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">📈 Fırsatlar
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $opportunities->total() }}</span>
        </h1>
        <div class="page-subtitle">Satış fırsatlarını takip et, durumlarını yönet</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.firsatlar.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Fırsat</span>
        </a>
    </div>
</div>

{{-- 4 Stat Card (controller'dan geliyor) --}}
@php
    $stats = $statCards ?? [];
    $iconMap = [
        'TOPLAM FIRSAT' => 'trending-up',
        'AÇIK' => 'unlock',
        'KAZANILAN' => 'award',
        'POTANSİYEL' => 'dollar-sign',
    ];
    // Hex → rgba helper (8-digit hex render sorununu çözer)
    $hexToRgba = function($hex, $alpha = 0.12) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) return "rgba(184,182,46,{$alpha})";
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba({$r},{$g},{$b},{$alpha})";
    };
@endphp
<div class="stat-grid" style="margin-bottom:20px">
    @foreach($stats as $stat)
        @php
            $renkHex = $stat['color'] ?? '#b8b62e';
            $bgColor = $hexToRgba($renkHex, 0.12);
        @endphp
        <div class="stat-card">
            <div class="stat-card-icon" style="background:{{ $bgColor }};color:{{ $renkHex }}">
                <i data-lucide="{{ $iconMap[$stat['label']] ?? 'circle' }}"></i>
            </div>
            <div class="stat-card-body">
                <div class="stat-card-label">{{ $stat['label'] }}</div>
                <div class="stat-card-value">{{ $stat['value'] }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- FİLTRELER --}}
<form method="GET" action="{{ route('admin.crm.firsatlar.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Arama</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Başlık veya açıklama..." class="form-input" style="height:36px;font-size:13px">
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Müşteri</label>
            <select name="musteri_id" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('musteri_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->adi }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Pipeline</label>
            <select name="pipeline_id" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach($pipelines as $p)
                    <option value="{{ $p->id }}" {{ request('pipeline_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->adi ?? $p->ad ?? 'Pipeline #'.$p->id }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Sorumlu</label>
            <select name="sorumlu_id" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach($yoneticiler as $y)
                    <option value="{{ $y->id }}" {{ request('sorumlu_id') == $y->id ? 'selected' : '' }}>
                        {{ $y->adi ?: $y->kullaniciadi }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Durum</label>
            <select name="durum" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach($durumlar as $key => $label)
                    <option value="{{ $key }}" {{ request('durum') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;gap:6px;align-items:end">
            <button type="submit" class="btn btn-primary btn-sm" style="height:36px">
                <i data-lucide="filter"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['search','musteri_id','pipeline_id','sorumlu_id','durum']))
                <a href="{{ route('admin.crm.firsatlar.index') }}" class="btn btn-ghost btn-sm" style="height:36px" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- TABLO --}}
@if($opportunities->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="trending-up" class="empty-state-icon"></i>
            <h4>Fırsat bulunamadı</h4>
            <p>
                @if(request()->hasAny(['search','musteri_id','pipeline_id','sorumlu_id','durum']))
                    Filtreyle eşleşen fırsat yok. <a href="{{ route('admin.crm.firsatlar.index') }}" style="color:var(--brand)">Filtreyi temizle</a>
                @else
                    Henüz fırsat kaydedilmemiş. İlk fırsatını oluştur!
                @endif
            </p>
            <a href="{{ route('admin.crm.firsatlar.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>Yeni Fırsat Ekle</span>
            </a>
        </div>
    </div>
@else
    @php
        $durumMap = [
            'acik'       => ['label' => 'Açık',       'class' => 'badge-warning', 'icon' => '🔓'],
            'beklemede'  => ['label' => 'Beklemede',  'class' => 'badge-neutral', 'icon' => '⏳'],
            'kazanildi'  => ['label' => 'Kazanıldı',  'class' => 'badge-success', 'icon' => '🏆'],
            'kaybedildi' => ['label' => 'Kaybedildi', 'class' => 'badge-danger',  'icon' => '❌'],
        ];
        $oncelikMap = [
            1 => ['icon' => '🟢', 'label' => 'Düşük'],
            2 => ['icon' => '🟡', 'label' => 'Normal'],
            3 => ['icon' => '🟠', 'label' => 'Yüksek'],
            4 => ['icon' => '🔴', 'label' => 'Acil'],
            5 => ['icon' => '⚫', 'label' => 'Kritik'],
        ];
        $paraSimgesi = ['TRY'=>'₺', 'USD'=>'$', 'EUR'=>'€', 'AED'=>'د.إ'];
    @endphp

    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Müşteri</th>
                        <th>Aşama</th>
                        <th class="text-right">Tutar</th>
                        <th>Sorumlu</th>
                        <th>Durum</th>
                        <th>Öncelik</th>
                        <th>Kapanış</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($opportunities as $o)
                        @php
                            $durum = $durumMap[$o->durum] ?? $durumMap['acik'];
                            $oncelik = $oncelikMap[(int)($o->oncelik ?? 2)] ?? $oncelikMap[2];
                            $sym = $paraSimgesi[$o->para_birimi] ?? '';
                            $kapanisGun = null;
                            if (!empty($o->beklenen_kapanis)) {
                                try { $kapanisGun = \Carbon\Carbon::parse($o->beklenen_kapanis)->diffInDays(now(), false) * -1; } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.crm.firsatlar.show', $o->id) }}" style="font-weight:600;color:var(--text);text-decoration:none">
                                    {{ Str::limit($o->baslik, 50) }}
                                </a>
                                @if(!empty($o->aciklama))
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                        {{ Str::limit(strip_tags($o->aciklama), 60) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($o->musteri)
                                    <a href="{{ route('admin.crm.musteriler.show', $o->musteri->id) }}" style="color:var(--text-secondary);text-decoration:none;font-size:12.5px">
                                        {{ Str::limit($o->musteri->adi, 24) }}
                                    </a>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:12.5px;color:var(--text-secondary)">{{ $o->stage->adi ?? $o->stage->ad ?? '—' }}</div>
                                @if($o->pipeline)
                                    <div style="font-size:10.5px;color:var(--text-muted)">{{ $o->pipeline->adi ?? $o->pipeline->ad }}</div>
                                @endif
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <span style="font-weight:700;color:var(--brand)">{{ $sym }}{{ number_format($o->tutar ?? 0, 2, ',', '.') }}</span>
                                <div style="font-size:10px;color:var(--text-muted)">{{ $o->para_birimi ?? 'TRY' }}</div>
                            </td>
                            <td style="font-size:12.5px;color:var(--text-secondary)">
                                {{ $o->sorumlu->adi ?? $o->sorumlu->kullaniciadi ?? '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $durum['class'] }}" style="font-size:10.5px">
                                    {{ $durum['icon'] }} {{ $durum['label'] }}
                                </span>
                            </td>
                            <td style="font-size:12.5px">
                                <span title="{{ $oncelik['label'] }}">{{ $oncelik['icon'] }}</span>
                                <span style="color:var(--text-muted);font-size:10.5px;margin-left:2px">{{ $oncelik['label'] }}</span>
                            </td>
                            <td style="font-size:12px">
                                @if(!empty($o->beklenen_kapanis))
                                    <span style="color:var(--text-secondary)">
                                        {{ \Carbon\Carbon::parse($o->beklenen_kapanis)->format('d.m.Y') }}
                                    </span>
                                    @if($kapanisGun !== null)
                                        @if($kapanisGun < 0 && !in_array($o->durum, ['kazanildi','kaybedildi']))
                                            <div style="font-size:10px;color:var(--danger);font-weight:600">⚠️ {{ abs($kapanisGun) }}g geçti</div>
                                        @elseif($kapanisGun <= 7 && $kapanisGun >= 0 && !in_array($o->durum, ['kazanildi','kaybedildi']))
                                            <div style="font-size:10px;color:var(--warning);font-weight:600">⏰ {{ $kapanisGun }}g kaldı</div>
                                        @endif
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <div class="table-actions">
                                    <a href="{{ route('admin.crm.firsatlar.show', $o->id) }}" class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <a href="{{ route('admin.crm.firsatlar.edit', $o->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.crm.firsatlar.destroy', $o->id) }}" method="POST" onsubmit="return confirm('Bu fırsatı silmek istediğine emin misin?');" style="margin:0;display:inline">
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

    @if($opportunities->hasPages())
        <div style="margin-top:16px">
            {{ $opportunities->withQueryString()->links() }}
        </div>
    @endif
@endif

@endsection