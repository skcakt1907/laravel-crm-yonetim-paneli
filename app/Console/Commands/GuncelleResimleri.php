<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GuncelleResimleri extends Command
{
    protected $signature = 'resim:guncelle';
    protected $description = 'yazilimlar.resim kolonunu webpaketresim tablosundan doldurur';

    public function handle()
    {
        $this->info('Resimler güncelleniyor...');
        
        // webpaketresim'den tüm resimleri al, rid'ye göre grupla
        $resimler = DB::table('webpaketresim')
            ->select('rid', 'resim')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('rid');
        
        $guncellenen = 0;
        
        // Her yazilim için ilk resmi yazilimlar.resim kolonuna kaydet
        foreach ($resimler as $rid => $resimGrubu) {
            $ilkResim = $resimGrubu->first()->resim;
            
            DB::table('yazilimlar')
                ->where('id', $rid)
                ->update(['resim' => $ilkResim]);
            
            $guncellenen++;
        }
        
        $this->info("✅ {$guncellenen} adet paketin resmi güncellendi!");
        
        return 0;
    }
}







