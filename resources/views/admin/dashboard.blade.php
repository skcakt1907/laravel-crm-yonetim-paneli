@extends('admin._layout')

@section('title', 'Anasayfa')

@section('content')

{{-- Hoş geldin başlığı --}}
<div class="page-header">
    <div>
        <div class="page-title">Hoş geldin, {{ session('admin_adi', 'Yönetici') }} 👋</div>
        <div class="page-subtitle">
            <strong style="color: var(--brand)">{{ $stats['online'] ?? 0 }}</strong> kişi şu an sitede,
            <strong style="color: var(--text)">{{ $stats['bugun_tekil'] ?? 0 }}</strong> tekil ziyaretçi bugün.
        </div>
    </div>
    <div class="page-actions">
        @if(\Illuminate\Support\Facades\Route::has('admin.musteri.ekle'))
        <a href="{{ route('admin.musteri.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Müşteri</span>
        </a>
        @endif
    </div>
</div>

{{-- STAT KARTLARI --}}
<div class="stat-grid">

    <div class="stat-card" style="--accent:#b8b62e;--soft:#f6f5e8">
        <div class="stat-ic"><i data-lucide="users"></i></div>
        <div class="stat-label"><span>Toplam Müşteri</span></div>
        <div class="stat-value">{{ number_format($stats['toplam_musteri'] ?? 0, 0, ',', '.') }}</div>
        <div class="stat-meta">
            <strong>{{ number_format($stats['aktif_musteri'] ?? 0, 0, ',', '.') }}</strong> aktif
        </div>
    </div>

    <div class="stat-card" style="--accent:#3b82f6;--soft:#eff6ff">
        <div class="stat-ic"><i data-lucide="shopping-bag"></i></div>
        <div class="stat-label"><span>Toplam Sipariş</span></div>
        <div class="stat-value">{{ number_format($stats['toplam_fatura'] ?? 0, 0, ',', '.') }}</div>
        <div class="stat-meta">
            <strong style="color: var(--success)">{{ number_format($stats['odenen_fatura'] ?? 0, 0, ',', '.') }}</strong> ödendi
            <span style="color: var(--text-muted)"> · </span>
            <strong style="color: var(--warning)">{{ $stats['odenmemis_fatura'] ?? 0 }}</strong> bekliyor
        </div>
    </div>

    <div class="stat-card" style="--accent:#8b5cf6;--soft:#f5f3ff">
        <div class="stat-ic"><i data-lucide="handshake"></i></div>
        <div class="stat-label"><span>Yetkili Bayi</span></div>
        <div class="stat-value">{{ $stats['toplam_bayi'] ?? 0 }}</div>
        <div class="stat-meta">
            <strong>{{ $stats['toplam_bayi_rol'] ?? 0 }}</strong> rol aktif
        </div>
    </div>

    <div class="stat-card" style="--accent:#10b981;--soft:#ecfdf5">
        <div class="stat-ic"><i data-lucide="bar-chart-3"></i></div>
        <div class="stat-label"><span>Bu Ay Trafik</span></div>
        <div class="stat-value">{{ number_format($stats['buay_cogul'] ?? 0, 0, ',', '.') }}</div>
        <div class="stat-meta">
            <strong>{{ number_format($stats['buay_tekil'] ?? 0, 0, ',', '.') }}</strong> tekil
        </div>
    </div>

</div>

{{-- ÜÇLÜ KART — FATURA ÖZETİ --}}
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">

    <div class="stat-card" style="--accent:#f59e0b;--soft:#fffbeb">
        <div class="stat-ic"><i data-lucide="file-text"></i></div>
        <div class="stat-label"><span>Bu Ay Fatura</span></div>
        <div class="stat-value">{{ $stats['buay_fatura_adet'] ?? 0 }}</div>
        <div class="stat-meta">
            {{ \Carbon\Carbon::now()->translatedFormat('F Y') }} ayında kesildi
        </div>
    </div>

    <div class="stat-card" style="--accent:#16a34a;--soft:#f0fdf4">
        <div class="stat-ic"><i data-lucide="check-circle-2"></i></div>
        <div class="stat-label"><span>Onaylanmış</span></div>
        <div class="stat-value text-success">{{ $stats['buay_fatura_onayli'] ?? 0 }}</div>
        <div class="stat-meta">
            <strong>{{ ($stats['buay_fatura_adet'] ?? 0) > 0 ? round(($stats['buay_fatura_onayli'] ?? 0) / $stats['buay_fatura_adet'] * 100) : 0 }}%</strong> onay oranı
        </div>
    </div>

</div>

{{-- ZİYARETÇİ İSTATİSTİKLERİ --}}
<div class="card mb-6">
    <div class="card-header">
        <div class="card-title">
            📈 Ziyaretçi İstatistikleri
        </div>
    </div>
    <div class="stat-grid" style="margin-bottom:0">
        <div class="stat-card" style="text-align:center">
            <div class="stat-value" style="color: var(--success)">{{ $stats['online'] ?? 0 }}</div>
            <div class="stat-label" style="justify-content:center">Online</div>
        </div>
        <div class="stat-card" style="text-align:center">
            <div class="stat-value">{{ $stats['bugun_tekil'] ?? 0 }}</div>
            <div class="stat-label" style="justify-content:center">Bugün</div>
        </div>
        <div class="stat-card" style="text-align:center">
            <div class="stat-value">{{ $stats['dun_tekil'] ?? 0 }}</div>
            <div class="stat-label" style="justify-content:center">Dün</div>
        </div>
        <div class="stat-card" style="text-align:center">
            <div class="stat-value">{{ number_format($stats['toplam_tekil'] ?? 0, 0, ',', '.') }}</div>
            <div class="stat-label" style="justify-content:center">Toplam</div>
        </div>
    </div>
</div>

{{-- SON ÜYELER + EKİP GÖREVLERİ + AYIN ELEMANI --}}
<div style="display:grid;grid-template-columns:1.1fr 1.1fr 0.8fr;gap:16px;margin-bottom:24px" class="dual-grid">

    {{-- Son Üyeler --}}
    <div class="card" style="padding:0">
        <div class="card-header" style="padding:20px 20px 16px;border: 0">
            <div class="card-title">
                <i data-lucide="user-plus" style="width:16px;height:16px;display:inline;vertical-align:-3px;margin-right:4px"></i>
                Son Üyeler
            </div>
            @if(\Illuminate\Support\Facades\Route::has('admin.uyeler.index'))
            <a href="{{ route('admin.uyeler.index') }}" class="btn btn-ghost btn-sm">
                Tümü
                <i data-lucide="arrow-right"></i>
            </a>
            @endif
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Üye</th>
                        <th class="text-right">Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($son_uyeler ?? [] as $u)
                        @php
                            $detayUrl = \Illuminate\Support\Facades\Route::has('admin.uyeler.detay')
                                ? route('admin.uyeler.detay', $u->id ?? 0)
                                : '#';
                            $uTarih = '—';
                            if (!empty($u->tarih)) {
                                try {
                                    $uTarih = is_numeric($u->tarih)
                                        ? \Carbon\Carbon::createFromTimestamp((int) $u->tarih)->format('d.m.Y')
                                        : \Carbon\Carbon::parse($u->tarih)->format('d.m.Y');
                                } catch (\Throwable $e) { $uTarih = '—'; }
                            }
                        @endphp
                        <tr class="clickable" onclick="window.location='{{ $detayUrl }}'">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="user-avatar" style="width:32px;height:32px;font-size:12px">{{ strtoupper(mb_substr($u->ad ?? 'M', 0, 1)) }}</div>
                                    <div style="min-width:0">
                                        <strong style="display:block">{{ $u->ad ?? '—' }} {{ $u->soyad ?? '' }}</strong>
                                        <div class="text-muted" style="font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px">{{ $u->email ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right text-muted text-sm">{{ $uTarih }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted" style="padding:32px">Henüz üye yok</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ekip Görevleri (Kanban) --}}
    <div class="card" style="padding:0">
        <div class="card-header" style="padding:20px 20px 16px;border: 0">
            <div class="card-title">
                <i data-lucide="kanban-square" style="width:16px;height:16px;display:inline;vertical-align:-3px;margin-right:4px"></i>
                Ekip Görevleri
            </div>
            @if(\Illuminate\Support\Facades\Route::has('admin.crm.kanban.index'))
            <a href="{{ route('admin.crm.kanban.index') }}" class="btn btn-ghost btn-sm">
                Kanbana Git
                <i data-lucide="arrow-right"></i>
            </a>
            @endif
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Personel</th>
                        <th class="text-right">Açık Görev</th>
                        <th class="text-right">Bu Ay Tamamlanan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ekipGorevleri ?? [] as $eg)
                        @php
                            $kanbanUrl = \Illuminate\Support\Facades\Route::has('admin.crm.kanban.index')
                                ? route('admin.crm.kanban.index')
                                : '#';
                        @endphp
                        <tr class="clickable" onclick="window.location='{{ $kanbanUrl }}'" title="Kanban panolarına git">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="user-avatar" style="width:32px;height:32px;font-size:12px">{{ strtoupper(mb_substr($eg->adi ?? 'P', 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $eg->adi ?? '—' }}</strong>
                                        @if(!empty($eg->kadi))<div style="font-size:11px;color: var(--text-muted)">{{ '@'.$eg->kadi }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-right">
                                @if(($eg->acik ?? 0) > 0)
                                    <span class="badge badge-warning">{{ $eg->acik }} açık</span>
                                @else
                                    <span class="badge badge-neutral">0</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="badge {{ ($eg->tamam_ay ?? 0) > 0 ? 'badge-success' : 'badge-neutral' }}">{{ $eg->tamam_ay ?? 0 }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted" style="padding:32px">Açık kanban görevi yok</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ayın Elemanı --}}
    <div class="card" style="padding:0;display:flex;flex-direction:column">
        <div class="card-header" style="padding:20px 20px 16px;border: 0">
            <div class="card-title">
                <i data-lucide="trophy" style="width:16px;height:16px;display:inline;vertical-align:-3px;margin-right:4px;color: var(--warning)"></i>
                Ayın Elemanı
            </div>
            <span style="font-size:11px;color: var(--text-muted)">{{ now()->translatedFormat('F Y') }}</span>
        </div>
        @if(!empty($ayinElemani))
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:12px 20px 28px">
            <div style="position:relative;margin-bottom:12px">
                <div class="user-avatar" style="width:72px;height:72px;font-size:26px">{{ strtoupper(mb_substr($ayinElemani->adi ?? 'P', 0, 1)) }}</div>
                <span style="position:absolute;bottom:-6px;right:-6px;font-size:24px">🏆</span>
            </div>
            <strong style="font-size:16px">{{ $ayinElemani->adi }}</strong>
            @if(!empty($ayinElemani->kadi))<div style="font-size:12px;color: var(--text-muted)">{{ '@'.$ayinElemani->kadi }}</div>@endif
            <div style="display:flex;gap:16px;margin-top:14px">
                <div style="text-align:center">
                    <div style="font-size:20px;font-weight:800;color: var(--success)">{{ $ayinElemani->tamam_ay }}</div>
                    <div style="font-size:10.5px;color: var(--text-muted);letter-spacing:.4px;text-transform:uppercase">Tamamlanan</div>
                </div>
                <div style="text-align:center">
                    <div style="font-size:20px;font-weight:800;color: var(--warning)">{{ $ayinElemani->acik }}</div>
                    <div style="font-size:10.5px;color: var(--text-muted);letter-spacing:.4px;text-transform:uppercase">Devam Eden</div>
                </div>
            </div>
        </div>
        @else
        <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:32px;color: var(--text-muted);font-size:13px;text-align:center">
            Bu ay henüz tamamlanan görev yok 🏁
        </div>
        @endif
    </div>

</div>

{{-- SON DUYURULAR --}}
@if(\Illuminate\Support\Facades\Schema::hasTable('duyurular'))
@php
    $dash_duyurular = \Illuminate\Support\Facades\DB::table('duyurular')
        ->orderByDesc('id')->limit(5)->get();
@endphp
<div class="card mb-6" style="padding:0">
    <div class="card-header" style="padding:20px 20px 16px;border: 0">
        <div class="card-title">
            <i data-lucide="megaphone" style="width:16px;height:16px;display:inline;vertical-align:-3px;margin-right:4px"></i>
            Son Duyurular
        </div>
        @if(\Illuminate\Support\Facades\Route::has('admin.duyurular.index'))
        <a href="{{ route('admin.duyurular.index') }}" class="btn btn-ghost btn-sm">
            Tümü
            <i data-lucide="arrow-right"></i>
        </a>
        @endif
    </div>
    <div style="padding:0 8px 8px">
        @forelse($dash_duyurular as $d)
        @php
            $dUrl = \Illuminate\Support\Facades\Route::has('admin.duyurular.goster')
                ? route('admin.duyurular.goster', $d->id) : '#';
        @endphp
        <a href="{{ $dUrl }}" style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;border-radius: 12px;text-decoration:none;color: inherit;transition:.15s" onmouseover="this.style.background='#fafbf5'" onmouseout="this.style.background='transparent'">
            <div style="width:38px;height:38px;border-radius: 11px;background: #f6f5e8;color: var(--brand-hover);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i data-lucide="bell" style="width:18px;height:18px"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;color: var(--text);font-size:14px;margin-bottom:3px">{{ $d->baslik }}</div>
                <div style="color: var(--text-muted);font-size:12.5px;line-height:1.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ \Illuminate\Support\Str::limit(strip_tags($d->icerik), 90) }}</div>
                <div style="color: var(--text-muted);font-size:11.5px;margin-top:5px">
                    {{ $d->olusturan_adi ?? 'Yönetici' }}
                    @if(!empty($d->created_at)) · {{ \Carbon\Carbon::parse($d->created_at)->diffForHumans() }}@endif
                    @if(($d->hedef_tip ?? '')==='tum') · <span style="color: var(--brand-hover)">Tüm yöneticiler</span>@else · <span style="color: var(--brand-hover)">Seçili</span>@endif
                </div>
            </div>
        </a>
        @empty
        <div class="text-center text-muted" style="padding:32px">Henüz duyuru yok</div>
        @endforelse
    </div>
</div>
@endif

{{-- AYIN İŞ ORTAKLARI — En çok kazandıran müşteriler --}}
@if(\Illuminate\Support\Facades\Schema::hasTable('faturalar') && \Illuminate\Support\Facades\Schema::hasTable('uyeler'))
@php
    $dash_ayBas = \Carbon\Carbon::now()->startOfMonth()->toDateTimeString();
    $dash_ayBit = \Carbon\Carbon::now()->endOfMonth()->toDateTimeString();
    $dash_isodaklari = \Illuminate\Support\Facades\DB::table('faturalar as f')
        ->join('uyeler as u', 'f.uyeid', '=', 'u.id')
        ->whereBetween('f.tarih', [$dash_ayBas, $dash_ayBit])
        ->where('f.durum', '!=', 2)
        ->select('u.ad', 'u.soyad', 'u.firmaadi', 'u.email',
            \Illuminate\Support\Facades\DB::raw('SUM(f.tutar) as toplam'),
            \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN f.durum=1 THEN f.tutar ELSE 0 END) as tahsil'),
            \Illuminate\Support\Facades\DB::raw('COUNT(f.id) as adet'))
        ->groupBy('u.id','u.ad','u.soyad','u.firmaadi','u.email')
        ->orderByDesc('toplam')->limit(5)->get();
    $dash_maxCiro = $dash_isodaklari->max('toplam') ?: 1;
@endphp
<div class="card mb-6" style="padding:0">
    <div class="card-header" style="padding:20px 20px 16px;border: 0">
        <div class="card-title">
            <i data-lucide="trophy" style="width:16px;height:16px;display:inline;vertical-align:-3px;margin-right:4px"></i>
            Ayın İş Ortakları — En Çok Kazandıranlar
        </div>
        @if(\Illuminate\Support\Facades\Route::has('admin.is-ortaklari.index'))
        <a href="{{ route('admin.is-ortaklari.index') }}" class="btn btn-ghost btn-sm">
            Tümü
            <i data-lucide="arrow-right"></i>
        </a>
        @endif
    </div>
    <div style="padding:0 16px 16px">
        @forelse($dash_isodaklari as $i => $m)
        @php
            $dmAd = trim((string)($m->firmaadi ?? ''));
            if ($dmAd === '') $dmAd = trim(($m->ad ?? '').' '.($m->soyad ?? ''));
            if ($dmAd === '') $dmAd = $m->email ?: 'Müşteri';
        @endphp
        <div style="display:flex;align-items:center;gap:13px;padding:11px 8px;border-bottom: 1px solid var(--border, #f1f3f5)">
            <div style="width:28px;height:28px;border-radius: 8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0;background: {{ $i===0?'#fef3c7':($i===1?'#e5e7eb':($i===2?'#fde9d8':'#f1f3f5')) }};color: {{ $i===0?'#b45309':($i===1?'#475569':($i===2?'#9a3412':'#64748b')) }}">{{ $i+1 }}</div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;color: var(--text);font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $dmAd }}</div>
                <div style="height:6px;background: var(--bg-subtle);border-radius: 4px;margin-top:5px;overflow:hidden">
                    <div style="height:100%;width:{{ $dash_maxCiro>0 ? ($m->toplam/$dash_maxCiro*100) : 0 }}%;background: var(--brand);border-radius: 4px"></div>
                </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:11px;color: var(--text-muted)">{{ $m->adet }} fatura</div>
            </div>
        </div>
        @empty
        <div class="text-center text-muted" style="padding:32px">Bu ay fatura yok</div>
        @endforelse
    </div>
</div>
@endif

<style>
    /* ── İstatistik kartları — bayi panel KPI tasarımıyla aynı dil ── */
    .stat-grid .stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 4px 18px rgba(0,0,0,.04);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .stat-grid .stat-card::before {
        content: "";
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 5px;
        background: var(--accent, var(--brand));
    }
    .stat-grid .stat-card:hover {
        transform: translateY(-4px);
        border-color: var(--accent, var(--brand));
        box-shadow: 0 16px 32px -12px var(--accent, rgba(0,0,0,.25));
    }
    .stat-grid .stat-card .stat-ic {
        width: 46px; height: 46px;
        border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        background: var(--soft, rgba(0,0,0,.04));
        color: var(--accent, var(--brand));
        margin-bottom: 14px;
    }
    .stat-grid .stat-card .stat-ic svg { width: 22px; height: 22px; }
    .stat-grid .stat-card .stat-label {
        margin-bottom: 6px;
        color: var(--text-muted);
    }
    .stat-grid .stat-card .stat-value {
        font-size: 28px;
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 6px;
        word-break: break-word;
    }

    /* Grid item'lar min-width:auto yüzünden tablo genişliğini taşırıyordu → yatay scrollbar.
       min-width:0 ile sütunlar 1fr'e sığar, tablo kart içinde kayar. */
    .dual-grid { min-width: 0; }
    .dual-grid > .card { min-width: 0; overflow: hidden; }
    .dual-grid .table-scroll { overflow-x: auto; }
    @media (max-width: 900px) {
        .dual-grid { grid-template-columns: 1fr !important; }
    }
</style>

@endsection