<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DN Unity artık bir DURUM değil, ayrı bir LİSTE'dir (crm_listeler "DN Unity").
 * Bu migration, durumu 'dn_unity' olan müşterileri:
 *   1) DN Unity listesine ekler (crm_customer_liste),
 *   2) durumlarını 'aktif' yapar.
 * Geri döndürülemez veri düzeltmesidir (down boş bırakılır — dn_unity durumuna geri dönülmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_customers') || !Schema::hasTable('crm_listeler') || !Schema::hasTable('crm_customer_liste')) {
            return;
        }

        // DN Unity listesini bul (slug veya ad ile); yoksa oluştur
        $liste = DB::table('crm_listeler')
            ->where('slug', 'dn-unity')
            ->orWhere('ad', 'DN Unity')
            ->first();

        if (!$liste) {
            $listeId = DB::table('crm_listeler')->insertGetId([
                'ad'         => 'DN Unity',
                'slug'       => 'dn-unity',
                'renk'       => '#3b82f6',
                'sira'       => 0,
                'durum'      => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $listeId = $liste->id;
        }

        // Durumu dn_unity olan müşteriler
        $musteriIdler = DB::table('crm_customers')->where('durum', 'dn_unity')->pluck('id');
        if ($musteriIdler->isEmpty()) {
            return;
        }

        // Zaten listede olanları çıkar, kalanları ekle
        $mevcut = DB::table('crm_customer_liste')
            ->where('liste_id', $listeId)
            ->whereIn('customer_id', $musteriIdler)
            ->pluck('customer_id')
            ->all();

        $eklenecek = $musteriIdler->reject(fn ($id) => in_array($id, $mevcut, true));
        foreach ($eklenecek as $cid) {
            DB::table('crm_customer_liste')->insert([
                'customer_id' => $cid,
                'liste_id'    => $listeId,
                'liste_durum' => 'aktif',
                'kaynak'      => 'migration:dn_unity',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Durumları 'aktif' yap
        DB::table('crm_customers')->where('durum', 'dn_unity')->update(['durum' => 'aktif']);
    }

    public function down(): void
    {
        // Geri dönüş yok: dn_unity bir durum olarak kaldırıldı.
    }
};
