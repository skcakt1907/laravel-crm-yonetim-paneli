<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Giriş loglama yardımcısı.
 * Hem yönetici hem üye giriş denemelerini tek tabloya (giris_loglari) yazar.
 * Tablo yoksa veya hata olursa sessizce geçer — giriş akışını ASLA bozmaz.
 */
class GirisLogHelper
{
    /**
     * @param string      $tip       'yonetici' | 'uye'
     * @param string      $durum     'basarili' | 'basarisiz' | 'bloke'
     * @param string|null $email     Girişte kullanılan e-posta/kullanıcı adı
     * @param string|null $kullaniciAdi Gösterim adı
     * @param int|null    $kullaniciId
     * @param string|null $aciklama  Sebep (başarısız/bloke için)
     */
    public static function kaydet(
        string $tip,
        string $durum,
        ?string $email = null,
        ?string $kullaniciAdi = null,
        ?int $kullaniciId = null,
        ?string $aciklama = null
    ): void {
        try {
            if (!Schema::hasTable('giris_loglari')) {
                return;
            }

            $req = request();

            DB::table('giris_loglari')->insert([
                'kullanici_tipi' => in_array($tip, ['yonetici', 'uye']) ? $tip : 'uye',
                'kullanici_id'   => $kullaniciId,
                'kullanici_adi'  => $kullaniciAdi ? mb_substr($kullaniciAdi, 0, 191) : null,
                'email'          => $email ? mb_substr($email, 0, 191) : null,
                'ip'             => $req ? $req->ip() : null,
                'durum'          => in_array($durum, ['basarili', 'basarisiz', 'bloke']) ? $durum : 'basarisiz',
                'aciklama'       => $aciklama ? mb_substr($aciklama, 0, 191) : null,
                'user_agent'     => $req ? mb_substr((string) $req->userAgent(), 0, 255) : null,
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Log kaydı giriş akışını bozmamalı — sessizce yut
        }
    }
}