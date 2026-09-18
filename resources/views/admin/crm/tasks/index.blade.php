@extends('admin._layout')

@section('title', 'Görevler')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Görevler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">✅ Görevler
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $tasks->total() }}</span>
        </h1>
        <div class="page-subtitle">Yapılacaklar listesi, sorumluluklar ve son tarihler</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.gorevler.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Görev</span>
        </a>
    </div>
</div>

@php
    // İstatistikler — bu sorgular sayfalama dışı toplam üzerinden
    $statsTotal = \App\Models\CRM\Task::count();
    $statsBekleyen = \App\Models\CRM\Task::where('durum', 'beklemede')->count();
    $statsDevam = \App\Models\CRM\Task::where('durum', 'devam')->count();
    $statsGeciken = \App\Models\CRM\Task::where('durum', '!=', 'tamamlandi')
        ->whereNotNull('son_tarih')
        ->where('son_tarih', '<', now())
        ->count();
@endphp

{{-- 4 Stat Card (tıklanabilir — listeyi filtreler) --}}
<style>
    a.stat-card, a.mini-stat { text-decoration: none; color: inherit; transition: transform .12s, box-shadow .12s; }
    a.stat-card:hover, a.mini-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
</style>
@php $gUrl = route('admin.crm.gorevler.index'); @endphp
<div class="stat-grid" style="margin-bottom:20px">
    <a href="{{ $gUrl }}" class="stat-card" title="Tüm görevleri göster"
       style="{{ !request('durum') && !request('yalnizca_geciken') ? 'outline:2px solid #3b82f6' : '' }}">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="list-checks"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Görev</div>
            <div class="stat-card-value">{{ $statsTotal }}</div>
        </div>
    </a>

    <a href="{{ $gUrl }}?durum=beklemede" class="stat-card" title="Bekleyen görevleri göster"
       style="{{ request('durum') == 'beklemede' ? 'outline:2px solid #64748b' : '' }}">
        <div class="stat-card-icon" style="background:rgba(148,163,184,0.18);color:#64748b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bekleyen</div>
            <div class="stat-card-value">{{ $statsBekleyen }}</div>
        </div>
    </a>

    <a href="{{ $gUrl }}?durum=devam" class="stat-card" title="Devam eden görevleri göster"
       style="{{ request('durum') == 'devam' ? 'outline:2px solid #f59e0b' : '' }}">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="play-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Devam Eden</div>
            <div class="stat-card-value">{{ $statsDevam }}</div>
        </div>
    </a>

    <a href="{{ $gUrl }}?yalnizca_geciken=1" class="stat-card" title="Geciken görevleri göster"
       style="@if($statsGeciken > 0)border-color:rgba(239,68,68,0.3);@endif{{ request('yalnizca_geciken') ? 'outline:2px solid #ef4444' : '' }}">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="alert-triangle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Geciken</div>
            <div class="stat-card-value" style="@if($statsGeciken > 0)color:var(--danger)@endif">{{ $statsGeciken }}</div>
        </div>
    </a>
</div>

{{-- FİLTRELER --}}
<form method="GET" action="{{ route('admin.crm.gorevler.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Durum</label>
            <select name="durum" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                <option value="beklemede" {{ request('durum') == 'beklemede' ? 'selected' : '' }}>📝 Beklemede</option>
                <option value="devam" {{ request('durum') == 'devam' ? 'selected' : '' }}>⏳ Devam</option>
                <option value="musteri_bekleniyor" {{ request('durum') == 'musteri_bekleniyor' ? 'selected' : '' }}>📞 Müşteri Bekleniyor</option>
                <option value="tamamlandi" {{ request('durum') == 'tamamlandi' ? 'selected' : '' }}>✅ Tamamlandı</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Sorumlu</label>
            <select name="atanan_id" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach($yoneticiler as $y)
                    <option value="{{ $y->id }}" {{ request('atanan_id') == $y->id ? 'selected' : '' }}>
                        {{ $y->adi ?: $y->kullaniciadi }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Müşteri</label>
            <select name="musteri_id" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('musteri_id') == $c->id ? 'selected' : '' }}>
                        {{ Str::limit($c->adi, 30) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Departman</label>
            <select name="departman" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                @foreach(\App\Models\CRM\Task::departmanlar() as $key => $label)
                    <option value="{{ $key }}" {{ request('departman') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;height:36px;padding:0 12px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md)">
                <input type="checkbox" name="yalnizca_geciken" value="1" {{ request('yalnizca_geciken') ? 'checked' : '' }} style="width:auto;margin:0">
                <span style="color:var(--danger);font-weight:600">⚠️ Sadece Geciken</span>
            </label>
        </div>

        <div style="display:flex;gap:6px;align-items:end">
            <button type="submit" class="btn btn-primary btn-sm" style="height:36px">
                <i data-lucide="filter"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['durum','atanan_id','musteri_id','departman','yalnizca_geciken']))
                <a href="{{ route('admin.crm.gorevler.index') }}" class="btn btn-ghost btn-sm" style="height:36px" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- TABLO --}}
@if($tasks->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="check-circle" class="empty-state-icon"></i>
            <h4>Görev bulunamadı</h4>
            <p>
                @if(request()->hasAny(['durum','atanan_id','musteri_id','departman','yalnizca_geciken']))
                    Filtreyle eşleşen görev yok. <a href="{{ route('admin.crm.gorevler.index') }}" style="color:var(--brand)">Filtreyi temizle</a>
                @else
                    Henüz görev oluşturulmamış. İlk görevini ekle!
                @endif
            </p>
            <a href="{{ route('admin.crm.gorevler.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>Yeni Görev Ekle</span>
            </a>
        </div>
    </div>
@else
    @php
        $durumMap = [
            'beklemede'  => ['label' => 'Beklemede',  'class' => 'badge-neutral', 'icon' => '📝'],
            'devam'      => ['label' => 'Devam',      'class' => 'badge-warning', 'icon' => '⏳'],
            'musteri_bekleniyor' => ['label' => 'Müşteri Bekleniyor', 'class' => 'badge-warning', 'icon' => '📞'],
            'tamamlandi' => ['label' => 'Tamamlandı', 'class' => 'badge-success', 'icon' => '✅'],
        ];
    @endphp

    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Konu</th>
                        <th>Departman</th>
                        <th>Müşteri</th>
                        <th>Sorumlu</th>
                        <th>Oluşturan</th>
                        <th>Son Tarih</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $t)
                        @php
                            $durum = $durumMap[$t->durum] ?? $durumMap['beklemede'];
                            $durumStil = [
                                'beklemede'  => 'background:rgba(148,163,184,.15);color:#64748b;border:1px solid rgba(148,163,184,.4)',
                                'devam'      => 'background:rgba(245,158,11,.15);color:#b45309;border:1px solid rgba(245,158,11,.4)',
                                'musteri_bekleniyor' => 'background:rgba(139,92,246,.15);color:#7c3aed;border:1px solid rgba(139,92,246,.4)',
                                'tamamlandi' => 'background:rgba(16,185,129,.15);color:#059669;border:1px solid rgba(16,185,129,.4)',
                            ][$t->durum] ?? 'background:var(--bg-subtle);color:var(--text-secondary);border:1px solid var(--border)';
                            $sonTarihGun = null;
                            if (!empty($t->son_tarih)) {
                                try { $sonTarihGun = (int) round(\Carbon\Carbon::parse($t->son_tarih)->startOfDay()->diffInDays(now()->startOfDay(), false)) * -1; } catch (\Throwable $e) {}
                            }
                            $isGeciken = $sonTarihGun !== null && $sonTarihGun < 0 && $t->durum !== 'tamamlandi';
                        @endphp
                        <tr @if($isGeciken)style="background:rgba(239,68,68,0.03)"@endif>
                            <td>
                                <a href="{{ route('admin.crm.gorevler.show', $t->id) }}" style="font-weight:600;color:var(--text);text-decoration:none">
                                    @if(($t->oncelik ?? 'normal') === 'yuksek')<span title="Yüksek öncelik">🔴</span>@elseif(($t->oncelik ?? 'normal') === 'dusuk')<span title="Düşük öncelik">🟢</span>@endif
                                    {{ Str::limit($t->konu, 60) }}
                                </a>
                                @if(!empty($t->aciklama))
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                        {{ Str::limit(strip_tags($t->aciklama), 80) }}
                                    </div>
                                @endif
                            </td>
                            <td style="font-size:12.5px;color:var(--text-secondary);white-space:nowrap">
                                {{ \App\Models\CRM\Task::departmanLabel($t->departman) }}
                            </td>
                            <td>
                                @if($t->musteri)
                                    <a href="{{ route('admin.crm.musteriler.show', $t->musteri->id) }}" style="color:var(--text);text-decoration:none;font-size:12.5px;font-weight:600">
                                        👤 {{ Str::limit($t->musteri->adi, 22) }}
                                    </a>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td style="font-size:12.5px;color:var(--text-secondary)">
                                @if($t->atanan)
                                    {{ $t->atanan->adi ?: $t->atanan->kullaniciadi }}
                                @else
                                    <span style="color:var(--text-muted)">Atanmamış</span>
                                @endif
                            </td>
                            <td style="font-size:12.5px;color:var(--text-secondary)">
                                @if($t->olusturan)
                                    {{ $t->olusturan->adi ?: $t->olusturan->kullaniciadi }}
                                @elseif(!empty($t->olusturan_adi))
                                    {{ $t->olusturan_adi }}
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px;white-space:nowrap">
                                @if(!empty($t->son_tarih))
                                    <span style="color:{{ $isGeciken ? 'var(--danger)' : 'var(--text-secondary)' }};font-weight:{{ $isGeciken ? '700' : '500' }}">
                                        {{ \Carbon\Carbon::parse($t->son_tarih)->format('d.m.Y') }}
                                    </span>
                                    @if($sonTarihGun !== null && $t->durum !== 'tamamlandi')
                                        @if($sonTarihGun < 0)
                                            <div style="font-size:10px;color:var(--danger);font-weight:700">⚠️ {{ abs($sonTarihGun) }}g geçti</div>
                                        @elseif($sonTarihGun == 0)
                                            <div style="font-size:10px;color:var(--warning);font-weight:700">⏰ Bugün</div>
                                        @elseif($sonTarihGun <= 3)
                                            <div style="font-size:10px;color:var(--warning);font-weight:600">⏰ {{ $sonTarihGun }}g kaldı</div>
                                        @endif
                                    @endif
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                @if(\Illuminate\Support\Facades\Route::has('admin.crm.gorevler.durum'))
                                    <form action="{{ route('admin.crm.gorevler.durum', $t->id) }}" method="POST" style="margin:0">
                                        @csrf
                                        <select name="durum" onchange="this.form.submit()" title="Durumu değiştir"
                                                style="{{ $durumStil }};font-size:11px;font-weight:700;border-radius:999px;padding:3px 8px;cursor:pointer;font-family:inherit;-webkit-appearance:none;appearance:none">
                                            <option value="beklemede" {{ $t->durum == 'beklemede' ? 'selected' : '' }}>📝 Beklemede ▾</option>
                                            <option value="devam" {{ $t->durum == 'devam' ? 'selected' : '' }}>⏳ Devam ▾</option>
                                            <option value="musteri_bekleniyor" {{ $t->durum == 'musteri_bekleniyor' ? 'selected' : '' }}>📞 Müşteri Bekleniyor ▾</option>
                                            <option value="tamamlandi" {{ $t->durum == 'tamamlandi' ? 'selected' : '' }}>✅ Tamamlandı ▾</option>
                                        </select>
                                    </form>
                                @else
                                    <span class="badge {{ $durum['class'] }}" style="font-size:10.5px">
                                        {{ $durum['icon'] }} {{ $durum['label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-right" style="white-space:nowrap">
                                <div class="table-actions">
                                    <form action="{{ route('admin.crm.gorevler.tamamla', $t->id) }}" method="POST" style="margin:0;display:inline">
                                        @csrf
                                        <button type="submit" class="table-action" style="color:var(--success,#10b981)" title="{{ $t->durum === 'tamamlandi' ? 'Tekrar aç' : 'Tamamlandı işaretle' }}">
                                            {{ $t->durum === 'tamamlandi' ? '↩' : '✅' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.crm.gorevler.show', $t->id) }}" class="table-action" title="Detay & Mesajlar">
                                        <i data-lucide="message-square"></i>
                                    </a>
                                    <a href="{{ route('admin.crm.gorevler.edit', $t->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.crm.gorevler.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Bu görevi silmek istediğine emin misin?');" style="margin:0;display:inline">
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

    @if($tasks->hasPages())
        <div style="margin-top:16px">
            {{ $tasks->withQueryString()->links() }}
        </div>
    @endif
@endif

@endsection