<?php

namespace App\Services\AI;

use Anthropic\Client;
use Anthropic\Messages\ToolUseBlock;
use Illuminate\Support\Facades\Log;

/**
 * AI Asistan — Claude bağlantısı ve araç döngüsü (Faz 1a).
 *
 * Akış:
 *   1) Kullanıcının sorusu + araç listesi Claude'a gönderilir
 *   2) Claude hangi aracın çalışacağını söyler (ham SQL değil, sadece araç adı)
 *   3) Sorguyu BİZİM sunucumuz çalıştırır, sonucu geri verir
 *   4) Claude sonucu Türkçe cümleye çevirir
 *
 * Güvenlik kuralları (Faz 1a):
 *   - Sadece OKUMA araçları var; hiçbir veri değişmez
 *   - Mail/SMS gönderme aracı YOK
 *   - Kişisel veriler maskelenerek gönderilir (bkz. AsistanAraclari)
 */
class AsistanServisi
{
    /** Asistan kullanılabilir mi? (API anahtarı girilmiş mi) */
    public static function aktifMi(): bool
    {
        return !empty(config('ai.anthropic.key'));
    }

    /**
     * Bir soruyu yanıtlar.
     *
     * @param  string $soru
     * @param  array  $gecmis  Önceki mesajlar (Anthropic messages formatı)
     * @return array{ok: bool, cevap: string, gecmis: array, kullanilan_araclar: array}
     */
    public function sor(string $soru, array $gecmis = []): array
    {
        if (!self::aktifMi()) {
            return [
                'ok'    => false,
                'cevap' => 'Yapay zekâ asistanı henüz etkin değil — API anahtarı tanımlanmamış. '
                         . 'Sunucudaki .env dosyasına ANTHROPIC_API_KEY eklenmelidir.',
                'gecmis' => $gecmis,
                'kullanilan_araclar' => [],
            ];
        }

        $client = new Client(apiKey: config('ai.anthropic.key'));
        $model  = config('ai.anthropic.model');

        $mesajlar   = $gecmis;
        $mesajlar[] = ['role' => 'user', 'content' => $soru];

        $kullanilan = [];
        $azamiTur   = (int) config('ai.azami_tur', 6);

        try {
            $yanit = $client->messages->create(
                model: $model,
                maxTokens: (int) config('ai.anthropic.max_tokens', 4096),
                system: $this->sistemTalimati(),
                tools: AsistanAraclari::tanimlar(),
                messages: $mesajlar,
            );

            $tur = 0;
            while ($yanit->stopReason === 'tool_use' && $tur < $azamiTur) {
                $tur++;
                $aracSonuclari = [];

                foreach ($yanit->content as $blok) {
                    if (!$blok instanceof ToolUseBlock) {
                        continue;
                    }

                    // Faz 1a güvencesi: yazma aracı varsa ÇALIŞTIRMA, onay iste.
                    if (AsistanAraclari::yazmaAraci($blok->name)) {
                        $cikti = json_encode([
                            'durum' => 'onay_bekliyor',
                            'not'   => 'Bu işlem veriyi değiştirir. Şu an sadece bilgi verebilirim; '
                                     . 'değişiklik yapma özelliği henüz açık değil.',
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        $cikti = AsistanAraclari::calistir($blok->name, (array) $blok->input);
                    }

                    $kullanilan[]    = $blok->name;
                    $aracSonuclari[] = [
                        'type'      => 'tool_result',
                        'toolUseID' => $blok->id,
                        'content'   => $cikti,
                    ];
                }

                $mesajlar[] = ['role' => 'assistant', 'content' => $yanit->content];
                $mesajlar[] = ['role' => 'user', 'content' => $aracSonuclari];

                $yanit = $client->messages->create(
                    model: $model,
                    maxTokens: (int) config('ai.anthropic.max_tokens', 4096),
                    system: $this->sistemTalimati(),
                    tools: AsistanAraclari::tanimlar(),
                    messages: $mesajlar,
                );
            }

            $cevap = '';
            foreach ($yanit->content as $blok) {
                if ($blok->type === 'text') {
                    $cevap .= $blok->text;
                }
            }

            if (trim($cevap) === '') {
                $cevap = 'Soruyu anlayamadım, biraz daha açık yazar mısın?';
            }

            // Oturumda SADECE düz metin turları saklanır (araç blokları saklanmaz):
            // hem oturum verisi küçük kalır hem de serialize sorunu yaşanmaz.
            $kalici   = $gecmis;
            $kalici[] = ['role' => 'user', 'content' => $soru];
            $kalici[] = ['role' => 'assistant', 'content' => $cevap];

            return [
                'ok'     => true,
                'cevap'  => $cevap,
                'gecmis' => $this->gecmisiKisalt($kalici),
                'kullanilan_araclar' => array_values(array_unique($kullanilan)),
            ];
        } catch (\Throwable $e) {
            Log::error('AI asistan hatası', ['mesaj' => $e->getMessage()]);

            return [
                'ok'     => false,
                'cevap'  => 'Yapay zekâ servisine şu an ulaşılamadı. Birazdan tekrar dener misin?',
                'gecmis' => $gecmis,
                'kullanilan_araclar' => [],
            ];
        }
    }

    /** Claude'a verilen kimlik ve kurallar. */
    private function sistemTalimati(): string
    {
        $kullanici = session('admin_adi') ?: session('admin_ad') ?: 'yönetici';
        $bugun     = date('d.m.Y');

        return <<<TXT
        Sen "İş Ortağım" yönetim panelinin Türkçe yapay zekâ asistanısın. İş Ortağım;
        web tasarım, yazılım, hosting, domain, dijital pazarlama ve tanıtım hizmetleri
        satan bir firmanın müşteri/satış yönetim panelidir.

        Konuştuğun kişi: {$kullanici}. Bugünün tarihi: {$bugun}.

        KURALLAR:
        - Sadece Türkçe, kısa ve net cevap ver. Gereksiz giriş cümlesi kurma.
        - Panel verisi gerektiren her soruda MUTLAKA sana verilen araçları kullan.
          Sayı, fiyat, isim veya tarih ASLA uydurma; bilmiyorsan aracı çağır.
        - Araç sonucu boşsa "kayıt bulunamadı" de; tahminde bulunma.
        - Para tutarlarını "12.500 TL" biçiminde yaz.
        - Şu an SADECE bilgi verebilirsin. Kullanıcı bir şeyi değiştirmeni, silmeni,
          indirim uygulamanı, e-posta veya SMS göndermeni isterse: bunu henüz
          yapamadığını, bu özelliğin onay ekranıyla birlikte yakında geleceğini söyle.
        - Kişisel veriler (e-posta, telefon) maskeli gelir; maskeyi kaldırmaya çalışma
          ve tam bilgi için panelin ilgili sayfasına yönlendir.
        TXT;
    }

    /** Sohbet geçmişini sınırlar (token maliyeti). */
    private function gecmisiKisalt(array $mesajlar): array
    {
        $azami = (int) config('ai.azami_gecmis', 20);

        return count($mesajlar) <= $azami ? $mesajlar : array_slice($mesajlar, -$azami);
    }
}
