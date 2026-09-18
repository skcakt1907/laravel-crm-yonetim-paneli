<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Randevu SMS gönderimi.
 * 1) Projede mevcut bir SMS servisi varsa (SmsService vb.) önce onu kullanır.
 * 2) Yoksa ayarlar tablosundaki SMS bilgileriyle Netgsm API üzerinden gönderir.
 */
class RandevuSms
{
    /** @return bool gönderim başarılı mı */
    public static function gonder(?string $telefon, string $mesaj): bool
    {
        $tel = self::normalize($telefon);
        if (!$tel) return false;

        // 1) Panelin kendi SMS servisi (Toplu Mesaj / Test Gönder ile aynı altyapı)
        if (class_exists('\\App\\Services\\SmsService')) {
            try {
                $servis = new \App\Services\SmsService();
                if (method_exists($servis, 'send')) {
                    $r = $servis->send($tel, $mesaj);
                    $basarili = is_array($r) ? !empty($r['success']) : ($r !== false);
                    Log::info('RandevuSms: SmsService kullanıldı', ['tel' => $tel, 'ok' => $basarili]);
                    if ($basarili) return true;
                    Log::warning('RandevuSms: SmsService başarısız döndü', ['yanit' => is_array($r) ? ($r['message'] ?? $r['error'] ?? '?') : $r]);
                    return false; // ayarlar/sağlayıcı sorunu — Netgsm tahminiyle üstüne gitme
                }
            } catch (\Throwable $e) {
                Log::warning('RandevuSms: SmsService hata verdi, Netgsm denenecek', ['hata' => $e->getMessage()]);
            }
        }

        // 2) Ayarlar tablosundan SMS bilgileri (sütun adları esnek aranır)
        try {
            $a = DB::table('ayarlar')->first();
        } catch (\Throwable $e) {
            Log::error('RandevuSms: ayarlar tablosu okunamadı', ['hata' => $e->getMessage()]);
            return false;
        }
        if (!$a) return false;

        $al = function (array $adaylar) use ($a) {
            foreach ($adaylar as $k) {
                if (isset($a->$k) && trim((string) $a->$k) !== '') return trim((string) $a->$k);
            }
            return null;
        };

        $kullanici = $al(['sms_kullanici_adi', 'sms_kullanici', 'sms_usercode', 'sms_username', 'sms_user']);
        $sifre     = $al(['sms_sifre', 'sms_password', 'sms_pass']);
        $baslik    = $al(['sms_baslik', 'sms_header', 'sms_title', 'sms_msgheader']) ?: 'DN KREATIF';

        if (!$kullanici || !$sifre) {
            Log::warning('RandevuSms: SMS ayarları bulunamadı (kullanıcı/şifre sütunları)', ['tel' => $tel]);
            return false;
        }

        // Netgsm GET API
        try {
            $resp = Http::timeout(15)->get('https://api.netgsm.com.tr/sms/send/get', [
                'usercode'  => $kullanici,
                'password'  => $sifre,
                'gsmno'     => $tel,
                'message'   => $mesaj,
                'msgheader' => $baslik,
                'dil'       => 'TR',
            ]);
            $govde = trim($resp->body());
            $okMu  = $resp->successful() && (str_starts_with($govde, '00') || str_starts_with($govde, '01') || str_starts_with($govde, '02'));
            Log::info('RandevuSms: Netgsm yanıtı', ['tel' => $tel, 'yanit' => mb_substr($govde, 0, 80), 'ok' => $okMu]);
            return $okMu;
        } catch (\Throwable $e) {
            Log::error('RandevuSms: Netgsm isteği başarısız', ['hata' => $e->getMessage(), 'tel' => $tel]);
            return false;
        }
    }

    /** 05xxxxxxxxx biçimine getirir */
    public static function normalize(?string $tel): ?string
    {
        if (!$tel) return null;
        $t = preg_replace('/\D+/', '', $tel);
        if (str_starts_with($t, '90') && strlen($t) === 12) $t = substr($t, 2);
        if (strlen($t) === 10 && str_starts_with($t, '5'))  $t = '0' . $t;
        return (strlen($t) === 11 && str_starts_with($t, '05')) ? $t : null;
    }
}