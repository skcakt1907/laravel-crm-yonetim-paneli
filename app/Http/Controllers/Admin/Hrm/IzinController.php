<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Support\HrmYetki;
use App\Services\NotificationMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * HRM — İzin talepleri.
 * Çalışan talep açar; Patron + Muhasebe (rol 1,5) onaylar/reddeder.
 * Yıllık izin bakiyesi: personel_ozluk.yillik_izin_hakki - onaylanan yıllık izin günleri.
 */
class IzinController extends Controller
{
    /**
     * İzin talepleri listesi.
     * Onay yetkisi olan: tüm talepleri görür. Diğerleri: sadece kendi talebini.
     */
    public function index(Request $request)
    {
        $onayYetkisi = HrmYetki::izinOnaylayabilir();
        $aktifId = session('admin_id');
        $durum = $request->get('durum');

        $talepler = collect();
        if (Schema::hasTable('izin_talepleri')) {
            $query = DB::table('izin_talepleri as it')
                ->leftJoin('yoneticiler as y', 'y.id', '=', 'it.yonetici_id')
                ->leftJoin('yoneticiler as o', 'o.id', '=', 'it.onaylayan_id')
                ->select('it.*', 'y.adi as personel_adi', 'o.adi as onaylayan_adi');

            // Onay yetkisi yoksa sadece kendi talepleri
            if (!$onayYetkisi) {
                $query->where('it.yonetici_id', $aktifId);
            }

            if (in_array($durum, ['bekliyor', 'onaylandi', 'reddedildi'])) {
                $query->where('it.durum', $durum);
            }

            $talepler = $query->orderByDesc('it.id')->paginate(25)->withQueryString();
        }

        // Bekleyen sayısı (onay yetkisi olanlar için rozet)
        $bekleyenSayi = 0;
        if ($onayYetkisi && Schema::hasTable('izin_talepleri')) {
            $bekleyenSayi = DB::table('izin_talepleri')->where('durum', 'bekliyor')->count();
        }

        return view('admin.hrm.izin.index', compact('talepler', 'onayYetkisi', 'durum', 'bekleyenSayi'));
    }

    /**
     * Yeni izin talebi formu (çalışanın kendi talebi).
     */
    public function olustur()
    {
        $aktifId = session('admin_id');
        $bakiye = $this->kendiBakiye($aktifId);
        return view('admin.hrm.izin.olustur', compact('bakiye'));
    }

    /**
     * İzin talebini kaydet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'izin_tipi' => 'required|in:yillik,hastalik,mazeret,ucretsiz',
            'baslangic' => 'required|date',
            'bitis' => 'required|date|after_or_equal:baslangic',
            'aciklama' => 'nullable|string',
        ], [
            'izin_tipi.required' => 'İzin tipi seçiniz.',
            'baslangic.required' => 'Başlangıç tarihi zorunludur.',
            'bitis.required' => 'Bitiş tarihi zorunludur.',
            'bitis.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ]);

        if (!Schema::hasTable('izin_talepleri')) {
            return back()->withInput()->with('error', 'İzin tablosu bulunamadı. Lütfen SQL kurulumunu çalıştırın.');
        }

        $aktifId = session('admin_id');
        if (!$aktifId) {
            return back()->withInput()->with('error', 'Oturum bulunamadı.');
        }

        // Gün sayısı (dahil)
        $bas = Carbon::parse($validated['baslangic']);
        $bit = Carbon::parse($validated['bitis']);
        $gun = $bas->diffInDays($bit) + 1;

        // Yıllık izinde bakiye kontrolü
        if ($validated['izin_tipi'] === 'yillik') {
            $bakiye = $this->kendiBakiye($aktifId);
            if ($gun > $bakiye['kalan']) {
                return back()->withInput()->with('error', "Yetersiz yıllık izin bakiyesi. Kalan: {$bakiye['kalan']} gün, talep: {$gun} gün.");
            }
        }

        DB::table('izin_talepleri')->insert([
            'yonetici_id' => $aktifId,
            'izin_tipi' => $validated['izin_tipi'],
            'baslangic' => $validated['baslangic'],
            'bitis' => $validated['bitis'],
            'gun_sayisi' => $gun,
            'aciklama' => $validated['aciklama'] ?? null,
            'durum' => 'bekliyor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Onaylayıcılara bildirim (admin_bildirimler) + mail
        $this->onaylayicilaraBildir($aktifId, $validated['izin_tipi'], $gun, $validated['baslangic'], $validated['bitis'], $validated['aciklama'] ?? null);

        return redirect()->route('admin.hrm.izin.index')->with('success', 'İzin talebiniz oluşturuldu. Onay bekleniyor.');
    }

    /**
     * İzin onayla (Patron + Muhasebe).
     */
    public function onayla($id)
    {
        if (!HrmYetki::izinOnaylayabilir()) {
            return back()->with('error', 'Bu işlem için yetkiniz yok.');
        }

        $talep = DB::table('izin_talepleri')->where('id', $id)->first();
        if (!$talep || $talep->durum !== 'bekliyor') {
            return back()->with('error', 'Talep bulunamadı veya zaten işlenmiş.');
        }

        DB::table('izin_talepleri')->where('id', $id)->update([
            'durum' => 'onaylandi',
            'onaylayan_id' => session('admin_id'),
            'onay_tarihi' => now(),
            'updated_at' => now(),
        ]);

        $this->personeleBildir($talep, 'onaylandi');

        return back()->with('success', 'İzin talebi onaylandı.');
    }

    /**
     * İzin reddet (Patron + Muhasebe).
     */
    public function reddet(Request $request, $id)
    {
        if (!HrmYetki::izinOnaylayabilir()) {
            return back()->with('error', 'Bu işlem için yetkiniz yok.');
        }

        $request->validate(['red_nedeni' => 'nullable|string|max:255']);

        $talep = DB::table('izin_talepleri')->where('id', $id)->first();
        if (!$talep || $talep->durum !== 'bekliyor') {
            return back()->with('error', 'Talep bulunamadı veya zaten işlenmiş.');
        }

        DB::table('izin_talepleri')->where('id', $id)->update([
            'durum' => 'reddedildi',
            'onaylayan_id' => session('admin_id'),
            'onay_tarihi' => now(),
            'red_nedeni' => $request->get('red_nedeni'),
            'updated_at' => now(),
        ]);

        $this->personeleBildir($talep, 'reddedildi');

        return back()->with('success', 'İzin talebi reddedildi.');
    }

    /**
     * Talep sil (sadece kendi bekleyen talebini veya onay yetkilisi).
     */
    public function sil($id)
    {
        $talep = DB::table('izin_talepleri')->where('id', $id)->first();
        if (!$talep) {
            return back()->with('error', 'Talep bulunamadı.');
        }

        $aktifId = session('admin_id');
        $kendi = ((int) $talep->yonetici_id === (int) $aktifId);
        if (!$kendi && !HrmYetki::izinOnaylayabilir()) {
            return back()->with('error', 'Bu işlem için yetkiniz yok.');
        }

        DB::table('izin_talepleri')->where('id', $id)->delete();
        return back()->with('success', 'İzin talebi silindi.');
    }

    /**
     * Aktif kullanıcının yıllık izin bakiyesi.
     */
    private function kendiBakiye($yoneticiId): array
    {
        $hak = 14;
        if (Schema::hasTable('personel_ozluk')) {
            $deger = DB::table('personel_ozluk')->where('yonetici_id', $yoneticiId)->value('yillik_izin_hakki');
            if ($deger !== null) {
                $hak = (int) $deger;
            }
        }

        $kullanilan = 0;
        if (Schema::hasTable('izin_talepleri')) {
            $kullanilan = (int) DB::table('izin_talepleri')
                ->where('yonetici_id', $yoneticiId)
                ->where('izin_tipi', 'yillik')
                ->where('durum', 'onaylandi')
                ->sum('gun_sayisi');
        }

        return ['hak' => $hak, 'kullanilan' => $kullanilan, 'kalan' => max(0, $hak - $kullanilan)];
    }

    /**
     * Yeni talepte onaylayıcılara (Patron+Muhasebe) bildirim + mail.
     */
    private function onaylayicilaraBildir($talepEdenId, $izinTipi, $gun, $baslangic = null, $bitis = null, $aciklama = null): void
    {
        if (!Schema::hasTable('admin_bildirimler') && !Schema::hasTable('yoneticiler')) {
            return;
        }
        try {
            $talepEden = DB::table('yoneticiler')->where('id', $talepEdenId)->value('adi') ?? 'Personel';
            $onaylayicilar = DB::table('yoneticiler')
                ->whereIn('rol', HrmYetki::ONAY_ROLLERI)
                ->where('durum', 1)
                ->get();

            $hasYoneticiId = Schema::hasTable('admin_bildirimler') && Schema::hasColumn('admin_bildirimler', 'yonetici_id');
            $tipMetni = ['yillik' => 'Yıllık', 'hastalik' => 'Hastalık', 'mazeret' => 'Mazeret', 'ucretsiz' => 'Ücretsiz'][$izinTipi] ?? $izinTipi;

            // Mail için tarih metni
            $tarihMetni = '';
            if ($baslangic && $bitis) {
                try {
                    $tarihMetni = Carbon::parse($baslangic)->format('d.m.Y') . ' — ' . Carbon::parse($bitis)->format('d.m.Y');
                } catch (\Throwable $e) {
                    $tarihMetni = $baslangic . ' — ' . $bitis;
                }
            }

            foreach ($onaylayicilar as $o) {
                // 1) Uygulama-içi bildirim
                if (Schema::hasTable('admin_bildirimler')) {
                    $b = [
                        'tip' => 'izin_talebi',
                        'baslik' => 'Yeni İzin Talebi',
                        'mesaj' => "{$talepEden} — {$tipMetni} izin ({$gun} gün) onay bekliyor.",
                        'ilgili_tablo' => 'izin_talepleri',
                        'okundu' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if ($hasYoneticiId) {
                        $b['yonetici_id'] = $o->id;
                    }
                    DB::table('admin_bildirimler')->insert($b);
                }

                // 2) E-posta (onaylayıcıya)
                $email = $o->email ?? $o->eposta ?? null;
                if ($email && class_exists(\App\Services\NotificationMailer::class)) {
                    $body = "<p>Merhaba <strong>" . e($o->adi ?? '') . "</strong>,</p>"
                        . "<p><strong>" . e($talepEden) . "</strong> yeni bir izin talebi oluşturdu. Onayınızı bekliyor:</p>"
                        . "<div style='background:#f8fafc;border-left:4px solid #b8b62e;padding:14px;border-radius:8px;margin:14px 0'>"
                        . "<strong>Personel:</strong> " . e($talepEden) . "<br>"
                        . "<strong>İzin Tipi:</strong> {$tipMetni}<br>"
                        . ($tarihMetni ? "<strong>Tarih:</strong> {$tarihMetni} ({$gun} gün)<br>" : "<strong>Gün:</strong> {$gun} gün<br>")
                        . (!empty($aciklama) ? "<strong>Açıklama:</strong><br>" . nl2br(e($aciklama)) : "<em>Açıklama girilmemiş.</em>")
                        . "</div>"
                        . "<p>Talebi görüntülemek ve onaylamak için İş Ortağım panelindeki <strong>İzin Talepleri</strong> sayfasına girebilirsiniz.</p>";
                    NotificationMailer::send($email, 'Yeni İzin Talebi — ' . $talepEden, $body, $o->adi ?? null);
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('İzin onaylayıcı bildirimi hatası', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Onay/red sonrası personele bildirim + mail.
     */
    private function personeleBildir($talep, $sonuc): void
    {
        try {
            $personel = DB::table('yoneticiler')->where('id', $talep->yonetici_id)->first();
            if (!$personel) {
                return;
            }

            $sonucMetni = $sonuc === 'onaylandi' ? 'onaylandı' : 'reddedildi';

            // Uygulama-içi bildirim
            if (Schema::hasTable('admin_bildirimler')) {
                $b = [
                    'tip' => 'izin_sonuc',
                    'baslik' => 'İzin Talebiniz ' . ucfirst($sonucMetni),
                    'mesaj' => "İzin talebiniz ({$talep->baslangic} - {$talep->bitis}) {$sonucMetni}.",
                    'ilgili_id' => $talep->id,
                    'ilgili_tablo' => 'izin_talepleri',
                    'okundu' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
                    $b['yonetici_id'] = $personel->id;
                }
                DB::table('admin_bildirimler')->insert($b);
            }

            // E-posta
            $email = $personel->email ?? $personel->eposta ?? null;
            if ($email) {
                $renk = $sonuc === 'onaylandi' ? '#10b981' : '#ef4444';
                $body = "<p>Merhaba <strong>" . ($personel->adi ?? '') . "</strong>,</p>"
                    . "<p>İzin talebiniz hakkında bir güncelleme var:</p>"
                    . "<div style='background:#f8fafc;border-left:4px solid {$renk};padding:14px;border-radius:8px;margin:14px 0'>"
                    . "<strong>Tarih:</strong> {$talep->baslangic} — {$talep->bitis} ({$talep->gun_sayisi} gün)<br>"
                    . "<strong>Sonuç:</strong> " . ucfirst($sonucMetni)
                    . (!empty($talep->red_nedeni) ? "<br><strong>Not:</strong> " . e($talep->red_nedeni) : '')
                    . "</div>";
                NotificationMailer::send($email, 'İzin Talebiniz ' . ucfirst($sonucMetni), $body, $personel->adi ?? null);
            }
        } catch (\Throwable $e) {
            \Log::warning('İzin sonuç bildirimi hatası', ['err' => $e->getMessage()]);
        }
    }
}