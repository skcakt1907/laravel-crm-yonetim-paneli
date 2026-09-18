<?php

/**
 * Yapay Zekâ Asistanı ayarları (Faz 1).
 * API anahtarı .env'e girilince asistan otomatik aktifleşir.
 */
return [
    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY', ''),
        // Varsayılan: Opus 5 (kurumsal/ajan işleri için önerilen model — $5/$25 per 1M token).
        // Daha ucuz istenirse .env'de ANTHROPIC_MODEL=claude-haiku-4-5 yazmak yeterli.
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
        'max_tokens' => 4096,
    ],

    // Bir soruda Claude'un en fazla kaç kez arka arkaya araç çağırabileceği (sonsuz döngü koruması).
    'azami_tur' => 6,

    // Sohbet geçmişinde tutulacak azami mesaj (token maliyeti koruması).
    'azami_gecmis' => 20,

    // Anahtar varsa asistan aktif; yoksa panelde "yakında" modunda kalır.
    'aktif' => !empty(env('ANTHROPIC_API_KEY', '')),
];
