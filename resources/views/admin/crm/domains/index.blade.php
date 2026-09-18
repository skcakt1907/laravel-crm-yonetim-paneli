@extends('admin._layout')

@section('title', 'Domain & Hosting Takip')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Domain &amp; Hosting Takip</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🌐 Domain &amp; Hosting Takip
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $stats['toplam'] ?? 0 }}</span>
        </h1>
        <div class="page-subtitle">Online sipariş + manuel + Metunic domainlerinin tek listesi. Bitiş tarihlerini takip et.</div>
    </div>
    <div class="page-actions">
        @if(\Route::has('admin.manuel-satis.index'))
            <a href="{{ route('admin.manuel-satis.index') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="shopping-cart"></i>
                <span>Manuel Satış</span>
            </a>
        @endif
        {{-- KÂR RAPORU / TOPLU TUTAR
             Iki ekran da menude yoktu ve yalnizca birbirine baglanmisti:
             kar raporuna Toplu Tutar'dan, Toplu Tutar'a kar raporundan.
             Yani URL elle yazilmadan ulasilamiyordu. Girisi buraya kondu. --}}
        @if(\Route::has('admin.crm.domains.kar-raporu'))
            <a href="{{ route('admin.crm.domains.kar-raporu') }}"
               class="btn btn-secondary btn-sm" title="Domain kâr/zarar raporu">
                <i data-lucide="trending-up"></i>
                <span>Kâr Raporu</span>
            </a>
        @endif
        @if(\Route::has('admin.crm.domains.toplu-tutar'))
            <a href="{{ route('admin.crm.domains.toplu-tutar') }}"
               class="btn btn-secondary btn-sm" title="Tutarı girilmemiş domainlere toplu tutar gir">
                <i data-lucide="coins"></i>
                <span>Toplu Tutar</span>
            </a>
        @endif
        @if(\Route::has('admin.crm.domains.metunic-sync'))
            <form action="{{ route('admin.crm.domains.metunic-sync') }}" method="POST" style="display:inline;margin:0">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" title="Metunic'ten domainleri canlı çek ve kaydet">
                    <i data-lucide="refresh-cw"></i>
                    <span>Metunic Senkronla</span>
                </button>
            </form>
        @endif
        @if(\Route::has('admin.crm.domains.disa-aktar'))
            {{-- Ekrandaki filtreler korunarak indirilir: ne görüyorsan o iner --}}
            <a href="{{ route('admin.crm.domains.disa-aktar', request()->query()) }}"
               class="btn btn-secondary btn-sm" title="Listeyi CSV olarak indir (Excel'de açılır)">
                <i data-lucide="download"></i>
                <span>CSV İndir</span>
            </a>
        @endif
        <a href="{{ route('admin.crm.domains.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Manuel Domain Ekle</span>
        </a>
    </div>
</div>

{{-- 4 Stat Card --}}
<style>
    a.stat-card, a.mini-stat { text-decoration: none; color: inherit; transition: transform .12s, box-shadow .12s; }
    a.stat-card:hover, a.mini-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
</style>
@php $domUrl = route('admin.crm.domains.index'); @endphp
<div class="stat-grid" style="margin-bottom:20px">
    <a href="{{ $domUrl }}" class="stat-card" title="Tüm domainleri göster"
       style="{{ !request('kalan') && !request('sgrup') && !request('kaynak') ? 'outline:2px solid #3b82f6' : '' }}">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="globe"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Domain</div>
            <div class="stat-card-value">{{ number_format($stats['toplam'] ?? 0) }}</div>
            {{-- "x sipariş + y manuel" alt yazısı kaldırıldı (04.08.2026):
                 online sipariş akışı kullanılmadığı için hep "0 sipariş" yazıyordu. --}}
        </div>
    </a>

    <a href="{{ $domUrl }}?kalan=30" class="stat-card" title="30 gün içinde bitecek domainleri göster"
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

    <a href="{{ $domUrl }}?kalan=7" class="stat-card" title="7 gün içinde bitecek domainleri göster"
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

    {{-- SÜRESİ GEÇMİŞ — eskiden hiçbir kartta görünmüyordu, "Aktif" içinde sayılıyordu --}}
    <a href="{{ $domUrl }}?kalan=gecmis" class="stat-card" title="Süresi dolmuş domainleri göster"
       style="@if(($stats['gecmis'] ?? 0) > 0)border-color:rgba(239,68,68,0.45);@endif{{ request('kalan') === 'gecmis' ? 'outline:2px solid #ef4444' : '' }}">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="calendar-x"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Süresi Geçmiş</div>
            <div class="stat-card-value" style="@if(($stats['gecmis'] ?? 0) > 0)color:var(--danger)@endif">{{ number_format($stats['gecmis'] ?? 0) }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Bitiş tarihi geçti</div>
        </div>
    </a>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:var(--brand)">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Müşteri</div>
            <div class="stat-card-value">{{ number_format($stats['musteri'] ?? 0) }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Domain sahipleri</div>
        </div>
    </div>
</div>

{{-- Ek Mini Stats (durum bazlı) --}}
{{--
    DURUM / KAYNAK KUTUCUKLARI (04.08.2026)

    Bu kartlar boş yere yer kaplıyordu:
      • "Aktif" toplamla birebir aynıydı (her kayda domain_durum='kayitli' yazılıyor)
      • "Bekleyen" ve "Hata" hiçbir zaman dolamıyordu — o durumlar (pending/paid/
        failed) online sipariş akışından gelir, domain_orders tablosu boş
      • "Online Sipariş" ve "Metunic" de aynı sebeple hep 0

    Silmek yerine KOŞULLU yapıldı: değeri olan kart görünür. İleride online
    satış açılır ya da Metunic senkronu çalışırsa kartlar kendiliğinden döner.

    NOT: "Hata/Süresi Dolan" ile üstteki "Süresi Geçmiş" farklı şeylerdir —
    bu domain_durum sütununa, üstteki gerçek bitiş tarihine bakar. Süresi
    dolmuş domainler için üstteki karta güven.
--}}
@php
    /*
     * Kartlar 0 olsa da DURUR (istek: "belki sonra olur, kalsın").
     * Tek istisna "Aktif" — değeri toplamla birebir aynı olduğu sürece
     * hiçbir bilgi vermiyor, o yüzden ancak farklıysa görünür.
     *
     * Etiket "Hata/Süresi Dolan" değil sadece "Hata": süresi dolanlar
     * yukarıdaki "Süresi Geçmiş" kartında, bu kart domain_durum
     * sütunundaki hata durumunu gösteriyor. İkisi farklı şey.
     */
    $durumKartlari = array_filter([
        ['sgrup=aktif',    'success', '🟢', 'Aktif',          $stats['aktif']    ?? 0, ($stats['aktif'] ?? 0) !== ($stats['toplam'] ?? 0)],
        ['sgrup=bekleyen', 'warning', '⏳', 'Bekleyen',       $stats['bekleyen'] ?? 0, true],
        ['sgrup=hata',     'danger',  '❌', 'Hata',           $stats['hata']     ?? 0, true],
        ['kaynak=order',   'info',    '🛒', 'Siteden Alınmış', $stats['order']   ?? 0, true],
        ['kaynak=metunic', 'info',    '🌐', 'Metunic var — bizde yok', $stats['metunic'] ?? 0, true],
    ], fn ($k) => $k[5]);
@endphp

@if($durumKartlari)
<div class="mini-stat-grid" style="margin-bottom:20px">
    @foreach($durumKartlari as [$sorgu, $sinif, $ikon, $etiket, $deger, $gorunsun])
        @php parse_str($sorgu, $p); $anahtar = array_key_first($p); @endphp
        <a href="{{ $domUrl }}?{{ $sorgu }}" class="mini-stat {{ $sinif }}" title="{{ $etiket }} kayıtları göster"
           style="padding:10px 14px;{{ request($anahtar) == $p[$anahtar] ? 'outline:2px solid var(--brand)' : '' }}">
            <div class="mini-stat-icon" style="font-size:18px">{{ $ikon }}</div>
            <div class="mini-stat-body">
                <div class="mini-stat-label">{{ $etiket }}</div>
                <div class="mini-stat-value" style="font-size:18px">{{ $deger }}</div>
            </div>
        </a>
    @endforeach
</div>
@endif

{{-- ═══ DOMAIN / HOSTING KAZANCI — AYLIK ═══
     Controller aylikKazanc() ile hesaplıyordu ama ekranda hiç gösterilmiyordu.
     Kaynak: ödenmiş faturalardan başlığı/hizmeti domain veya hosting geçenler. --}}
@if(!empty($kazanc))
@php $tl = fn ($x) => '₺' . number_format((float) $x, 2, ',', '.'); @endphp
<div class="table-wrap" style="margin-bottom:20px">
    <div class="card-header" style="padding:14px 18px;margin-bottom:0">
        <div class="card-title">Domain / Hosting Kazancı</div>
        <span class="badge badge-neutral" style="font-size:10px">ödenmiş faturalardan</span>
    </div>
    <div style="padding:16px 18px">
        <div class="mini-stat-grid" style="margin-bottom:0">
            <div class="mini-stat success" style="padding:10px 14px">
                <div class="mini-stat-body">
                    <div class="mini-stat-label">Bu Ay</div>
                    <div class="mini-stat-value" style="font-size:18px;color:var(--success)">{{ $tl($kazanc['bu_ay'] ?? 0) }}</div>
                </div>
            </div>
            <div class="mini-stat" style="padding:10px 14px">
                <div class="mini-stat-body">
                    <div class="mini-stat-label">Geçen Ay</div>
                    <div class="mini-stat-value" style="font-size:18px">{{ $tl($kazanc['gecen_ay'] ?? 0) }}</div>
                </div>
            </div>
            <div class="mini-stat brand" style="padding:10px 14px">
                <div class="mini-stat-body">
                    <div class="mini-stat-label">Bu Yıl</div>
                    <div class="mini-stat-value" style="font-size:18px">{{ $tl($kazanc['yil'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        @if(!empty($kazanc['aylar']))
            {{-- Son 6 ay — en yüksek aya göre orantılı çubuk --}}
            @php $enYuksek = max(1, max(array_column($kazanc['aylar'], 'tutar'))); @endphp
            <div style="display:flex;gap:10px;align-items:flex-end;height:90px;margin-top:16px">
                @foreach($kazanc['aylar'] as $ay)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px" title="{{ $ay['etiket'] }}: {{ $tl($ay['tutar']) }}">
                        <div style="font-size:10px;color:var(--text-muted);white-space:nowrap">{{ $ay['tutar'] > 0 ? $tl($ay['tutar']) : '' }}</div>
                        <div style="width:100%;background:var(--brand);border-radius:4px 4px 0 0;min-height:2px;
                                    height:{{ max(2, round(($ay['tutar'] / $enYuksek) * 55)) }}px"></div>
                        <div style="font-size:10.5px;color:var(--text-secondary);white-space:nowrap">{{ $ay['etiket'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif

{{-- TÜR KUTUCUKLARI + FİLTRE (madde 7): toplam / domain / hosting / mail hosting / business hosting --}}
@php $aktifTur = request('tur'); @endphp
<div class="mini-stat-grid" style="margin-bottom:20px">
    {{-- "Toplam" = Business Hosting + Mail Hosting (04.08.2026).
         Eskiden tüm kayıtları sayıyordu, üstteki "Toplam Domain" ile aynı
         rakamı iki kez gösteriyordu. Tıklayınca da aynı iki türü listeler. --}}
    <a href="{{ $domUrl }}?tur=toplam" class="mini-stat" title="Business + Mail Hosting"
       style="padding:10px 14px;{{ $aktifTur === 'toplam' ? 'outline:2px solid var(--brand)' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">📦</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Toplam</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['toplam_bh_mh'] ?? 0 }}</div>
        </div>
    </a>
    <a href="{{ $domUrl }}?tur=domain" class="mini-stat info" title="Sadece domainler"
       style="padding:10px 14px;{{ $aktifTur === 'domain' ? 'outline:2px solid #3b82f6' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">🌐</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Domain</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['domain_adet'] ?? 0 }}</div>
        </div>
    </a>
    <a href="{{ $domUrl }}?tur=hosting" class="mini-stat" title="Tüm hosting (business + mail + diğer)"
       style="padding:10px 14px;{{ $aktifTur === 'hosting' ? 'outline:2px solid #8b5cf6' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">🖥️</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Hosting</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['hosting_tum'] ?? 0 }}</div>
        </div>
    </a>
    <a href="{{ $domUrl }}?tur=business_hosting" class="mini-stat" title="Sadece Business Hosting"
       style="padding:10px 14px;{{ $aktifTur === 'business_hosting' ? 'outline:2px solid #0ea5e9' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">💼</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Business Hosting</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['business_hosting'] ?? 0 }}</div>
        </div>
    </a>
    <a href="{{ $domUrl }}?tur=mail_hosting" class="mini-stat" title="Sadece Mail Hosting"
       style="padding:10px 14px;{{ $aktifTur === 'mail_hosting' ? 'outline:2px solid #f59e0b' : '' }}">
        <div class="mini-stat-icon" style="font-size:18px">✉️</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Mail Hosting</div>
            <div class="mini-stat-value" style="font-size:18px">{{ $stats['mail_hosting'] ?? 0 }}</div>
        </div>
    </a>
</div>

{{-- FİLTRELER --}}
<form method="GET" action="{{ route('admin.crm.domains.index') }}" class="section" style="padding:16px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;align-items:end">
        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Arama</label>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Domain, e-posta, firma, sipariş ID..." class="form-input" style="height:36px;font-size:13px">
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Durum</label>
            <select name="status" class="form-select" style="height:36px;font-size:13px">
                <option value="">Tümü</option>
                <option value="active" {{ ($status ?? '') == 'active' ? 'selected' : '' }}>🟢 Aktif (online)</option>
                <option value="kayitli" {{ ($status ?? '') == 'kayitli' ? 'selected' : '' }}>🟢 Kayıtlı</option>
                <option value="1" {{ ($status ?? '') == '1' ? 'selected' : '' }}>🟢 Aktif (manuel)</option>
                <option value="pending" {{ ($status ?? '') == 'pending' ? 'selected' : '' }}>⏳ Beklemede</option>
                <option value="paid" {{ ($status ?? '') == 'paid' ? 'selected' : '' }}>💳 Ödendi</option>
                <option value="failed" {{ ($status ?? '') == 'failed' ? 'selected' : '' }}>❌ Başarısız</option>
                <option value="expired" {{ ($status ?? '') == 'expired' ? 'selected' : '' }}>⏰ Süresi Doldu</option>
                <option value="0" {{ ($status ?? '') == '0' ? 'selected' : '' }}>🔴 Pasif (manuel)</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:11px">Kaynak</label>
            <select name="kaynak" class="form-select" style="height:36px;font-size:13px">
                <option value="" {{ (($kompozit ?? null) === null && ($kaynak ?? '') === '') ? 'selected' : '' }}>Tümü ({{ $stats['toplam'] ?? 0 }})</option>
                <option value="order" {{ (($kompozit ?? null) === null && ($kaynak ?? '') == 'order') ? 'selected' : '' }}>🛒 Siteden Alınmış ({{ $stats['order'] ?? 0 }})</option>
                <option value="manuel" {{ (($kompozit ?? null) === null && ($kaynak ?? '') == 'manuel') ? 'selected' : '' }}>✋ Manuel — elle eklenen ({{ $stats['manuel'] ?? 0 }})</option>
                <option value="metunic" {{ (($kompozit ?? null) === null && ($kaynak ?? '') == 'metunic') ? 'selected' : '' }}>🌐 Yalnızca Metunic'te — bizde kaydı yok ({{ $stats['metunic'] ?? 0 }})</option>

                {{-- Kaydın kaynağına değil, domainin iki tablodaki
                     durumuna bakan süzgeçler. --}}
                <option value="ikisinde" {{ ($kompozit ?? null) === 'ikisinde' ? 'selected' : '' }}>🔁 İkisinde de var ({{ $stats['ikisinde'] ?? 0 }})</option>
                <option value="metunicsiz" {{ ($kompozit ?? null) === 'metunicsiz' ? 'selected' : '' }}>📄 Metunic'te olmayanlar ({{ $stats['metunicsiz'] ?? 0 }})</option>
            </select>
        </div>

        <div style="display:flex;gap:6px;align-items:end">
            <button type="submit" class="btn btn-primary btn-sm" style="height:36px">
                <i data-lucide="filter"></i>
                <span>Filtrele</span>
            </button>
            @if(request()->hasAny(['search','status','kaynak']))
                <a href="{{ route('admin.crm.domains.index') }}" class="btn btn-ghost btn-sm" style="height:36px" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- TABLO --}}
@if($domains->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="globe" class="empty-state-icon"></i>
            <h4>Domain bulunamadı</h4>
            <p>
                @if(request()->hasAny(['search','status','kaynak']))
                    Filtreyle eşleşen domain yok. <a href="{{ route('admin.crm.domains.index') }}" style="color:var(--brand)">Filtreyi temizle</a>
                @else
                    Henüz domain kaydı yok. Manuel ekle veya online sipariş bekle.
                @endif
            </p>
            <a href="{{ route('admin.crm.domains.create') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i>
                <span>Manuel Domain Ekle</span>
            </a>
        </div>
    </div>
@else
    @php
        $statusMap = [
            'active'   => ['label' => 'Aktif',     'class' => 'badge-success', 'icon' => '🟢'],
            'kayitli'  => ['label' => 'Kayıtlı',   'class' => 'badge-success', 'icon' => '🟢'],
            '1'        => ['label' => 'Aktif',     'class' => 'badge-success', 'icon' => '🟢'],
            1          => ['label' => 'Aktif',     'class' => 'badge-success', 'icon' => '🟢'],
            'pending'  => ['label' => 'Beklemede', 'class' => 'badge-warning', 'icon' => '⏳'],
            'paid'     => ['label' => 'Ödendi',    'class' => 'badge-warning', 'icon' => '💳'],
            'bekliyor' => ['label' => 'Bekliyor',  'class' => 'badge-warning', 'icon' => '⏳'],
            'failed'   => ['label' => 'Başarısız', 'class' => 'badge-danger',  'icon' => '❌'],
            'expired'  => ['label' => 'Süresi Doldu', 'class' => 'badge-danger', 'icon' => '⏰'],
            'hata'     => ['label' => 'Hata',      'class' => 'badge-danger',  'icon' => '❌'],
            '0'        => ['label' => 'Pasif',     'class' => 'badge-danger',  'icon' => '🔴'],
            0          => ['label' => 'Pasif',     'class' => 'badge-danger',  'icon' => '🔴'],
        ];
    @endphp

    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">Kaynak</th>
                        <th>Domain</th>
                        <th style="width:150px">Hizmet</th>
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
                            $isOrder = ($d->kaynak ?? 'manuel') === 'order';
                            $isMetunic = ($d->kaynak ?? '') === 'metunic';
                            $musteriAdi = $d->uye_firma ?: trim(($d->uye_ad ?? '').' '.($d->uye_soyad ?? '')) ?: ($d->uye_email ?? '—');
                            $isUrgent = $kalanGun !== null && $kalanGun <= 7 && $kalanGun >= 0;
                            $isExpired = $kalanGun !== null && $kalanGun < 0;
                        @endphp
                        <tr @if($isExpired)style="background:rgba(239,68,68,0.04)"@elseif($isUrgent)style="background:rgba(245,158,11,0.04)"@endif>
                            <td style="text-align:center">
                                @if($isOrder)
                                    <span title="Online sipariş" style="font-size:18px">🛒</span>
                                @elseif(($d->kaynak ?? '') === 'hosting')
                                    <span title="Hosting" style="font-size:18px">🖥️</span>
                                @elseif(($d->kaynak ?? '') === 'metunic')
                                    <span title="Metunic" style="font-size:18px">🌐</span>
                                @else
                                    <span title="Manuel eklendi" style="font-size:18px">✋</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.crm.domains.show', ['kaynak' => $d->kaynak, 'id' => $d->id]) }}" style="font-weight:700;color:var(--text);text-decoration:none;font-size:13.5px">
                                    🌐 {{ $d->domain ?? '—' }}
                                </a>
                                @if(!empty($d->yil) && $d->yil > 1)
                                    <div style="font-size:10.5px;color:var(--text-muted);margin-top:1px">{{ $d->yil }} yıllık</div>
                                @endif
                            </td>

                            {{-- HİZMET
                                 Bir kayıt aynı anda birden çok hizmet olabiliyor
                                 (hem domain hem hosting satılmış olabilir), bu yüzden
                                 tek etiket yerine geçerli olanların HEPSİ basılıyor.
                                 Controller bu bayrakları zaten hesaplıyor. --}}
                            <td>
                                @php
                                    $hizmetler = [];
                                    if ($d->hizDomain   ?? false) $hizmetler[] = ['Domain', 'badge-brand'];
                                    if ($d->hizHosting  ?? false) $hizmetler[] = ['Hosting', 'badge-info'];
                                    if ($d->hizBusiness ?? false) $hizmetler[] = ['Business Hosting', 'badge-success'];
                                    if ($d->hizMail     ?? false) $hizmetler[] = ['Mail Hosting', 'badge-warning'];
                                @endphp
                                @forelse($hizmetler as [$ad, $sinif])
                                    <span class="badge {{ $sinif }}" style="font-size:10.5px;margin:1px 2px 1px 0;display:inline-block">{{ $ad }}</span>
                                @empty
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endforelse
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
                                    <a href="{{ route('admin.crm.domains.show', ['kaynak' => $d->kaynak, 'id' => $d->id]) }}" class="table-action" title="Detay">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <a href="https://{{ $d->domain }}" target="_blank" class="table-action" title="Domain'i aç">
                                        <i data-lucide="external-link"></i>
                                    </a>
                                    {{-- ÖDEME BİLDİRİMİ (04.08.2026)
                                         Yenileme bedelini müşteriye mail + SMS ile gönderir.
                                         Metot ve rota vardı ama ekranda butonu yoktu. --}}
                                    @if(Route::has('admin.crm.domains.odeme-bildirimi'))
                                        <form action="{{ route('admin.crm.domains.odeme-bildirimi', ['kaynak' => $d->kaynak, 'id' => $d->id]) }}"
                                              method="POST" style="display:inline;margin:0"
                                              onsubmit="return confirm('{{ $d->domain }}\n\nMüşteriye ödeme bildirimi gönderilsin mi?');">
                                            @csrf
                                            <button type="submit" class="table-action" title="Ödeme bildirimi gönder">
                                                <i data-lucide="send"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(!$isOrder && !$isMetunic && Route::has('admin.crm.domains.edit'))
                                        <a href="{{ route('admin.crm.domains.edit', ['kaynak' => $d->kaynak, 'id' => $d->id]) }}" class="table-action" title="Düzenle">
                                            <i data-lucide="edit-2"></i>
                                        </a>
                                    @endif
                                    {{-- METUNİC KAYDINA TUTAR
                                         Metunic senkronu fiyat getirmiyor. Bu kayitlarin
                                         tutari elle girilmeden kâr raporuna giremiyor;
                                         ikinci tablo kaldirilinca giris buraya tasindi. --}}
                                    @if($isMetunic && Route::has('admin.crm.domains.metunic-tutar'))
                                        <button type="button" class="table-action" title="Tutar / maliyet gir"
                                                onclick="document.getElementById('mt-{{ $d->id }}').toggleAttribute('hidden')">
                                            <i data-lucide="wallet"></i>
                                        </button>
                                    @endif
                                    @if(!$isMetunic && Route::has('admin.crm.domains.destroy'))
                                        <form action="{{ route('admin.crm.domains.destroy', ['kaynak' => $d->kaynak, 'id' => $d->id]) }}" method="POST" style="display:inline;margin:0" onsubmit="return confirm('{{ $isOrder ? 'Bu bir ONLINE SİPARİŞ kaydı — silmek istediğine emin misin?' : 'Bu domain kaydını silmek istediğine emin misin?' }}');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- TUTAR GİRİŞ SATIRI — cüzdan butonuna basınca açılır.
                             Varsayılan gizli; hidden özniteliği butonla çevriliyor.
                             colspan tablo sütun sayısı kadar (8). --}}
                        @if($isMetunic && Route::has('admin.crm.domains.metunic-tutar'))
                            <tr id="mt-{{ $d->id }}" hidden>
                                <td colspan="8" style="background:var(--bg-subtle);padding:12px 16px">
                                    <form method="POST" action="{{ route('admin.crm.domains.metunic-tutar', $d->id) }}"
                                          style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                        @csrf
                                        <span style="font-size:12.5px;color:var(--text-muted);min-width:150px">
                                            {{ $d->domain }} — Metunic kaydı
                                        </span>
                                        <input type="number" step="0.01" min="0" name="tutar" class="form-input"
                                               style="width:110px" placeholder="Tutar"
                                               value="{{ $d->tutar !== null ? rtrim(rtrim(number_format((float) $d->tutar, 2, '.', ''), '0'), '.') : '' }}">
                                        <input type="number" step="0.01" min="0" name="maliyet" class="form-input"
                                               style="width:110px" placeholder="Maliyet"
                                               value="{{ $d->maliyet !== null ? rtrim(rtrim(number_format((float) $d->maliyet, 2, '.', ''), '0'), '.') : '' }}">
                                        <input type="date" name="satis_tarihi" class="form-input" style="width:155px"
                                               value="{{ $d->satis_tarihi ? substr((string) $d->satis_tarihi, 0, 10) : '' }}">
                                        <button type="submit" class="btn btn-primary btn-sm">Kaydet</button>
                                        <span style="font-size:11.5px;color:var(--text-muted)">
                                            Tutar girilmeden kâr raporuna girmez.
                                        </span>
                                    </form>
                                </td>
                            </tr>
                        @endif
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