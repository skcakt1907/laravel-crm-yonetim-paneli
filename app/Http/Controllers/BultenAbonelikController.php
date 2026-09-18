<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BÜLTEN ABONELİKTEN ÇIKMA (12.08.2026)
 *
 * NEDEN VAR: Gmail ve Outlook, toplu/pazarlama e-postalarında
 * `List-Unsubscribe` başlığını ZORUNLU tutuyor (Gmail'in Şubat 2024
 * toplu gönderici kuralları). Bu başlık yoksa mailler spam'e düşüyor ve
 * gönderen adresin itibarı bozuluyor; aynı adresten çıkan doğrulama
 * kodu / şifre sıfırlama gibi KRİTİK mailler de spam'e düşmeye başlıyor.
 *
 * GİRİŞ GEREKTİRMEZ: Kullanıcı maildeki bağlantıya tıklayınca hesabına
 * girmeden çıkabilmeli. Güvenlik için Laravel'in imzalı URL'i kullanılır
 * (bağlantı kurcalanırsa geçersiz olur), böylece kimse başkasının
 * aboneliğini iptal edemez.
 */
class BultenAbonelikController extends Controller
{
    /** Maildeki bağlantı — onay ekranını gösterir (GET) */
    public function goster(Request $request, string $email)
    {
        /*
         * DİKKAT: hasValidSignature() DEĞİL, ...WhileIgnoring(['lang']).
         * SetLocale middleware'i isteği yakalayıp URL'e '&lang=tr' ekleyerek
         * yönlendiriyor; bu ek parametre imzayı bozduğu için düz
         * hasValidSignature() bağlantıyı 403 ile reddediyordu (12.08.2026'da
         * test sırasında yakalandı — canlıda abonelikten çıkma hiç
         * çalışmayacaktı). 'lang' imza dışı bırakılarak çözüldü.
         */
        if (!$request->hasValidSignatureWhileIgnoring(['lang'])) {
            abort(403, 'Bu bağlantı geçersiz veya süresi dolmuş.');
        }

        return view('tema.bulten-cik', [
            'email'   => $email,
            'cikildi' => false,
        ]);
    }

    /**
     * Abonelikten çıkar (POST).
     *
     * Gmail'in "tek tıkla abonelikten çık" özelliği bu adrese kullanıcı
     * onayı olmadan POST atar (List-Unsubscribe-Post başlığı), bu yüzden
     * metot hem form gönderimini hem o otomatik isteği karşılar.
     */
    public function cik(Request $request, string $email)
    {
        /*
         * DİKKAT: hasValidSignature() DEĞİL, ...WhileIgnoring(['lang']).
         * SetLocale middleware'i isteği yakalayıp URL'e '&lang=tr' ekleyerek
         * yönlendiriyor; bu ek parametre imzayı bozduğu için düz
         * hasValidSignature() bağlantıyı 403 ile reddediyordu (12.08.2026'da
         * test sırasında yakalandı — canlıda abonelikten çıkma hiç
         * çalışmayacaktı). 'lang' imza dışı bırakılarak çözüldü.
         */
        if (!$request->hasValidSignatureWhileIgnoring(['lang'])) {
            abort(403, 'Bu bağlantı geçersiz veya süresi dolmuş.');
        }

        $guncellendi = false;
        try {
            if (Schema::hasTable('uyeler')) {
                $veri = [];
                if (Schema::hasColumn('uyeler', 'kampanya_mail_izin')) {
                    $veri['kampanya_mail_izin'] = 0;
                }
                if ($veri) {
                    $guncellendi = DB::table('uyeler')->where('email', $email)->update($veri) > 0;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Bülten abonelik iptali başarısız', ['email' => $email, 'hata' => $e->getMessage()]);
        }

        // Gmail'in otomatik tek-tık isteği: sayfa değil, sade 200 bekler
        if ($request->expectsJson() || $request->input('List-Unsubscribe') === 'One-Click') {
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        return view('tema.bulten-cik', [
            'email'   => $email,
            'cikildi' => true,
            'bulundu' => $guncellendi,
        ]);
    }
}
