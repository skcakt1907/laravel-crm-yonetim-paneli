<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * NetGSM SESLİ MESAJ (TTS) servisi.
 * Bir telefon numarasını arayıp verilen metni sesli olarak okutur (dinamik sesli mesaj).
 * Kimlik bilgileri SMS ile AYNI NetGSM hesabıdır (ayarlar tablosu: sms_kullanici_adi / sms_sifre).
 * NOT: NetGSM hesabında "Sesli Mesaj" yetkisi/bakiyesi açık olmalıdır.
 *
 * Endpoint: https://api.netgsm.com.tr/voicesms/send  (POST, Content-Type: text/xml)
 */
class VoiceSmsService
{
    private const ENDPOINT = 'https://api.netgsm.com.tr/voicesms/send';

    /** ayarlar tablosundan SMS/NetGSM kimlik bilgilerini çeker (SMS servisiyle aynı hesap). */
    private static function kimlik(): array
    {
        $a = null;
        try { $a = DB::table('ayarlar')->first(); } catch (\Throwable $e) {}

        $al = function (array $adaylar) use ($a) {
            if (!$a) return null;
            foreach ($adaylar as $k) {
                if (isset($a->$k) && trim((string) $a->$k) !== '') return trim((string) $a->$k);
            }
            return null;
        };

        return [
            'usercode' => $al(['sms_kullanici_adi', 'sms_kullanici', 'sms_usercode', 'sms_username', 'sms_user']),
            'password' => $al(['sms_sifre', 'sms_password', 'sms_pass']),
        ];
    }

    /** Telefonu 10 haneli (5xxxxxxxxx) formata indirger. */
    private static function normalizeTel(string $tel): ?string
    {
        $t = preg_replace('/\D/', '', $tel);
        if ($t === null || $t === '') return null;
        $t = ltrim($t, '0');
        if (str_starts_with($t, '90')) $t = substr($t, 2);
        if (strlen($t) === 10 && $t[0] === '5') return $t;
        return null;
    }

    /**
     * Bir numarayı arayıp metni TTS ile okutur.
     * @return bool  true = NetGSM isteği başarılı (00 kodu)
     */
    public static function tts(string $telefon, string $metin, int $ringtime = 30): bool
    {
        $no = self::normalizeTel($telefon);
        if (!$no) {
            Log::warning('VoiceSmsService: geçersiz telefon', ['tel' => $telefon]);
            return false;
        }

        ['usercode' => $usercode, 'password' => $password] = self::kimlik();
        if (!$usercode || !$password) {
            Log::warning('VoiceSmsService: NetGSM kimlik bilgisi yok (ayarlar tablosu)');
            return false;
        }

        // TTS metni: NetGSM tek satır ister, XML-güvenli hale getir
        $metinSafe = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $metin)), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $xml = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<mainbody>
  <header>
    <usercode>{$usercode}</usercode>
    <password>{$password}</password>
    <startdate></startdate>
    <starttime></starttime>
    <stopdate></stopdate>
    <stoptime></stoptime>
    <ringtime>{$ringtime}</ringtime>
    <key>1</key>
  </header>
  <body>
    <voicemail>
      <scenario>
        <series s='1'><text>{$metinSafe}</text></series>
        <number><no>{$no}</no></number>
      </scenario>
    </voicemail>
  </body>
</mainbody>
XML;

        try {
            $resp = Http::withHeaders(['Content-Type' => 'text/xml'])
                ->timeout(20)
                ->withBody($xml, 'text/xml')
                ->post(self::ENDPOINT);

            $govde = trim($resp->body());
            // NetGSM: başarılı yanıt "00 <jobid>" ile başlar; hata kodları 20/30/40/70 vb.
            $ok = $resp->successful() && str_starts_with($govde, '00');

            if (!$ok) {
                Log::warning('VoiceSmsService: NetGSM hata yanıtı', ['no' => $no, 'yanit' => $govde]);
            }
            return $ok;
        } catch (\Throwable $e) {
            Log::error('VoiceSmsService: istek hatası', ['no' => $no, 'hata' => $e->getMessage()]);
            return false;
        }
    }
}
