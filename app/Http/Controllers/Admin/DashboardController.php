<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $adminRol = session('admin_rol', 2);
        
        // Bayi ise kendi dashboard'ına yönlendir
        if ($adminRol == 3) {
            return redirect()->route('admin.bayi.dashboard');
        }
        
        // Müşteri ise müşteri paneline yönlendir
        if ($adminRol == 4) {
            return redirect()->route('hesabim');
        }
        
        // Patron ve Çalışan için normal dashboard
        return $this->patronCalisanDashboard($adminRol);
    }
    
    private function patronCalisanDashboard($adminRol)
    {
        // Pahalı statleri 5 dakika cache'le (~184k satırlı hit tablosu çok yavaş)
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return $this->buildStats();
        });

        // Son listeler — küçük, cache'sizdir (gerçek zamanlı)
        $son_uyeler = DB::table('uyeler')
            ->select('id', 'ad', 'soyad', 'email', 'tarih')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();
        $son_destekler = DB::table('destek')
            ->select('id', 'baslik', 'durum', 'tarih')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();
        $son_faturalar = DB::table('faturalar AS f')
            ->leftJoin('uyeler AS u', 'u.id', '=', 'f.uyeid')
            ->select('f.id', 'f.tutar', 'f.durum', 'f.tarih', 'f.baslik',
                     'u.ad AS musteri_ad', 'u.soyad AS musteri_soyad', 'u.email AS musteri_email')
            ->orderBy('f.id', 'desc')
            ->limit(5)
            ->get();

        // --- Kanban: kişi başına açık görevler + Ayın Elemanı ---
        [$ekipGorevleri, $ayinElemani] = $this->kanbanOzeti();

        return view('admin.dashboard', compact('stats', 'son_uyeler', 'son_destekler', 'son_faturalar', 'ekipGorevleri', 'ayinElemani'));
    }

    /**
     * Ekip görev özeti: her iç personel için açık kanban kartı sayısı,
     * Ayın Elemanı: bu ay en çok kart tamamlayan (arşivlenen) kişi.
     */
    private function kanbanOzeti(): array
    {
        if (!Schema::hasTable('kanban_cards') || !Schema::hasTable('yoneticiler')) {
            return [collect(), null];
        }

        $uyeVar = Schema::hasTable('kanban_card_members');

        // Kişi eşleşmesi: atanan_id VEYA kart üyeliği
        $kisiKosul = function ($q, int $yid) use ($uyeVar) {
            $q->where('c.atanan_id', $yid);
            if ($uyeVar) {
                $q->orWhereExists(function ($sq) use ($yid) {
                    $sq->selectRaw('1')
                       ->from('kanban_card_members as m')
                       ->whereColumn('m.card_id', 'c.id')
                       ->where('m.yonetici_id', $yid);
                });
            }
        };

        $yoneticiler = DB::table('yoneticiler')
            ->where('durum', 1)
            ->select('id', 'adi', 'kullaniciadi')
            ->orderBy('adi')
            ->get();

        $ayBasi = now()->startOfMonth();
        $ekip = collect();

        foreach ($yoneticiler as $y) {
            $acik = DB::table('kanban_cards as c')
                ->whereNull('c.archived_at')
                ->where(fn ($q) => $kisiKosul($q, (int) $y->id))
                ->count();

            $tamamAy = DB::table('kanban_cards as c')
                ->whereNotNull('c.archived_at')
                ->where('c.archived_at', '>=', $ayBasi)
                ->where(fn ($q) => $kisiKosul($q, (int) $y->id))
                ->count();

            if ($acik > 0 || $tamamAy > 0) {
                $ekip->push((object) [
                    'id'       => $y->id,
                    'adi'      => $y->adi,
                    'kadi'     => $y->kullaniciadi,
                    
                    'acik'     => $acik,
                    'tamam_ay' => $tamamAy,
                ]);
            }
        }

        $ekip = $ekip->sortByDesc('acik')->values();
        $ayinElemani = $ekip->where('tamam_ay', '>', 0)->sortByDesc('tamam_ay')->first();

        return [$ekip, $ayinElemani];
    }

    private function buildStats()
    {
        $online = 0;
        $bugun_cogul = $bugun_tekil = 0;
        $dun_cogul = $dun_tekil = 0;
        $buay_cogul = $buay_tekil = 0;
        $toplam_cogul = $toplam_tekil = 0;

        if (Schema::hasTable('hit')) {
            $now = Carbon::now('Europe/Istanbul');
            $bugunGun = (int) $now->format('d');
            $bugunAy  = (int) $now->format('m');
            $bugunYil = (int) $now->format('Y');

            $dun = $now->copy()->subDay();
            $dunGun = (int) $dun->format('d');
            $dunAy  = (int) $dun->format('m');
            $dunYil = (int) $dun->format('Y');

            $onlineSuresi = time() - 60;
            
            // Online kullanıcılar
            $online = DB::table('hit')->where('simdi', '>', $onlineSuresi)->count();
            
            // Bugün
            $bugun_cogul = DB::table('hit')
                ->where('gun', $bugunGun)
                ->where('ay', $bugunAy)
                ->where('yil', $bugunYil)
                ->sum('sayac') ?: 0;
            $bugun_tekil = DB::table('hit')
                ->where('gun', $bugunGun)
                ->where('ay', $bugunAy)
                ->where('yil', $bugunYil)
                ->count();
            
            // Dün
            $dun_cogul = DB::table('hit')
                ->where('gun', $dunGun)
                ->where('ay', $dunAy)
                ->where('yil', $dunYil)
                ->sum('sayac') ?: 0;
            $dun_tekil = DB::table('hit')
                ->where('gun', $dunGun)
                ->where('ay', $dunAy)
                ->where('yil', $dunYil)
                ->count();
            
            // Bu Ay
            $buay_cogul = DB::table('hit')
                ->where('ay', $bugunAy)
                ->where('yil', $bugunYil)
                ->sum('sayac') ?: 0;
            $buay_tekil = DB::table('hit')
                ->where('ay', $bugunAy)
                ->where('yil', $bugunYil)
                ->count();
            
            // Toplam
            $toplam_cogul = DB::table('hit')->sum('sayac') ?: 0;
            $toplam_tekil = DB::table('hit')->count();
        }
        
        // Üye İstatistikleri
        $kayitt = date('Y-m-d');
        $haftabasi = date("Y-m-d", strtotime('monday this week'));
        $haftasonu = date("Y-m-d", strtotime('sunday this week'));
        $ilkay = date("Y-m-d", strtotime('first day of this month'));
        $sonay = date("Y-m-d", strtotime('last day of this month'));
        
        $bugun_uye = DB::table('uyeler')->where('tarih', '>=', $kayitt)->count();
        $buhafta_uye = DB::table('uyeler')->whereTarihBetween('tarih', $haftabasi, $haftasonu)->count();
        $buay_uye = DB::table('uyeler')->whereTarihBetween('tarih', $ilkay, $sonay)->count();
        $toplam_uye = DB::table('uyeler')->count();
        
        // Diğer İstatistikler
        $kayitli_sayfa = DB::table('sayfalar')->where('durum', 1)->count();
        $kayitli_blog = DB::table('blog')->where('durum', 1)->count();
        $kayitli_slider = DB::table('slider')->where('durum', 1)->count();
        
        return [
            // Hit istatistikleri
            'online' => $online,
            'bugun_tekil' => $bugun_tekil,
            'bugun_cogul' => $bugun_cogul,
            'dun_tekil' => $dun_tekil,
            'dun_cogul' => $dun_cogul,
            'buay_tekil' => $buay_tekil,
            'buay_cogul' => $buay_cogul,
            'toplam_tekil' => $toplam_tekil,
            'toplam_cogul' => $toplam_cogul,
            
            // Üye istatistikleri
            'bugun_uye' => $bugun_uye,
            'buhafta_uye' => $buhafta_uye,
            'buay_uye' => $buay_uye,
            'toplam_uye' => $toplam_uye,
            'aktif_uye' => DB::table('uyeler')->where('durum', 1)->count(),
            
            // İçerik istatistikleri
            'kayitli_sayfa' => $kayitli_sayfa,
            'kayitli_blog' => $kayitli_blog,
            'kayitli_slider' => $kayitli_slider,
            
            // Paket istatistikleri
            'toplam_paket' => DB::table('yazilimlar')->where('durum', 1)->count(),
            'toplam_web_paket' => DB::table('yazilimlar')->count(),
            
            // Destek istatistikleri
            'bekleyen_destek' => DB::table('destek')->where('durum', 0)->count(),
            'toplam_destek' => DB::table('destek')->count(),
            
            // CRM Müşteri istatistikleri
            'toplam_musteri' => DB::table('crm_customers')->count(),
            'aktif_musteri' => DB::table('crm_customers')->where('durum', 'aktif')->count(),

            // Bayi istatistikleri
            'toplam_bayi' => DB::table('bayiler')->count(),
            
            // Yönetici/Rol istatistikleri
            'toplam_patron' => DB::table('yoneticiler')->where('rol', 1)->count(),
            'toplam_calisan' => DB::table('yoneticiler')->where('rol', 2)->count(),
            'toplam_bayi_rol' => DB::table('yoneticiler')->where('rol', 3)->count(),
            'toplam_musteri_rol' => DB::table('yoneticiler')->where('rol', 4)->count(),
            
            // Fatura istatistikleri
            'toplam_fatura' => DB::table('faturalar')->count(),
            'odenmemis_fatura' => DB::table('faturalar')->where('durum', 0)->count(),
            'odenen_fatura' => DB::table('faturalar')->where('durum', 1)->count(),

            // Bu ay fatura istatistikleri (tarih varchar olabilir)
            'buay_fatura_adet' => DB::table('faturalar')
                ->whereRaw("STR_TO_DATE(tarih, '%Y-%m-%d %H:%i:%s') >= ?", [now()->startOfMonth()])
                ->count(),
            'buay_fatura_onayli' => DB::table('faturalar')
                ->where('durum', 1)
                ->whereRaw("STR_TO_DATE(tarih, '%Y-%m-%d %H:%i:%s') >= ?", [now()->startOfMonth()])
                ->count(),
            'buay_fatura_tutar' => (float) DB::table('faturalar')
                ->where('durum', 1)
                ->whereRaw("STR_TO_DATE(tarih, '%Y-%m-%d %H:%i:%s') >= ?", [now()->startOfMonth()])
                ->sum(DB::raw('CAST(tutar AS DECIMAL(15,2))')),
            
            // Blog & Referans
            'toplam_blog' => DB::table('blog')->count(),
            'toplam_referans' => DB::table('referanslar')->count(),
            
            // Mesaj istatistikleri
            'okunmamis_mesaj' => DB::table('mesajlar')->where('durum', 0)->count(),
            'toplam_mesaj' => DB::table('mesajlar')->count(),
        ];
    }
}
