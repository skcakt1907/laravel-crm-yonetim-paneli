<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromoKodOnayController extends Controller
{
    /**
     * Onay bekleyen promo kodları listele
     */
    public function index()
    {
        $promolar = DB::table('bayi_promosyon_kodlar')
            ->leftJoin('bayiler', 'bayi_promosyon_kodlar.bayi_id', '=', 'bayiler.yonetici_id')
            ->leftJoin('uyeler', 'bayiler.uye_id', '=', 'uyeler.id')
            ->select(
                'bayi_promosyon_kodlar.*',
                'uyeler.firmaadi as bayi_adi',
                'bayiler.bayi_kodu'
            )
            ->orderByRaw('CASE WHEN bayi_promosyon_kodlar.onay_durumu = 0 THEN 0 ELSE 1 END')
            ->orderBy('bayi_promosyon_kodlar.created_at', 'desc')
            ->get();
        
        return view('admin.promo-onay.index', compact('promolar'));
    }
    
    /**
     * Promo kodu onayla
     */
    public function onayla($id)
    {
        $promo = DB::table('bayi_promosyon_kodlar')->where('id', $id)->first();
        
        if (!$promo) {
            return redirect()->back()->with('error', 'Promo kod bulunamadı!');
        }
        
        DB::table('bayi_promosyon_kodlar')
            ->where('id', $id)
            ->update([
                'onay_durumu' => 1,
                'onay_tarihi' => now(),
                'updated_at' => now(),
            ]);
        
        // Bayi'ye bildirim gönder
        if (DB::getSchemaBuilder()->hasTable('bayi_bildirimler')) {
            DB::table('bayi_bildirimler')->insert([
                'bayi_id' => $promo->bayi_id,
                'baslik' => __('messages.promo_code_approved'),
                'mesaj' => $promo->kod . ' kodlu promosyon kodunuz onaylandı ve kullanıma hazır.',
                'tip' => 'promo_kod',
                'okundu' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Admin bildirimini okundu olarak işaretle
        DB::table('admin_bildirimler')
            ->where('ilgili_id', $id)
            ->where('ilgili_tablo', 'bayi_promosyon_kodlar')
            ->update(['okundu' => 1, 'okunma_tarihi' => now()]);
        
        return redirect()->back()->with('success', 'Promo kod onaylandı!');
    }
    
    /**
     * Promo kodu reddet
     */
    public function reddet(Request $request, $id)
    {
        $request->validate([
            'red_nedeni' => 'nullable|string|max:500'
        ]);
        
        $promo = DB::table('bayi_promosyon_kodlar')->where('id', $id)->first();
        
        if (!$promo) {
            return redirect()->back()->with('error', 'Promo kod bulunamadı!');
        }
        
        DB::table('bayi_promosyon_kodlar')
            ->where('id', $id)
            ->update([
                'onay_durumu' => 2,
                'red_nedeni' => $request->red_nedeni,
                'onay_tarihi' => now(),
                'updated_at' => now(),
            ]);
        
        // Bayi'ye bildirim gönder
        if (DB::getSchemaBuilder()->hasTable('bayi_bildirimler')) {
            $mesaj = $promo->kod . ' kodlu promosyon kodunuz reddedildi.';
            if ($request->red_nedeni) {
                $mesaj .= ' Neden: ' . $request->red_nedeni;
            }
            
            DB::table('bayi_bildirimler')->insert([
                'bayi_id' => $promo->bayi_id,
                'baslik' => __('messages.promo_code_rejected'),
                'mesaj' => $mesaj,
                'tip' => 'promo_kod',
                'okundu' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Admin bildirimini okundu olarak işaretle
        DB::table('admin_bildirimler')
            ->where('ilgili_id', $id)
            ->where('ilgili_tablo', 'bayi_promosyon_kodlar')
            ->update(['okundu' => 1, 'okunma_tarihi' => now()]);
        
        return redirect()->back()->with('success', 'Promo kod reddedildi!');
    }
}
