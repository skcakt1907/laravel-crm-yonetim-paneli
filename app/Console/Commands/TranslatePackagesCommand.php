<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Services\GoogleCloudTranslationService;

#[AsCommand(name: 'paketler:translate', description: 'Yazilimlar (web paketleri) tablosundaki TR içerikleri otomatik olarak EN ve AR dillerine çevirir.')]
class TranslatePackagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paketler:translate {--limit= : Sadece belirtilen adet kadar kaydı çevir}';

    /**
     * Execute the console command.
     */
    public function handle(GoogleCloudTranslationService $translator): int
    {
        if (!DB::getSchemaBuilder()->hasTable('yazilimlar')) {
            $this->error('yazilimlar tablosu bulunamadı.');
            return self::FAILURE;
        }

        $limit = $this->option('limit');

        // Çevrilecek alanlar
        $fields = [
            'adi',
            'kisa',
            'ozellik',
            'talimat',
            'icerik',
        ];

        $processed = 0;

        DB::table('yazilimlar')
            ->orderBy('id')
            ->when($limit, fn ($query) => $query->limit((int) $limit))
            ->chunkById(25, function ($paketler) use ($fields, $translator, &$processed) {
                foreach ($paketler as $paket) {
                    $updates = [];

                    foreach ($fields as $field) {
                        $value = $paket->{$field} ?? null;

                        if (!$value) {
                            continue;
                        }

                        $enField = "{$field}_en";
                        $arField = "{$field}_ar";

                        // EN
                        if (empty($paket->{$enField}) || $paket->{$enField} === $value) {
                            try {
                                $updates[$enField] = $translator->translate($value, 'en', 'tr');
                            } catch (\Throwable $e) {
                                $this->warn("ID {$paket->id} için {$field} EN çevirisi başarısız: {$e->getMessage()}");
                            }
                        }

                        // AR
                        if (empty($paket->{$arField}) || $paket->{$arField} === $value) {
                            try {
                                $updates[$arField] = $translator->translate($value, 'ar', 'tr');
                            } catch (\Throwable $e) {
                                $this->warn("ID {$paket->id} için {$field} AR çevirisi başarısız: {$e->getMessage()}");
                            }
                        }
                    }

                    if (!empty($updates)) {
                        DB::table('yazilimlar')->where('id', $paket->id)->update($updates);
                        $processed++;
                        $this->info("ID {$paket->id} güncellendi.");
                    }
                }
            });

        $this->info("İşlem tamamlandı. Güncellenen kayıt sayısı: {$processed}");

        return self::SUCCESS;
    }
}

