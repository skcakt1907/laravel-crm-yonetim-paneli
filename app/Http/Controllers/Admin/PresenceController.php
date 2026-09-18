<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    // Eşikler (saniye)
    const CEVRIMDISI_ESIK = 150;   // 2.5 dk heartbeat yoksa → çevrimdışı
    const UZAKTA_ESIK     = 900;   // 15 dk hareket yoksa → uzakta

    /** Tarayıcıdan gelen heartbeat. Sekme açıkken ~60 sn'de bir çağrılır. */
    public function ping(Request $request)
    {
        $id = (int) session('admin_id');
        if (!$id) {
            return response()->json(['ok' => false], 401);
        }

        $data = ['son_gorulme' => now()];
        // aktif=1 → kullanıcı son 15 dk içinde gerçek bir etkileşim yaptı
        if ($request->boolean('aktif', true)) {
            $data['son_etkinlik'] = now();
        }

        DB::table('yoneticiler')->where('id', $id)->update($data);

        return response()->json(['ok' => true]);
    }

    /** Bir yöneticinin durumu: cevrimici | uzakta | cevrimdisi */
    public static function durum($sonGorulme, $sonEtkinlik): string
    {
        if (!$sonGorulme) {
            return 'cevrimdisi';
        }
        $g = strtotime((string) $sonGorulme);
        if (!$g || (time() - $g) > self::CEVRIMDISI_ESIK) {
            return 'cevrimdisi';
        }
        $e = $sonEtkinlik ? strtotime((string) $sonEtkinlik) : 0;
        if (!$e || (time() - $e) > self::UZAKTA_ESIK) {
            return 'uzakta';
        }
        return 'cevrimici';
    }

    /** Durum için renk + etiket (UI). */
    public static function durumBilgi(string $durum): array
    {
        return [
            'cevrimici'  => ['renk' => '#22c55e', 'etiket' => 'Çevrimiçi'],
            'uzakta'     => ['renk' => '#f59e0b', 'etiket' => 'Uzakta'],
            'cevrimdisi' => ['renk' => '#9ca3af', 'etiket' => 'Çevrimdışı'],
        ][$durum] ?? ['renk' => '#9ca3af', 'etiket' => 'Çevrimdışı'];
    }
}
