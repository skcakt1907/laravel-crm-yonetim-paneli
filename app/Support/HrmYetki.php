<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * HRM ortak yetki yardımcısı.
 * İzin onaylama yetkisi: Patron (rol=1) + Muhasebe (rol=5).
 * Tek kaynak — ileride RBAC'tan genişletmek için sadece burası değiştirilir.
 */
class HrmYetki
{
    /** İzin onaylayabilen roller (yoneticiler.rol). 1=Patron, 5=Muhasebe */
    public const ONAY_ROLLERI = [1, 5];

    /**
     * Aktif yöneticinin rol id'sini döndürür (session -> DB fallback).
     */
    public static function aktifRol(): ?int
    {
        $rol = session('admin_rol');
        if ($rol !== null && $rol !== '') {
            return (int) $rol;
        }

        $adminId = session('admin_id');
        if (!$adminId) {
            return null;
        }
        $deger = DB::table('yoneticiler')->where('id', $adminId)->value('rol');
        return $deger !== null ? (int) $deger : null;
    }

    /**
     * Aktif yönetici izin onaylayabilir mi?
     */
    public static function izinOnaylayabilir(): bool
    {
        $rol = self::aktifRol();
        return $rol !== null && in_array($rol, self::ONAY_ROLLERI, true);
    }
}