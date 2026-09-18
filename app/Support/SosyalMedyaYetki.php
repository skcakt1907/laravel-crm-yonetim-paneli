<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * SOSYAL MEDYA TAKİP — yetki kontrolü
 *
 * NEDEN AYRI BİR SINIF:
 * İlk sürümde controller'lar `rol` alanını 1 (Patron) ve 2 (Çalışan) ile
 * karşılaştırıyordu. Bu YANLIŞTI — bu panelde roller 1-4 ile sınırlı değil:
 * canlıda Muhasebe=5, Sosyal Medya=20, Yazılımcı=21 gibi roller var. Sabit
 * liste, tam da bu modülü kullanacak kişileri (Sosyal Medya rolündeki uzman
 * ve Muhasebe) dışarıda bırakıyordu.
 *
 * Doğrusu, panelin kendi yetki sistemini kullanmak:
 *   roller.korumali   -> her zaman açık (SADECE Patron değil — panelin
 *                        RolKontrol middleware'i bu bayrağı taşıyan HER
 *                        role tam erişim veriyor; canlıda "Yazılımcı"
 *                        rolü de korumali=1 olarak işaretli)
 *   roller.tam_yetki  -> her zaman açık
 *   diğer roller      -> rol_yetkileri tablosunda ilgili sayfa için gorebilir=1
 * Böylece erişim "Roller" ekranından veriliyor; kod değişikliği gerekmiyor.
 *
 * DÜZELTME (10.09.2026): İlk sürüm sadece rol_id===1'i "Patron" sayıp
 * korumali bayrağını hiç okumuyordu. Yerel geliştirme veritabanında
 * Yazılımcı rolü tam_yetki=1 olduğu için sorun görünmüyordu; canlıda aynı
 * rol tam_yetki=0 + korumali=1 olarak ayarlıydı ve gerçek bir yönetici
 * (Yazılımcı rolündeki AYKUT) modüle "yetkiniz yok" ile giremedi. korumali
 * kontrolü eklendi — artık RolKontrol middleware'iyle birebir aynı kuralı
 * uyguluyor.
 *
 * RolKontrol middleware'i GET isteklerinde bu kontrolü zaten yapıyor. Bu sınıf
 * YAZMA uçları (POST/DELETE) içindir: middleware bilinçli olarak GET dışını
 * kontrol etmiyor ("bir bölümü görebilen, oradaki işlemi de yapabilmeli"),
 * fakat o kural yalnızca sayfayı görebilenler için geçerli olmalı — yoksa
 * panele giren herkes doğrudan POST atabilirdi.
 */
class SosyalMedyaYetki
{
    /** Yetkinin okunduğu sayfa — modülün giriş ekranı */
    public const SAYFA = 'admin.crm.sosyal-medya-takip.index';

    public static function varMi(?string $sayfa = null): bool
    {
        $rolId = (int) session('admin_rol', 0);

        if ($rolId <= 0) {
            return false;
        }

        $rol = DB::table('roller')->where('id', $rolId)->first();

        if (! $rol || (int) $rol->durum !== 1) {
            return false;
        }

        // korumali VEYA tam_yetki -> tam erişim. İkisi ayrı bayrak; hangisi
        // işaretliyse işaretli, panelde ikisi de "her yeri gör" anlamına
        // gelir (bkz. RolKontrol middleware).
        if ((int) $rol->korumali === 1 || (int) $rol->tam_yetki === 1) {
            return true;
        }

        return DB::table('rol_yetkileri')
            ->where('rol_id', $rolId)
            ->where('sayfa_route', $sayfa ?: self::SAYFA)
            ->where('gorebilir', 1)
            ->exists();
    }

    /** Yetki yoksa 403 ile durdurur */
    public static function zorunlu(?string $sayfa = null): void
    {
        abort_unless(self::varMi($sayfa), 403, 'Bu bölüme erişim yetkiniz yok.');
    }
}
