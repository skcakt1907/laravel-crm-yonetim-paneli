<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * CRM adayını (crm_customers) gerçek kayıtlı üyeye (uyeler) çevirir.
 *
 * Mantık, CustomerController@uyeOlustur ile aynıdır; canlı büyük controller'ı riske
 * atmamak için ayrı servise alındı. Şema-duyarlı insert (uyeler sütunları değişse de
 * uyumlu), mükerrer engelli (aynı e-posta ikinci kez üye yaratmaz).
 */
class CrmUyeOlusturucu
{
    /**
     * @return array{uye_id:?int, sifre:?string, yeni:bool, hata:?string}
     */
    public static function olustur(object $crmCustomer): array
    {
        $email = trim((string) ($crmCustomer->email ?? ''));
        if ($email === '') {
            return ['uye_id' => null, 'sifre' => null, 'yeni' => false, 'hata' => 'E-posta olmadan üye oluşturulamaz.'];
        }

        // Zaten üye mi? (mükerrer engelle)
        $mevcut = DB::table('uyeler')->where('email', $email)->value('id');
        if ($mevcut) {
            return ['uye_id' => (int) $mevcut, 'sifre' => null, 'yeni' => false, 'hata' => null];
        }

        try {
            // Ad/soyad parçala
            $tamAd = trim(($crmCustomer->adi ?? '') . ' ' . ($crmCustomer->soyad ?? ''));
            if ($tamAd === '') {
                $tamAd = (string) ($crmCustomer->unvan ?? $email);
            }
            $parca = explode(' ', $tamAd, 2);
            $ad    = $parca[0] ?? '';
            $soyad = $parca[1] ?? '';

            $duzSifre = Str::random(12);   // admin sonra sıfırlayabilir / gönderebilir

            $kolonlar = Schema::getColumnListing('uyeler');

            // Tüm potansiyel alanlar — tabloda olmayanlar aşağıda filtrelenir
            $potansiyel = [
                'ad'         => $ad,
                'soyad'      => $soyad,
                'email'      => $email,
                'telefon'    => $crmCustomer->telefon ?? '',
                'sifre'      => Hash::make($duzSifre),
                'utipi'      => 0,
                'cinsiyet'   => 'Belirtilmedi',
                'firmaadi'   => $crmCustomer->unvan ?? '',
                'il'         => $crmCustomer->il ?? '',
                'ilce'       => $crmCustomer->ilce ?? '',
                'adres'      => $crmCustomer->adres ?? '',
                'durum'      => 1,
                'bakiye'     => 0,
                'ktarih'     => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $data  = array_intersect_key($potansiyel, array_flip($kolonlar));
            $uyeId = (int) DB::table('uyeler')->insertGetId($data);

            return ['uye_id' => $uyeId, 'sifre' => $duzSifre, 'yeni' => true, 'hata' => null];
        } catch (\Throwable $e) {
            Log::error('CrmUyeOlusturucu: üye oluşturulamadı', [
                'email' => $email,
                'hata'  => $e->getMessage(),
            ]);
            return ['uye_id' => null, 'sifre' => null, 'yeni' => false, 'hata' => 'Üye oluşturulamadı: ' . $e->getMessage()];
        }
    }
}
