<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_customers')) return;

        Schema::table('crm_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_customers', 'gsm'))            $table->string('gsm', 50)->nullable()->after('telefon');
            if (!Schema::hasColumn('crm_customers', 'web_sitesi'))     $table->string('web_sitesi', 190)->nullable()->after('gsm');
            if (!Schema::hasColumn('crm_customers', 'firma_tipi'))     $table->string('firma_tipi', 50)->nullable()->after('unvan'); // sahis, limited, anonim
            if (!Schema::hasColumn('crm_customers', 'vergi_dairesi'))  $table->string('vergi_dairesi', 100)->nullable();
            if (!Schema::hasColumn('crm_customers', 'vergi_no'))       $table->string('vergi_no', 50)->nullable();
            if (!Schema::hasColumn('crm_customers', 'tc_kimlik'))      $table->string('tc_kimlik', 20)->nullable();
            if (!Schema::hasColumn('crm_customers', 'il'))             $table->string('il', 80)->nullable();
            if (!Schema::hasColumn('crm_customers', 'ilce'))           $table->string('ilce', 80)->nullable();
            if (!Schema::hasColumn('crm_customers', 'bayi_mi'))        $table->boolean('bayi_mi')->default(false);
            if (!Schema::hasColumn('crm_customers', 'bayi_tarihi'))    $table->date('bayi_tarihi')->nullable();
            if (!Schema::hasColumn('crm_customers', 'bayi_komisyon'))  $table->decimal('bayi_komisyon', 5, 2)->nullable(); // %
            if (!Schema::hasColumn('crm_customers', 'dogum_tarihi'))   $table->date('dogum_tarihi')->nullable();
            if (!Schema::hasColumn('crm_customers', 'not_icerik'))     $table->text('not_icerik')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_customers')) return;
        Schema::table('crm_customers', function (Blueprint $table) {
            $cols = ['gsm','web_sitesi','firma_tipi','vergi_dairesi','vergi_no','tc_kimlik','il','ilce','bayi_mi','bayi_tarihi','bayi_komisyon','dogum_tarihi','not_icerik'];
            foreach ($cols as $c) if (Schema::hasColumn('crm_customers', $c)) $table->dropColumn($c);
        });
    }
};
