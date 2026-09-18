<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminStats
{
    /**
     * Standart 4 kart: Toplam, Aktif, Pasif, Bu Ay.
     * $opt: ['durum'=>'durum', 'aktif_value'=>1, 'pasif_value'=>0, 'tarih'=>'created_at',
     *         'labels'=>['toplam'=>'TOPLAM','aktif'=>'AKTİF','pasif'=>'PASİF','buay'=>'BU AY'],
     *         'icons'=>['📊','✅','⏸️','📅']]
     */
    public static function standart(string $tablo, array $opt = []): array
    {
        if (!Schema::hasTable($tablo)) return [];

        $cols = Schema::getColumnListing($tablo);
        $durumCol = $opt['durum'] ?? (in_array('durum', $cols) ? 'durum' : null);
        $aktifVal = $opt['aktif_value'] ?? 1;
        $pasifVal = $opt['pasif_value'] ?? 0;
        $tarihCol = $opt['tarih'] ?? (in_array('created_at', $cols) ? 'created_at' : (in_array('tarih', $cols) ? 'tarih' : null));

        $labels = array_merge(['toplam' => 'TOPLAM', 'aktif' => 'AKTİF', 'pasif' => 'PASİF', 'buay' => 'BU AY'], $opt['labels'] ?? []);
        $icons  = array_merge(['toplam' => '📊', 'aktif' => '✅', 'pasif' => '⏸️', 'buay' => '📅'], $opt['icons'] ?? []);

        $toplam  = DB::table($tablo)->count();
        $aktif   = $durumCol ? DB::table($tablo)->where($durumCol, $aktifVal)->count() : null;
        $pasif   = $durumCol ? DB::table($tablo)->where($durumCol, $pasifVal)->count() : null;

        $buAy = null;
        if ($tarihCol) {
            $baslangic = now()->startOfMonth()->toDateTimeString();
            try {
                if ($tarihCol === 'tarih') {
                    $buAy = DB::table($tablo)->whereRaw("STR_TO_DATE($tarihCol, '%Y-%m-%d %H:%i:%s') >= ?", [$baslangic])->count();
                } else {
                    $buAy = DB::table($tablo)->where($tarihCol, '>=', $baslangic)->count();
                }
            } catch (\Throwable $e) { $buAy = null; }
        }

        $cards = [
            ['label' => $labels['toplam'], 'value' => $toplam, 'icon' => $icons['toplam'], 'color' => '#3b82f6', 'text' => '#60a5fa'],
        ];
        if ($aktif !== null) $cards[] = ['label' => $labels['aktif'], 'value' => $aktif, 'icon' => $icons['aktif'], 'color' => '#22c55e', 'text' => '#4ade80'];
        if ($pasif !== null) $cards[] = ['label' => $labels['pasif'], 'value' => $pasif, 'icon' => $icons['pasif'], 'color' => '#ef4444', 'text' => '#fca5a5'];
        if ($buAy !== null)  $cards[] = ['label' => $labels['buay'],  'value' => $buAy,  'icon' => $icons['buay'],  'color' => '#b8b62e', 'text' => '#d4d066'];

        return $cards;
    }
}
