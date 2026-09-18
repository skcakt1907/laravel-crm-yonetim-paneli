<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixBakiye extends Command
{
    protected $signature = 'bakiye:fix {--user_id= : Belirli bir kullanıcı ID\'si} {--all : Tüm 50 TL bakiyeli hesapları 0\'a güncelle}';
    
    protected $description = 'Kullanıcı bakiyelerini düzelt';

    public function handle()
    {
        if ($this->option('user_id')) {
            $userId = $this->option('user_id');
            $uye = DB::table('uyeler')->where('id', $userId)->first();
            
            if (!$uye) {
                $this->error("Kullanıcı bulunamadı!");
                return 1;
            }
            
            $this->info("Kullanıcı: {$uye->email}");
            $this->info("Mevcut Bakiye: {$uye->bakiye} TL");
            
            if ($this->confirm('Bakiyeyi 0 TL\'ye güncellemek istiyor musunuz?', true)) {
                DB::table('uyeler')->where('id', $userId)->update(['bakiye' => 0]);
                $this->info("✓ Bakiye 0 TL'ye güncellendi!");
            }
            
            return 0;
        }
        
        if ($this->option('all')) {
            $count = DB::table('uyeler')->where('bakiye', 50)->count();
            
            if ($count == 0) {
                $this->info("50 TL bakiyeli hesap bulunamadı.");
                return 0;
            }
            
            $this->info("50 TL bakiyeli {$count} hesap bulundu.");
            
            if ($this->confirm("Tüm 50 TL bakiyeli hesapları 0 TL'ye güncellemek istiyor musunuz?", true)) {
                $updated = DB::table('uyeler')->where('bakiye', 50)->update(['bakiye' => 0]);
                $this->info("✓ {$updated} hesap güncellendi!");
            }
            
            return 0;
        }
        
        $this->error("Lütfen --user_id=ID veya --all parametresini kullanın.");
        $this->info("Örnek kullanım:");
        $this->info("  php artisan bakiye:fix --user_id=1");
        $this->info("  php artisan bakiye:fix --all");
        
        return 1;
    }
}


