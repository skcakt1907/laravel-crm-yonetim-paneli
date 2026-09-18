<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Support\HrmYetki;
use App\Services\NotificationMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HRM — Avans talepleri.
 * Calisan talep acar; Patron + Muhasebe onaylar/reddeder (izinle ayni yetki).
 */
class AvansController extends Controller
{
    /**
     * Avans talepleri listesi.
     * Onay yetkisi olan: tum talepleri gorur. Digerleri: sadece kendi talebini.
     */
    public function index(Request $request)
    {
        $onayYetkisi = HrmYetki::izinOnaylayabilir();
        $aktifId = session('admin_id');
        $durum = $request->get('durum');

        $talepler = collect();
        if (Schema::hasTable('avans_talepleri')) {
            $query = DB::table('avans_talepleri as at')
                ->leftJoin('yoneticiler as y', 'y.id', '=', 'at.yonetici_id')
                ->leftJoin('yoneticiler as o', 'o.id', '=', 'at.onaylayan_id')
                ->select('at.*', 'y.adi as personel_adi', 'o.adi as onaylayan_adi');

            if (!$onayYetkisi) {
                $query->where('at.yonetici_id', $aktifId);
            }

            if (in_array($durum, ['bekliyor', 'onaylandi', 'reddedildi'])) {
                $query->where('at.durum', $durum);
            }

            $talepler = $query->orderByDesc('at.id')->paginate(25)->withQueryString();
        }

        $bekleyenSayi = 0;
        if ($onayYetkisi && Schema::hasTable('avans_talepleri')) {
            $bekleyenSayi = DB::table('avans_talepleri')->where('durum', 'bekliyor')->count();
        }

        return view('admin.hrm.avans.index', compact('talepler', 'onayYetkisi', 'durum', 'bekleyenSayi'));
    }

    /**
     * Yeni avans talebi formu.
     */
    public function olustur()
    {
        $aktifId = session('admin_id');
        $ozet = $this->kendiOzet($aktifId);
        return view('admin.hrm.avans.olustur', compact('ozet'));
    }

    /**
     * Avans talebini kaydet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tutar' => 'required|numeric|min:1',
            'aciklama' => 'nullable|string',
        ], [
            'tutar.required' => 'Tutar zorunludur.',
            'tutar.numeric' => 'Tutar sayisal olmalidir.',
            'tutar.min' => 'Tutar en az 1 TL olmalidir.',
        ]);

        if (!Schema::hasTable('avans_talepleri')) {
            return back()->withInput()->with('error', 'Avans tablosu bulunamadi. Lutfen SQL kurulumunu calistirin.');
        }

        $aktifId = session('admin_id');
        if (!$aktifId) {
            return back()->withInput()->with('error', 'Oturum bulunamadi.');
        }

        DB::table('avans_talepleri')->insert([
            'yonetici_id' => $aktifId,
            'tutar' => $validated['tutar'],
            'aciklama' => $validated['aciklama'] ?? null,
            'durum' => 'bekliyor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->onaylayicilaraBildir($aktifId, (float) $validated['tutar']);

        return redirect()->route('admin.hrm.avans.index')->with('success', 'Avans talebiniz olusturuldu. Onay bekleniyor.');
    }

    /**
     * Avans onayla (Patron + Muhasebe).
     */
    public function onayla($id)
    {
        if (!HrmYetki::izinOnaylayabilir()) {
            return back()->with('error', 'Bu islem icin yetkiniz yok.');
        }

        $talep = DB::table('avans_talepleri')->where('id', $id)->first();
        if (!$talep || $talep->durum !== 'bekliyor') {
            return back()->with('error', 'Talep bulunamadi veya zaten islenmis.');
        }

        DB::table('avans_talepleri')->where('id', $id)->update([
            'durum' => 'onaylandi',
            'onaylayan_id' => session('admin_id'),
            'onay_tarihi' => now(),
            'updated_at' => now(),
        ]);

        $this->personeleBildir($talep, 'onaylandi');

        return back()->with('success', 'Avans talebi onaylandi.');
    }

    /**
     * Avans reddet (Patron + Muhasebe).
     */
    public function reddet(Request $request, $id)
    {
        if (!HrmYetki::izinOnaylayabilir()) {
            return back()->with('error', 'Bu islem icin yetkiniz yok.');
        }

        $request->validate(['red_nedeni' => 'nullable|string|max:255']);

        $talep = DB::table('avans_talepleri')->where('id', $id)->first();
        if (!$talep || $talep->durum !== 'bekliyor') {
            return back()->with('error', 'Talep bulunamadi veya zaten islenmis.');
        }

        DB::table('avans_talepleri')->where('id', $id)->update([
            'durum' => 'reddedildi',
            'onaylayan_id' => session('admin_id'),
            'onay_tarihi' => now(),
            'red_nedeni' => $request->get('red_nedeni'),
            'updated_at' => now(),
        ]);

        $this->personeleBildir($talep, 'reddedildi');

        return back()->with('success', 'Avans talebi reddedildi.');
    }

    /**
     * Talep sil (sadece kendi bekleyen talebini veya onay yetkilisi).
     */
    public function sil($id)
    {
        $talep = DB::table('avans_talepleri')->where('id', $id)->first();
        if (!$talep) {
            return back()->with('error', 'Talep bulunamadi.');
        }

        $aktifId = session('admin_id');
        $kendi = ((int) $talep->yonetici_id === (int) $aktifId);
        if (!$kendi && !HrmYetki::izinOnaylayabilir()) {
            return back()->with('error', 'Bu islem icin yetkiniz yok.');
        }

        DB::table('avans_talepleri')->where('id', $id)->delete();
        return back()->with('success', 'Avans talebi silindi.');
    }

    /**
     * Aktif kullanicinin avans ozeti (bu yil onaylanan + bekleyen toplam).
     */
    private function kendiOzet($yoneticiId): array
    {
        $onaylanan = 0.0;
        $bekleyen = 0.0;
        if (Schema::hasTable('avans_talepleri')) {
            $onaylanan = (float) DB::table('avans_talepleri')
                ->where('yonetici_id', $yoneticiId)
                ->where('durum', 'onaylandi')
                ->whereYear('created_at', now()->year)
                ->sum('tutar');
            $bekleyen = (float) DB::table('avans_talepleri')
                ->where('yonetici_id', $yoneticiId)
                ->where('durum', 'bekliyor')
                ->sum('tutar');
        }

        return ['onaylanan' => $onaylanan, 'bekleyen' => $bekleyen];
    }

    /**
     * Yeni talepte onaylayicilara (Patron+Muhasebe) bildirim.
     */
    private function onaylayicilaraBildir($talepEdenId, float $tutar): void
    {
        if (!Schema::hasTable('admin_bildirimler')) {
            return;
        }
        try {
            $talepEden = DB::table('yoneticiler')->where('id', $talepEdenId)->value('adi') ?? 'Personel';
            $onaylayicilar = DB::table('yoneticiler')
                ->whereIn('rol', HrmYetki::ONAY_ROLLERI)
                ->where('durum', 1)
                ->get();

            $hasYoneticiId = Schema::hasColumn('admin_bildirimler', 'yonetici_id');
            $tutarMetni = number_format($tutar, 2, ',', '.');

            foreach ($onaylayicilar as $o) {
                $b = [
                    'tip' => 'avans_talebi',
                    'baslik' => 'Yeni Avans Talebi',
                    'mesaj' => "{$talepEden} — {$tutarMetni} TL avans onay bekliyor.",
                    'ilgili_tablo' => 'avans_talepleri',
                    'okundu' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($hasYoneticiId) {
                    $b['yonetici_id'] = $o->id;
                }
                DB::table('admin_bildirimler')->insert($b);
            }
        } catch (\Throwable $e) {
            \Log::warning('Avans onaylayici bildirimi hatasi', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Onay/red sonrasi personele bildirim + mail.
     */
    private function personeleBildir($talep, $sonuc): void
    {
        try {
            $personel = DB::table('yoneticiler')->where('id', $talep->yonetici_id)->first();
            if (!$personel) {
                return;
            }

            $sonucMetni = $sonuc === 'onaylandi' ? 'onaylandi' : 'reddedildi';
            $sonucGoster = $sonuc === 'onaylandi' ? 'Onaylandı' : 'Reddedildi';
            $tutarMetni = number_format((float) $talep->tutar, 2, ',', '.');

            if (Schema::hasTable('admin_bildirimler')) {
                $b = [
                    'tip' => 'avans_sonuc',
                    'baslik' => 'Avans Talebiniz ' . $sonucGoster,
                    'mesaj' => "Avans talebiniz ({$tutarMetni} TL) " . ($sonuc === 'onaylandi' ? 'onaylandı' : 'reddedildi') . ".",
                    'ilgili_id' => $talep->id,
                    'ilgili_tablo' => 'avans_talepleri',
                    'okundu' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
                    $b['yonetici_id'] = $personel->id;
                }
                DB::table('admin_bildirimler')->insert($b);
            }

            $email = $personel->email ?? $personel->eposta ?? null;
            if ($email) {
                $renk = $sonuc === 'onaylandi' ? '#10b981' : '#ef4444';
                $body = "<p>Merhaba <strong>" . ($personel->adi ?? '') . "</strong>,</p>"
                    . "<p>Avans talebiniz hakkında bir güncelleme var:</p>"
                    . "<div style='background:#f8fafc;border-left:4px solid {$renk};padding:14px;border-radius:8px;margin:14px 0'>"
                    . "<strong>Tutar:</strong> {$tutarMetni} TL<br>"
                    . "<strong>Sonuç:</strong> " . $sonucGoster
                    . (!empty($talep->red_nedeni) ? "<br><strong>Not:</strong> " . e($talep->red_nedeni) : '')
                    . "</div>";
                NotificationMailer::send($email, 'Avans Talebiniz ' . $sonucGoster, $body, $personel->adi ?? null);
            }
        } catch (\Throwable $e) {
            \Log::warning('Avans sonuc bildirimi hatasi', ['err' => $e->getMessage()]);
        }
    }
}