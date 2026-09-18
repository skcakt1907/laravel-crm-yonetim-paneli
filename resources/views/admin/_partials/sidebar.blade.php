{{-- 
═══════════════════════════════════════════════════════════
ADMIN PANEL — SIDEBAR PARTIAL v2
İzole partial: layout'tan ayrı, kendi başına bozulmaz
Tüm route'lar Route::has() ile korunmuş, eksik route 500 vermez
═══════════════════════════════════════════════════════════
--}}

@php
    // Yardımcı fonksiyon: Route varsa link, yoksa null
    if (!function_exists('safeRoute')) {
        function safeRoute($name, $params = []) {
            return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : null;
        }
    }
@endphp

<aside id="appSidebar" class="app-sidebar">

    <div class="sidebar-brand">
        <div class="logo-mark">İO</div>
        <div class="logo-text">
            <strong>İş Ortağım</strong>
            <small>Admin Panel</small>
        </div>
    </div>

    <nav class="sidebar-nav">

        {{-- ═══ ANASAYFA ═══ --}}
        @if(Route::has('admin.dashboard'))
        <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard"></i>
            <span class="label">Anasayfa</span>
        </a>
        @endif

        @if(Route::has('admin.data-center.index'))
        <a href="{{ route('admin.data-center.index') }}" class="sidebar-link {{ request()->routeIs('admin.data-center.*') ? 'active' : '' }}">
            <i data-lucide="database"></i>
            <span class="label">Data Center</span>
        </a>
        @endif

        @if(Route::has('admin.ai-asistan.index'))
        <a href="{{ route('admin.ai-asistan.index') }}" class="sidebar-link {{ request()->routeIs('admin.ai-asistan.*') ? 'active' : '' }}">
            <i data-lucide="sparkles"></i>
            <span class="label">AI Asistan</span>
        </a>
        @endif

        @if(Route::has('admin.islem-gecmisi.index'))
        <a href="{{ route('admin.islem-gecmisi.index') }}" class="sidebar-link {{ request()->routeIs('admin.islem-gecmisi.*') ? 'active' : '' }}">
            <i data-lucide="history"></i>
            <span class="label">İşlem Geçmişi</span>
        </a>
        @endif

        {{-- ═══ CRM ═══ --}}
        <div class="sidebar-section">CRM Yönetimi</div>

        <div class="sidebar-group" data-group="crm">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="crm">
                <i data-lucide="users"></i>
                <span class="label">CRM</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.crm.musteriler.index'))
                <a href="{{ route('admin.crm.musteriler.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.musteriler.index') ? 'active' : '' }}">
                    <span class="label">Müşteriler</span>
                </a>
                @endif
                @if(Route::has('admin.engellenenler.index'))
                <a href="{{ route('admin.engellenenler.index') }}" class="sidebar-link {{ request()->routeIs('admin.engellenenler.*') ? 'active' : '' }}">
                    <span class="label">Engellenenler</span>
                </a>
                @endif
                @if(Route::has('admin.crm.firsatlar.index'))
                <a href="{{ route('admin.crm.firsatlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.firsatlar.*') ? 'active' : '' }}">
                    <span class="label">Fırsatlar</span>
                </a>
                @endif
                @if(Route::has('admin.crm.gorevler.index'))
                <a href="{{ route('admin.crm.gorevler.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.gorevler.*') ? 'active' : '' }}">
                    <span class="label">Görevler</span>
                </a>
                @endif
                @if(Route::has('admin.crm.kanban.index'))
                <a href="{{ route('admin.crm.kanban.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.kanban.*') ? 'active' : '' }}">
                    <span class="label">Kanban Board</span>
                </a>
                @endif
                {{-- GÖREV #214/5: Domain & Hosting ve Sanal Tur Hosting takibi
                     MUHASEBE bölümüne taşındı (aşağıda). CRM grubundan çıkarıldı. --}}
                @if(Route::has('admin.crm.sozlesmeler.index'))
                <a href="{{ route('admin.crm.sozlesmeler.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.sozlesmeler.*') ? 'active' : '' }}">
                    <span class="label">Sözleşmeler</span>
                </a>
                @endif
                @if(Route::has('admin.crm.ozel-kayitlar.index'))
                <a href="{{ route('admin.crm.ozel-kayitlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.ozel-kayitlar.*') ? 'active' : '' }}">
                    <span class="label">Şifre Kasası</span>
                </a>
                @endif
                {{-- Sosyal medya paylaşım takibi. Plan ekranı ayrı link değil:
                     takip ekranının üstünden gidiliyor, menü şişmesin. --}}
                @if(Route::has('admin.crm.sosyal-medya-takip.index'))
                <a href="{{ route('admin.crm.sosyal-medya-takip.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.sosyal-medya-takip.*') ? 'active' : '' }}">
                    <span class="label">Sosyal Medya Takibi</span>
                </a>
                @endif
                @if(Route::has('admin.crm.musteriler.create'))
                <a href="{{ route('admin.crm.musteriler.create') }}" class="sidebar-link {{ request()->routeIs('admin.crm.musteriler.create') ? 'active' : '' }}">
                    <span class="label">Yeni Müşteri</span>
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-group" data-group="randevu">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="randevu">
                <i data-lucide="calendar-check"></i>
                <span class="label">Randevu Yönetimi</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.randevu.randevular'))
                <a href="{{ route('admin.randevu.randevular') }}" class="sidebar-link {{ request()->routeIs('admin.randevu.randevular') ? 'active' : '' }}">
                    <span class="label">Randevular</span>
                </a>
                @endif
                @if(Route::has('admin.randevu.hizmetler'))
                <a href="{{ route('admin.randevu.hizmetler') }}" class="sidebar-link {{ request()->routeIs('admin.randevu.hizmetler') ? 'active' : '' }}">
                    <span class="label">Lokasyonlar</span>
                </a>
                @endif
                @if(Route::has('admin.randevu.calisanlar'))
                <a href="{{ route('admin.randevu.calisanlar') }}" class="sidebar-link {{ request()->routeIs('admin.randevu.calisanlar') ? 'active' : '' }}">
                    <span class="label">Çalışanlar</span>
                </a>
                @endif
                @if(Route::has('admin.randevu.ayin-elemani'))
                <a href="{{ route('admin.randevu.ayin-elemani') }}" class="sidebar-link {{ request()->routeIs('admin.randevu.ayin-elemani') ? 'active' : '' }}">
                    <span class="label">Ayın Elemanı</span>
                </a>
                @endif
                @if(Route::has('admin.randevu.degerlendirmeler'))
                <a href="{{ route('admin.randevu.degerlendirmeler') }}" class="sidebar-link {{ request()->routeIs('admin.randevu.degerlendirmeler') ? 'active' : '' }}">
                    <span class="label">Değerlendirmeler</span>
                </a>
                @endif
            </div>
        </div>

        {{-- ═══ İNSAN KAYNAKLARI (HRM) ═══ --}}
        <div class="sidebar-section">İnsan Kaynakları</div>

        @if(Route::has('admin.hrm.durum.index'))
        <a href="{{ route('admin.hrm.durum.index') }}" class="sidebar-link {{ request()->routeIs('admin.hrm.durum.*') ? 'active' : '' }}">
            <i data-lucide="activity"></i>
            <span class="label">Durum Panosu</span>
        </a>

        {{-- ANLIK DURUMLAR
             Kim ne yapıyor, menüden çıkmadan görünsün. Tek sorgu ile
             çekiliyor (PersonelDurumu::herkes) — kişi başına sorgu
             atılsaydı sidebar her sayfada onlarca sorgu açardı.
             Yalnızca durum GİRMİŞ olanlar listeleniyor; boş satırlar
             menüyü uzatmasın. --}}
        @php
            $sbDurumlar = collect();
            try {
                $sbDurumlar = \App\Services\PersonelDurumu::herkes()
                    ->filter(fn ($k) => filled($k->durum_adi))
                    ->take(10);
            } catch (\Throwable $e) {
                // Tablolar henüz kurulmadıysa menü çökmesin
            }
        @endphp
        @if($sbDurumlar->isNotEmpty())
        <div class="sidebar-durumlar">
            @foreach($sbDurumlar as $sbKisi)
                <div class="sb-durum" title="{{ $sbKisi->durum_notu }}">
                    <span class="sb-em">{{ $sbKisi->durum_emoji }}</span>
                    <span class="sb-ad">{{ \Illuminate\Support\Str::of($sbKisi->adi ?: $sbKisi->kullaniciadi)->explode(' ')->first() }}</span>
                    <span class="sb-dr" style="color:{{ $sbKisi->durum_renk }}">{{ $sbKisi->durum_adi }}</span>
                </div>
            @endforeach
        </div>
        @endif
        @endif

        @if(Route::has('admin.hrm.plan.index'))
        <a href="{{ route('admin.hrm.plan.index') }}" class="sidebar-link {{ request()->routeIs('admin.hrm.plan.*') ? 'active' : '' }}">
            <i data-lucide="calendar-clock"></i>
            <span class="label">Gün Planı</span>
        </a>
        @endif

        @if(Route::has('admin.hrm.durum.gecmis'))
        <a href="{{ route('admin.hrm.durum.gecmis') }}" class="sidebar-link {{ request()->routeIs('admin.hrm.durum.gecmis') ? 'active' : '' }}">
            <i data-lucide="history"></i>
            <span class="label">Durum Geçmişi</span>
        </a>
        @endif

        @if(Route::has('admin.hrm.personel.index'))
        <a href="{{ route('admin.hrm.personel.index') }}" class="sidebar-link {{ request()->routeIs('admin.hrm.personel.*') ? 'active' : '' }}">
            <i data-lucide="users"></i>
            <span class="label">Personel</span>
        </a>
        @endif

        @if(Route::has('admin.hrm.izin.index'))
        <a href="{{ route('admin.hrm.izin.index') }}" class="sidebar-link {{ request()->routeIs('admin.hrm.izin.*') ? 'active' : '' }}">
            <i data-lucide="calendar-days"></i>
            <span class="label">İzin Talepleri</span>
        </a>
        @endif

        @if(Route::has('admin.hrm.avans.index'))
        <a href="{{ route('admin.hrm.avans.index') }}" class="sidebar-link {{ request()->routeIs('admin.hrm.avans.*') ? 'active' : '' }}">
            <i data-lucide="banknote"></i>
            <span class="label">Avans Talepleri</span>
        </a>
        @endif

        @if(Route::has('admin.duyurular.index'))
        <a href="{{ route('admin.duyurular.index') }}" class="sidebar-link {{ request()->routeIs('admin.duyurular.*') ? 'active' : '' }}">
            <i data-lucide="megaphone"></i>
            <span class="label">Duyurular</span>
        </a>
        @endif

        {{-- ═══ MUHASEBE ═══ --}}
        <div class="sidebar-section">Muhasebe</div>

        {{-- GÖREV #214/5: Domain / Hosting / Sanal Tur takibi CRM'den buraya taşındı --}}
        <div class="sidebar-group" data-group="hizmet-takip">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="hizmet-takip">
                <i data-lucide="globe"></i>
                <span class="label">Domain / Hosting Takip</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.crm.domains.index'))
                <a href="{{ route('admin.crm.domains.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.domains.*') ? 'active' : '' }}">
                    <span class="label">Domain &amp; Hosting Takip</span>
                </a>
                @endif
                @if(Route::has('admin.crm.sanaltur-hosting.index'))
                <a href="{{ route('admin.crm.sanaltur-hosting.index') }}" class="sidebar-link {{ request()->routeIs('admin.crm.sanaltur-hosting.*') ? 'active' : '' }}">
                    <span class="label">Sanal Tur Hosting Takip</span>
                </a>
                @endif
                @if(Route::has('admin.domain.fiyatlar.index'))
                <a href="{{ route('admin.domain.fiyatlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.domain.fiyatlar.*') ? 'active' : '' }}">
                    <span class="label">Domain Fiyatları</span>
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-group" data-group="faturalar">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="faturalar">
                <i data-lucide="receipt"></i>
                <span class="label">Faturalar</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.faturalar.bekleyen'))
                <a href="{{ route('admin.faturalar.bekleyen') }}" class="sidebar-link">
                    <span class="label">Bekleyen</span>
                </a>
                @endif
                @if(Route::has('admin.faturalar.onaylanan'))
                <a href="{{ route('admin.faturalar.onaylanan') }}" class="sidebar-link">
                    <span class="label">Onaylanan</span>
                </a>
                @endif
                @if(Route::has('admin.faturalar.index'))
                <a href="{{ route('admin.faturalar.index') }}" class="sidebar-link">
                    <span class="label">Tümü</span>
                </a>
                @endif
                @if(Route::has('admin.faturalar.ekle'))
                <a href="{{ route('admin.faturalar.ekle') }}" class="sidebar-link">
                    <span class="label">Yeni Fatura</span>
                </a>
                @endif
            </div>
        </div>

        @if(Route::has('admin.is-ortaklari.index'))
        <a href="{{ route('admin.is-ortaklari.index') }}" class="sidebar-link {{ request()->routeIs('admin.is-ortaklari.*') ? 'active' : '' }}">
            <i data-lucide="trophy"></i>
            <span class="label">Ayın İş Ortakları</span>
        </a>
        @endif

        @if(Route::has('admin.gelir-gider.index'))
        <a href="{{ route('admin.gelir-gider.index') }}" class="sidebar-link {{ request()->routeIs('admin.gelir-gider.*') ? 'active' : '' }}">
            <i data-lucide="scale"></i>
            <span class="label">Gelir-Gider</span>
        </a>
        @endif

        @if(Route::has('admin.borc-takip.index'))
        <a href="{{ route('admin.borc-takip.index') }}" class="sidebar-link {{ request()->routeIs('admin.borc-takip.*') ? 'active' : '' }}">
            <i data-lucide="landmark"></i>
            <span class="label">Borç Takip</span>
        </a>
        @endif

        {{-- BİLDİRİM MERKEZİ — domain/sözleşme/fatura/alacak hatırlatmaları tek ekranda --}}
        @if(Route::has('admin.bildirim-merkezi'))
        <a href="{{ route('admin.bildirim-merkezi') }}" class="sidebar-link {{ request()->routeIs('admin.bildirim-merkezi') ? 'active' : '' }}">
            <i data-lucide="bell-ring"></i>
            <span class="label">Bildirim Merkezi</span>
            @php
                $bmGecikmis = 0;
                try {
                    $bmGecikmis = \App\Services\BildirimMerkezi::ozet(
                        \App\Services\BildirimMerkezi::yaklasanlar(30)
                    )['gecikmis'] ?? 0;
                } catch (\Throwable $e) {}
            @endphp
            @if($bmGecikmis > 0)<span class="badge badge-danger" style="margin-left:auto">{{ $bmGecikmis }}</span>@endif
        </a>
        @endif

        @if(Route::has('admin.raporlar.gunluk'))
        <a href="{{ route('admin.raporlar.gunluk') }}" class="sidebar-link {{ request()->routeIs('admin.raporlar.gunluk*') ? 'active' : '' }}">
            <i data-lucide="bar-chart-3"></i>
            <span class="label">Günlük Raporlar</span>
        </a>
        @endif

        @if(Route::has('admin.raporlar.gun-sonu'))
        <a href="{{ route('admin.raporlar.gun-sonu') }}" class="sidebar-link {{ request()->routeIs('admin.raporlar.gun-sonu*') ? 'active' : '' }}">
            <i data-lucide="clipboard-list"></i>
            <span class="label">Gün Sonu Raporu</span>
        </a>
        @endif

        @if(Route::has('admin.temizlik.index'))
        <a href="{{ route('admin.temizlik.index') }}" class="sidebar-link {{ request()->routeIs('admin.temizlik.*') ? 'active' : '' }}">
            <i data-lucide="spray-can"></i>
            <span class="label">Temizlik Kontrol</span>
        </a>
        @endif

        {{--
            MÜŞTERİ BORÇ TAKİP — menüden GİZLENDİ (görev #217/7, 30.07.2026).
            Modül ve rotalar SİLİNMEDİ; ayın 28'inde gönderilen borç takip
            raporu (görev #217/9) bu verileri okumaya devam ediyor.
            Tekrar görünür yapmak için aşağıdaki bloğun yorumunu kaldırmak yeterli.

        @if(Route::has('admin.musteri-borc.index'))
        <a href="{{ route('admin.musteri-borc.index') }}" class="sidebar-link {{ request()->routeIs('admin.musteri-borc.*') ? 'active' : '' }}">
            <i data-lucide="users"></i>
            <span class="label">Müşteri Borç Takip</span>
        </a>
        @endif
        --}}

        @if(Route::has('admin.giderler.index'))
        <a href="{{ route('admin.giderler.index') }}" class="sidebar-link {{ request()->routeIs('admin.giderler.*') ? 'active' : '' }}">
            <i data-lucide="wallet"></i>
            <span class="label">Harcamalar</span>
        </a>
        @endif

        @if(Route::has('admin.aylik-odemeler.index'))
        <a href="{{ route('admin.aylik-odemeler.index') }}" class="sidebar-link {{ request()->routeIs('admin.aylik-odemeler.*') ? 'active' : '' }}">
            <i data-lucide="calendar-clock"></i>
            <span class="label">Aylık Bildirimli Ödemeler</span>
        </a>
        @endif

        @if(Route::has('admin.aylik-alacaklar.index'))
        <a href="{{ route('admin.aylik-alacaklar.index') }}" class="sidebar-link {{ request()->routeIs('admin.aylik-alacaklar.*') ? 'active' : '' }}">
            <i data-lucide="hand-coins"></i>
            <span class="label">Aylık Bildirimli Alacaklar</span>
            @php
                $alacakYaklasan = 0;
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('aylik_alacaklar')) {
                        $alacakYaklasan = \Illuminate\Support\Facades\DB::table('aylik_alacaklar')
                            ->where('durum', 'bekliyor')->where('aktif', 1)
                            ->whereNotNull('son_tahsil_tarihi')
                            ->whereDate('son_tahsil_tarihi', '<=', now()->addDays(7))
                            ->count();
                    }
                } catch (\Throwable $e) {}
            @endphp
            @if($alacakYaklasan > 0)<span class="badge badge-brand" style="margin-left:auto">{{ $alacakYaklasan }}</span>@endif
        </a>
        @endif

        @if(Route::has('admin.dnbank-krediler.index'))
        <a href="{{ route('admin.dnbank-krediler.index') }}" class="sidebar-link {{ request()->routeIs('admin.dnbank-krediler.*') ? 'active' : '' }}">
            <i data-lucide="landmark"></i>
            <span class="label">DN Bank Kredi Talepleri</span>
            @php
                $krediBekleyen = 0;
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('musteri_krediler')) {
                        $krediBekleyen = \Illuminate\Support\Facades\DB::table('musteri_krediler')
                            ->where('onay_durumu', 'bekliyor')->count();
                    }
                } catch (\Throwable $e) {}
            @endphp
            @if($krediBekleyen > 0)<span class="badge badge-brand" style="margin-left:auto">{{ $krediBekleyen }}</span>@endif
        </a>
        @endif

        @if(Route::has('admin.banka.index'))
        <a href="{{ route('admin.banka.index') }}" class="sidebar-link {{ request()->routeIs('admin.banka.*') ? 'active' : '' }}">
            <i data-lucide="landmark"></i>
            <span class="label">Banka Hesapları</span>
        </a>
        @endif

        @if(Route::has('admin.odeme-bildirim.index'))
        <a href="{{ route('admin.odeme-bildirim.index') }}" class="sidebar-link {{ request()->routeIs('admin.odeme-bildirim.*') ? 'active' : '' }}">
            <i data-lucide="banknote"></i>
            <span class="label">Ödeme Bildirimleri</span>
        </a>
        @endif

        @if(Route::has('admin.kuponlar.index'))
        <a href="{{ route('admin.kuponlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.kuponlar.*') ? 'active' : '' }}">
            <i data-lucide="ticket"></i>
            <span class="label">Kuponlar</span>
        </a>
        @endif

        {{-- ═══ İLETİŞİM ═══ --}}
        <div class="sidebar-section">İletişim</div>

        <div class="sidebar-group" data-group="destek">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="destek">
                <i data-lucide="life-buoy"></i>
                <span class="label">Destek Merkezi</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.destek.index'))
                <a href="{{ route('admin.destek.index') }}" class="sidebar-link">
                    <span class="label">Destek Talepleri</span>
                </a>
                @endif
            </div>
        </div>

        @if(Route::has('admin.mesajlar.index'))
        <a href="{{ route('admin.mesajlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.mesajlar.*') ? 'active' : '' }}">
            <i data-lucide="message-square"></i>
            <span class="label">Mesajlar</span>
        </a>
        @endif

        @if(Route::has('admin.dm.index') && !in_array((int)session('admin_rol'), [3]))
        @php
            try {
                $_dmOkunmamis = \Illuminate\Support\Facades\DB::table('admin_dm_mesajlar')
                    ->where('alici_id', session('admin_id'))->where('okundu', 0)->count();
            } catch (\Throwable $e) { $_dmOkunmamis = 0; }
        @endphp
        <a href="{{ route('admin.dm.index') }}" class="sidebar-link {{ request()->routeIs('admin.dm.*') ? 'active' : '' }}">
            <i data-lucide="messages-square"></i>
            <span class="label">Mesajlaşma (DM)</span>
            @if($_dmOkunmamis > 0)
            <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:11px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px">{{ $_dmOkunmamis }}</span>
            @endif
        </a>
        @endif

        @if(Route::has('admin.musteri-dm.index') && !in_array((int)session('admin_rol'), [3]))
        @php
            try {
                $_musteriDmOkunmamis = \Illuminate\Support\Facades\DB::table('musteri_dm_mesajlar')
                    ->where('yonetici_id', session('admin_id'))->where('gonderen', 'uye')->where('okundu', 0)->count();
            } catch (\Throwable $e) { $_musteriDmOkunmamis = 0; }
        @endphp
        <a href="{{ route('admin.musteri-dm.index') }}" class="sidebar-link {{ request()->routeIs('admin.musteri-dm.*') ? 'active' : '' }}">
            <i data-lucide="message-circle"></i>
            <span class="label">Müşteri Mesajları</span>
            @if($_musteriDmOkunmamis > 0)
            <span style="margin-left:auto;background:#ef4444;color:#fff;font-size:11px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px">{{ $_musteriDmOkunmamis }}</span>
            @endif
        </a>
        @endif

        @if(Route::has('admin.toplu-mesaj.index'))
        <a href="{{ route('admin.toplu-mesaj.index') }}" class="sidebar-link {{ request()->routeIs('admin.toplu-mesaj.*') ? 'active' : '' }}">
            <i data-lucide="send"></i>
            <span class="label">Toplu Mesaj</span>
        </a>
        @endif

        @if(Route::has('admin.iletisim.index'))
        <a href="{{ route('admin.iletisim.index') }}" class="sidebar-link {{ request()->routeIs('admin.iletisim.*') ? 'active' : '' }}">
            <i data-lucide="mail"></i>
            <span class="label">İletişim Formları</span>
        </a>
        @endif

        @if(Route::has('admin.bildirimler.index'))
        <a href="{{ route('admin.bildirimler.index') }}" class="sidebar-link {{ request()->routeIs('admin.bildirimler.*') ? 'active' : '' }}">
            <i data-lucide="bell"></i>
            <span class="label">Bildirimler</span>
        </a>
        @endif

        {{-- ═══ SATIŞ & ÜRÜN ═══ --}}
        <div class="sidebar-section">Satış</div>

        <div class="sidebar-group" data-group="bayilik">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="bayilik">
                <i data-lucide="store"></i>
                <span class="label">Bayilik</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.bayiler.index'))
                <a href="{{ route('admin.bayiler.index') }}" class="sidebar-link">
                    <span class="label">Tüm Bayiler</span>
                </a>
                @endif
                @if(Route::has('admin.bayiler.ekle'))
                <a href="{{ route('admin.bayiler.ekle') }}" class="sidebar-link">
                    <span class="label">Yeni Bayi</span>
                </a>
                @endif
                @if(Route::has('admin.bayiler.ayarlar'))
                <a href="{{ route('admin.bayiler.ayarlar') }}" class="sidebar-link">
                    <span class="label">Bayi Ayarları</span>
                </a>
                @endif
                @if(Route::has('admin.bayilik.satislar'))
                <a href="{{ route('admin.bayilik.satislar') }}" class="sidebar-link">
                    <span class="label">Bayi Satışları</span>
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-group" data-group="paketler">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="paketler">
                <i data-lucide="package"></i>
                <span class="label">Paketler</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.paketler.index'))
                <a href="{{ route('admin.paketler.index') }}" class="sidebar-link">
                    <span class="label">Tüm Paketler</span>
                </a>
                @endif
                @if(Route::has('admin.paketler.ekle'))
                <a href="{{ route('admin.paketler.ekle') }}" class="sidebar-link">
                    <span class="label">Yeni Paket</span>
                </a>
                @endif
                @if(Route::has('admin.paketler.anasayfa'))
                <a href="{{ route('admin.paketler.anasayfa') }}" class="sidebar-link">
                    <span class="label">Anasayfa Paketleri</span>
                </a>
                @endif
                @if(Route::has('admin.paketler.ozel'))
                <a href="{{ route('admin.paketler.ozel') }}" class="sidebar-link">
                    <span class="label">Müşteriye Özel Paket Teklifleri</span>
                </a>
                @endif
                @if(Route::has('admin.paketler.teklif.create'))
                <a href="{{ route('admin.paketler.teklif.create') }}" class="sidebar-link {{ request()->routeIs('admin.paketler.teklif.*') ? 'active' : '' }}">
                    <span class="label">Teklif Oluştur</span>
                </a>
                @endif
                @if(Route::has('admin.kategoriler.index'))
                <a href="{{ route('admin.kategoriler.index') }}" class="sidebar-link">
                    <span class="label">Kategoriler</span>
                </a>
                @endif
                @if(Route::has('admin.satislar.web-paket'))
                <a href="{{ route('admin.satislar.web-paket') }}" class="sidebar-link">
                    <span class="label">Paket Satışları</span>
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-group" data-group="hizmet">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="hizmet">
                <i data-lucide="briefcase"></i>
                <span class="label">Hizmetler</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.hizmetler.index'))
                <a href="{{ route('admin.hizmetler.index') }}" class="sidebar-link">
                    <span class="label">Hizmet Listesi</span>
                </a>
                @endif
                @if(Route::has('admin.hizmetler.ekle'))
                <a href="{{ route('admin.hizmetler.ekle') }}" class="sidebar-link">
                    <span class="label">Yeni Hizmet</span>
                </a>
                @endif
                @if(Route::has('admin.hizmet-fiyatlari.index'))
                <a href="{{ route('admin.hizmet-fiyatlari.index') }}" class="sidebar-link">
                    <span class="label">Hizmet Fiyatları</span>
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-group" data-group="hosting">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="hosting">
                <i data-lucide="server"></i>
                <span class="label">Hosting</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.hosting.paketler.index'))
                <a href="{{ route('admin.hosting.paketler.index') }}" class="sidebar-link">
                    <span class="label">Hosting Paketleri</span>
                </a>
                @endif
                @if(Route::has('admin.satislar.hosting'))
                <a href="{{ route('admin.satislar.hosting') }}" class="sidebar-link">
                    <span class="label">Hosting Satışları</span>
                </a>
                @endif
            </div>
        </div>

        <div class="sidebar-group" data-group="domain">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="domain">
                <i data-lucide="globe"></i>
                <span class="label">Alan Adı</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.domains.index'))
                <a href="#" class="sidebar-link">
                    <span class="label">Domainler</span>
                </a>
                @endif
                @if(Route::has('admin.domain-orders.index'))
                <a href="{{ route('admin.domain-orders.index') }}" class="sidebar-link">
                    <span class="label">Domain Siparişleri</span>
                </a>
                @endif
                @if(Route::has('admin.domain.fiyatlar.index'))
                <a href="{{ route('admin.domain.fiyatlar.index') }}" class="sidebar-link">
                    <span class="label">Domain Fiyatları</span>
                </a>
                @endif
                @if(Route::has('admin.satislar.domain'))
                <a href="{{ route('admin.satislar.domain') }}" class="sidebar-link">
                    <span class="label">Domain Satışları</span>
                </a>
                @endif
            </div>
        </div>

        @if(Route::has('admin.kampanyalar.index'))
        <a href="{{ route('admin.kampanyalar.index') }}" class="sidebar-link {{ request()->routeIs('admin.kampanyalar.*') ? 'active' : '' }}">
            <i data-lucide="megaphone"></i>
            <span class="label">Kampanyalar</span>
        </a>
        @endif

        {{-- ═══ İÇERİK / SİTE ═══ --}}
        <div class="sidebar-section">Site İçerik</div>

        <div class="sidebar-group" data-group="sayfalar">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="sayfalar">
                <i data-lucide="file-text"></i>
                <span class="label">Sayfalar</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.sayfalar.index'))
                <a href="{{ route('admin.sayfalar.index') }}" class="sidebar-link">
                    <span class="label">Tüm Sayfalar</span>
                </a>
                @endif
                @if(Route::has('admin.sayfalar.ekle'))
                <a href="{{ route('admin.sayfalar.ekle') }}" class="sidebar-link">
                    <span class="label">Yeni Sayfa</span>
                </a>
                @endif
            </div>
        </div>

        @if(Route::has('admin.slider.index'))
        <a href="{{ route('admin.slider.index') }}" class="sidebar-link {{ request()->routeIs('admin.slider.*') ? 'active' : '' }}">
            <i data-lucide="images"></i>
            <span class="label">Slider</span>
        </a>
        @endif

        @if(Route::has('admin.blog.index'))
        <a href="{{ route('admin.blog.index') }}" class="sidebar-link {{ request()->routeIs('admin.blog.*') ? 'active' : '' }}">
            <i data-lucide="rss"></i>
            <span class="label">Blog</span>
        </a>
        @endif

        @if(Route::has('admin.referanslar.index'))
        <a href="{{ route('admin.referanslar.index') }}" class="sidebar-link {{ request()->routeIs('admin.referanslar.*') ? 'active' : '' }}">
            <i data-lucide="award"></i>
            <span class="label">Referanslar</span>
        </a>
        @endif

        @if(Route::has('admin.yorumlar.index'))
        <a href="{{ route('admin.yorumlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.yorumlar.*') ? 'active' : '' }}">
            <i data-lucide="message-circle"></i>
            <span class="label">Yorumlar</span>
        </a>
        @endif

        @if(Route::has('admin.arge-anketleri.index'))
        <a href="{{ route('admin.arge-anketleri.index') }}" class="sidebar-link {{ request()->routeIs('admin.arge-anketleri.*') ? 'active' : '' }}">
            <i data-lucide="clipboard-list"></i>
            <span class="label">Ar-Ge Anketi</span>
            @php $argeOkunmamis = \Illuminate\Support\Facades\Schema::hasTable('arge_anketleri') ? \App\Models\ArgeAnketi::where('okundu', 0)->count() : 0; @endphp
            @if($argeOkunmamis > 0)<span class="badge badge-brand" style="margin-left:auto">{{ $argeOkunmamis }}</span>@endif
        </a>
        @endif

        @if(Route::has('admin.is-basvurulari.index'))
        <a href="{{ route('admin.is-basvurulari.index') }}" class="sidebar-link {{ request()->routeIs('admin.is-basvurulari.*') ? 'active' : '' }}">
            <i data-lucide="briefcase"></i>
            <span class="label">İş Başvuruları</span>
            @php $basvuruOkunmamis = \Illuminate\Support\Facades\Schema::hasTable('is_basvurulari') ? \App\Models\IsBasvuru::where('okundu', 0)->count() : 0; @endphp
            @if($basvuruOkunmamis > 0)<span class="badge badge-brand" style="margin-left:auto">{{ $basvuruOkunmamis }}</span>@endif
        </a>
        @endif

        @if(Route::has('admin.bayi-basvurulari.index'))
        <a href="{{ route('admin.bayi-basvurulari.index') }}" class="sidebar-link {{ request()->routeIs('admin.bayi-basvurulari.*') ? 'active' : '' }}">
            <i data-lucide="handshake"></i>
            <span class="label">İş Ortağı Başvuruları</span>
            @php $bayiBekleyen = \Illuminate\Support\Facades\Schema::hasTable('bayi_basvurulari') ? \App\Models\BayiBasvuru::where('durum', 'beklemede')->count() : 0; @endphp
            @if($bayiBekleyen > 0)<span class="badge badge-brand" style="margin-left:auto">{{ $bayiBekleyen }}</span>@endif
        </a>
        @endif

        <div class="sidebar-group" data-group="menuler">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="menuler">
                <i data-lucide="menu"></i>
                <span class="label">Menüler</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.menuler.header'))
                <a href="{{ route('admin.menuler.header') }}" class="sidebar-link">
                    <span class="label">Header Menü</span>
                </a>
                @endif
                @if(Route::has('admin.menuler.footer'))
                <a href="{{ route('admin.menuler.footer') }}" class="sidebar-link">
                    <span class="label">Footer Menü</span>
                </a>
                @endif
                @if(Route::has('admin.menuler.navbar-ayarlari'))
                <a href="{{ route('admin.menuler.navbar-ayarlari') }}" class="sidebar-link">
                    <span class="label">Navbar Ayarları</span>
                </a>
                @endif
            </div>
        </div>

        @if(Route::has('admin.diller.index'))
        <div class="sidebar-group" data-group="diller">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="diller">
                <i data-lucide="languages"></i>
                <span class="label">Dil Yönetimi</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                <a href="{{ route('admin.diller.index') }}" class="sidebar-link">
                    <span class="label">Dil Listesi</span>
                </a>
                @if(Route::has('admin.diller.ekle'))
                <a href="{{ route('admin.diller.ekle') }}" class="sidebar-link">
                    <span class="label">Yeni Dil</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- ═══ ARAÇLAR ═══ --}}
        <div class="sidebar-section">Araçlar</div>

        @if(Route::has('admin.takvim.index'))
        <a href="{{ route('admin.takvim.index') }}" class="sidebar-link {{ request()->routeIs('admin.takvim.*') ? 'active' : '' }}">
            <i data-lucide="calendar-days"></i>
            <span class="label">Takvim</span>
        </a>
        @endif

        @if(Route::has('admin.kanban.takvim') || Route::has('admin.kanban.istatistik'))
        <a href="{{ safeRoute('admin.kanban.takvim') ?? safeRoute('admin.kanban.istatistik') ?? '#' }}" class="sidebar-link {{ request()->routeIs('admin.kanban.*') ? 'active' : '' }}">
            <i data-lucide="trello"></i>
            <span class="label">Kanban</span>
        </a>
        @endif

        <div class="sidebar-group" data-group="tablolar">
            <button type="button" class="sidebar-link sidebar-group-toggle" data-toggle-group="tablolar">
                <i data-lucide="table-2"></i>
                <span class="label">Tablolar</span>
                <i data-lucide="chevron-right" class="chevron"></i>
            </button>
            <div class="sidebar-group-items">
                @if(Route::has('admin.tablolar.index'))
                <a href="{{ route('admin.tablolar.index') }}" class="sidebar-link">
                    <span class="label">Tüm Tablolar</span>
                </a>
                @endif
                @if(Route::has('admin.tablolar.create'))
                <a href="{{ route('admin.tablolar.create') }}" class="sidebar-link">
                    <span class="label">Yeni Tablo</span>
                </a>
                @endif
            </div>
        </div>

        @if(Route::has('admin.mail-templates.index'))
        <a href="{{ route('admin.mail-templates.index') }}" class="sidebar-link {{ request()->routeIs('admin.mail-templates.*') ? 'active' : '' }}">
            <i data-lucide="mail-plus"></i>
            <span class="label">Mail Şablonları</span>
        </a>
        @endif

        @if(Route::has('admin.ek-hizmetler.index'))
        <a href="{{ route('admin.ek-hizmetler.index') }}" class="sidebar-link {{ request()->routeIs('admin.ek-hizmetler.*') ? 'active' : '' }}">
            <i data-lucide="puzzle"></i>
            <span class="label">Ek Hizmetler</span>
        </a>
        @endif

        @if(Route::has('admin.ebulten.index'))
        <a href="{{ route('admin.ebulten.index') }}" class="sidebar-link {{ request()->routeIs('admin.ebulten.*') ? 'active' : '' }}">
            <i data-lucide="send"></i>
            <span class="label">E-Bülten</span>
        </a>
        @endif

        @if(Route::has('admin.raporlar'))
        <a href="#" class="sidebar-link {{ request()->routeIs('admin.raporlar') ? 'active' : '' }}">
            <i data-lucide="bar-chart-3"></i>
            <span class="label">Raporlar</span>
        </a>
        @endif

        @if(Route::has('admin.import.export'))
        <a href="{{ route('admin.import.export') }}" class="sidebar-link {{ request()->routeIs('admin.import.export') ? 'active' : '' }}">
            <i data-lucide="upload"></i>
            <span class="label">Import / Export</span>
        </a>
        @endif

        {{-- ═══ KULLANICILAR ═══ --}}
        <div class="sidebar-section">Kullanıcılar</div>

        {{--
            ÜYELER — menüden GİZLENDİ (31.07.2026).

            NEDEN: Personel iki ayrı müşteri listesi görüyordu (Üyeler + CRM Müşteriler)
            ve karışıklık yaratıyordu. Artık tek kapı var: CRM → Müşteriler.

            `uyeler` tablosu SİLİNMEDİ, silinemez de — müşterinin siteye giriş yaptığı
            hesap orada (şifre, oturum). Bayi girişi de aynı tabloyu kullanıyor.
            Sadece arka planda kaldı; personel CRM'den çalışıyor.

            Rotalar ve ekranlar duruyor; gerekirse /admin/uyeler adresi hâlâ açılır.
            Tekrar menüye almak için bu yorumu kaldırmak yeterli.

        @if(Route::has('admin.uyeler.index'))
        <a href="{{ route('admin.uyeler.index') }}" class="sidebar-link {{ request()->routeIs('admin.uyeler.*') ? 'active' : '' }}">
            <i data-lucide="user"></i>
            <span class="label">Üyeler</span>
        </a>
        @endif
        --}}

        @if(Route::has('admin.yoneticiler.index'))
        <a href="{{ route('admin.yoneticiler.index') }}" class="sidebar-link {{ request()->routeIs('admin.yoneticiler.*') ? 'active' : '' }}">
            <i data-lucide="user-cog"></i>
            <span class="label">Yöneticiler</span>
        </a>
        @endif

        @if(Route::has('admin.roller.index'))
        <a href="{{ route('admin.roller.index') }}" class="sidebar-link {{ request()->routeIs('admin.roller.*') ? 'active' : '' }}">
            <i data-lucide="shield"></i>
            <span class="label">Roller</span>
        </a>
        @endif

        @if(Route::has('admin.giris-loglari.index'))
        <a href="{{ route('admin.giris-loglari.index') }}" class="sidebar-link {{ request()->routeIs('admin.giris-loglari.*') ? 'active' : '' }}">
            <i data-lucide="shield-check"></i>
            <span class="label">Giriş Logları</span>
        </a>
        @endif

        {{-- ═══ SİSTEM ═══ --}}
        <div class="sidebar-section">Sistem</div>

        @if(Route::has('admin.ayarlar.index'))
        <a href="{{ route('admin.ayarlar.index') }}" class="sidebar-link {{ request()->routeIs('admin.ayarlar.*') ? 'active' : '' }}">
            <i data-lucide="settings"></i>
            <span class="label">Ayarlar</span>
        </a>
        @endif

        @if(Route::has('admin.cikis'))
        <a href="{{ route('admin.cikis') }}" class="sidebar-link" style="color:var(--danger);margin-top:8px"
           onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?');">
            <i data-lucide="log-out"></i>
            <span class="label">Çıkış Yap</span>
        </a>
        @endif

    </nav>

</aside>

<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeSidebar()"></div>
