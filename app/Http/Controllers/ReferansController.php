<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReferansController extends Controller
{
    /**
     * Referans link tıklamasını kaydet
     */
    public function tikla(Request $request, $ref)
    {
        // Bayi kodunu kontrol et
        $bayi = DB::table('bayiler')->where('bayi_kodu', $ref)->first();
        
        if ($bayi && Schema::hasTable('referans_tiklanmalar')) {
            DB::table('referans_tiklanmalar')->insert([
                'bayi_kodu' => $ref,
                'bayi_id' => $bayi->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'created_at' => now(),
            ]);
        }
        
        // Kayıt sayfasına yönlendir ve ref parametresini session'a kaydet
        session(['referans_kodu' => $ref]);
        
        return redirect()->route('kayit', ['ref' => $ref]);
    }
}

