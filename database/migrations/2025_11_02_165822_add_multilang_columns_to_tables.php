<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Geçici olarak strict mode'u kapat
        DB::statement("SET SESSION sql_mode = ''");
        
        // SLIDER tablosu - çoklu dil desteği
        if (Schema::hasTable('slider')) {
            Schema::table('slider', function (Blueprint $table) {
                if (!Schema::hasColumn('slider', 'adi_en')) {
                    $table->string('adi_en', 255)->nullable()->after('adi');
                }
                if (!Schema::hasColumn('slider', 'adi_ar')) {
                    $table->string('adi_ar', 255)->nullable()->after('adi_en');
                }
                if (!Schema::hasColumn('slider', 'aciklama_en')) {
                    $table->text('aciklama_en')->nullable()->after('aciklama');
                }
                if (!Schema::hasColumn('slider', 'aciklama_ar')) {
                    $table->text('aciklama_ar')->nullable()->after('aciklama_en');
                }
            });
            
            // Mevcut verileri kopyala (TR -> EN, AR)
            DB::statement("UPDATE slider SET adi_en = adi, adi_ar = adi WHERE adi_en IS NULL");
            DB::statement("UPDATE slider SET aciklama_en = aciklama, aciklama_ar = aciklama WHERE aciklama_en IS NULL");
        }
        
        // YAZILIMLAR tablosu - çoklu dil desteği
        if (Schema::hasTable('yazilimlar')) {
            Schema::table('yazilimlar', function (Blueprint $table) {
                if (!Schema::hasColumn('yazilimlar', 'adi_en')) {
                    $table->string('adi_en', 255)->nullable()->after('adi');
                }
                if (!Schema::hasColumn('yazilimlar', 'adi_ar')) {
                    $table->string('adi_ar', 255)->nullable()->after('adi_en');
                }
                if (!Schema::hasColumn('yazilimlar', 'aciklama_en')) {
                    $table->text('aciklama_en')->nullable()->after('aciklama');
                }
                if (!Schema::hasColumn('yazilimlar', 'aciklama_ar')) {
                    $table->text('aciklama_ar')->nullable()->after('aciklama_en');
                }
            });
            
            DB::statement("UPDATE yazilimlar SET adi_en = adi, adi_ar = adi WHERE adi_en IS NULL");
            DB::statement("UPDATE yazilimlar SET aciklama_en = aciklama, aciklama_ar = aciklama WHERE aciklama_en IS NULL");
        }
        
        // BLOG tablosu - çoklu dil desteği
        if (Schema::hasTable('blog')) {
            Schema::table('blog', function (Blueprint $table) {
                if (!Schema::hasColumn('blog', 'adi_en')) {
                    $table->string('adi_en', 255)->nullable()->after('adi');
                }
                if (!Schema::hasColumn('blog', 'adi_ar')) {
                    $table->string('adi_ar', 255)->nullable()->after('adi_en');
                }
                if (!Schema::hasColumn('blog', 'aciklama_en')) {
                    $table->longText('aciklama_en')->nullable()->after('aciklama');
                }
                if (!Schema::hasColumn('blog', 'aciklama_ar')) {
                    $table->longText('aciklama_ar')->nullable()->after('aciklama_en');
                }
            });
            
            DB::statement("UPDATE blog SET adi_en = adi, adi_ar = adi WHERE adi_en IS NULL");
            DB::statement("UPDATE blog SET aciklama_en = aciklama, aciklama_ar = aciklama WHERE aciklama_en IS NULL");
        }
        
        // REFERANSLAR tablosu - çoklu dil desteği
        if (Schema::hasTable('referanslar')) {
            Schema::table('referanslar', function (Blueprint $table) {
                if (!Schema::hasColumn('referanslar', 'adi_en')) {
                    $table->string('adi_en', 255)->nullable()->after('adi');
                }
                if (!Schema::hasColumn('referanslar', 'adi_ar')) {
                    $table->string('adi_ar', 255)->nullable()->after('adi_en');
                }
                if (!Schema::hasColumn('referanslar', 'kisa_en')) {
                    $table->string('kisa_en', 255)->nullable()->after('kisa');
                }
                if (!Schema::hasColumn('referanslar', 'kisa_ar')) {
                    $table->string('kisa_ar', 255)->nullable()->after('kisa_en');
                }
                if (!Schema::hasColumn('referanslar', 'aciklama_en')) {
                    $table->text('aciklama_en')->nullable()->after('aciklama');
                }
                if (!Schema::hasColumn('referanslar', 'aciklama_ar')) {
                    $table->text('aciklama_ar')->nullable()->after('aciklama_en');
                }
            });
            
            DB::statement("UPDATE referanslar SET adi_en = adi, adi_ar = adi WHERE adi_en IS NULL");
            DB::statement("UPDATE referanslar SET kisa_en = kisa, kisa_ar = kisa WHERE kisa_en IS NULL");
            DB::statement("UPDATE referanslar SET aciklama_en = aciklama, aciklama_ar = aciklama WHERE aciklama_en IS NULL");
        }
        
        // SAYFALAR tablosu - çoklu dil desteği
        if (Schema::hasTable('sayfalar')) {
            Schema::table('sayfalar', function (Blueprint $table) {
                if (!Schema::hasColumn('sayfalar', 'adi_en')) {
                    $table->string('adi_en', 255)->nullable()->after('adi');
                }
                if (!Schema::hasColumn('sayfalar', 'adi_ar')) {
                    $table->string('adi_ar', 255)->nullable()->after('adi_en');
                }
                if (!Schema::hasColumn('sayfalar', 'aciklama_en')) {
                    $table->longText('aciklama_en')->nullable()->after('aciklama');
                }
                if (!Schema::hasColumn('sayfalar', 'aciklama_ar')) {
                    $table->longText('aciklama_ar')->nullable()->after('aciklama_en');
                }
                if (!Schema::hasColumn('sayfalar', 'kisa_en')) {
                    $table->text('kisa_en')->nullable()->after('kisa');
                }
                if (!Schema::hasColumn('sayfalar', 'kisa_ar')) {
                    $table->text('kisa_ar')->nullable()->after('kisa_en');
                }
            });
            
            DB::statement("UPDATE sayfalar SET adi_en = adi, adi_ar = adi WHERE adi_en IS NULL");
            DB::statement("UPDATE sayfalar SET aciklama_en = aciklama, aciklama_ar = aciklama WHERE aciklama_en IS NULL");
            DB::statement("UPDATE sayfalar SET kisa_en = kisa, kisa_ar = kisa WHERE kisa_en IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma işlemi - kolonları sil
        if (Schema::hasTable('slider')) {
            Schema::table('slider', function (Blueprint $table) {
                $table->dropColumn(['adi_en', 'adi_ar', 'aciklama_en', 'aciklama_ar']);
            });
        }
        
        if (Schema::hasTable('yazilimlar')) {
            Schema::table('yazilimlar', function (Blueprint $table) {
                $table->dropColumn(['adi_en', 'adi_ar', 'aciklama_en', 'aciklama_ar']);
            });
        }
        
        if (Schema::hasTable('blog')) {
            Schema::table('blog', function (Blueprint $table) {
                $table->dropColumn(['adi_en', 'adi_ar', 'aciklama_en', 'aciklama_ar']);
            });
        }
        
        if (Schema::hasTable('referanslar')) {
            Schema::table('referanslar', function (Blueprint $table) {
                $table->dropColumn(['adi_en', 'adi_ar', 'kisa_en', 'kisa_ar', 'aciklama_en', 'aciklama_ar']);
            });
        }
        
        if (Schema::hasTable('sayfalar')) {
            Schema::table('sayfalar', function (Blueprint $table) {
                $table->dropColumn(['adi_en', 'adi_ar', 'aciklama_en', 'aciklama_ar', 'kisa_en', 'kisa_ar']);
            });
        }
    }
};
