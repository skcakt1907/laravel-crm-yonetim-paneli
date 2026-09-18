<?php

namespace App\Services;

use App\Helpers\TranslationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutoTranslateContentService
{
    /**
     * Hedef diller ve locale eşlemesi.
     */
    protected static array $languageMap = [
        2 => 'en',
        3 => 'ar',
    ];

    /**
     * Türkçe olarak eklenen kayıtlar için diğer dillere otomatik çeviri kayıtları oluşturur.
     *
     * @param  string       $table         Çalışılacak tablo adı.
     * @param  int          $recordId      Türkçe kaydın ID değeri.
     * @param  array        $textColumns   Çevrilecek kolonlar.
     * @param  string|null  $slugColumn    Benzersiz slug kolonu (örn: seo).
     * @param  string|null  $titleColumn   Slug üretirken kullanılacak başlık kolonu.
     * @return void
     */
    public static function ensureTranslations(
        string $table,
        int $recordId,
        array $textColumns,
        ?string $slugColumn = null,
        ?string $titleColumn = null
    ): void {
        if (!Schema::hasTable($table)) {
            return;
        }

        $base = DB::table($table)->where('id', $recordId)->first();

        if (!$base) {
            return;
        }

        if (!Schema::hasColumn($table, 'dil')) {
            return;
        }

        $baseLanguage = (int) ($base->dil ?? 1);

        // Sadece Türkçe kayıtlar için otomatik çeviri oluşturuyoruz.
        if ($baseLanguage !== 1) {
            return;
        }

        $baseData = (array) $base;

        foreach (self::$languageMap as $languageId => $locale) {
            $translatedData = $baseData;
            unset($translatedData['id']);

            $translatedData['dil'] = $languageId;

            if (Schema::hasColumn($table, 'bagli_id')) {
                $translatedData['bagli_id'] = $baseData['bagli_id'] ?? $recordId;
            } elseif (Schema::hasColumn($table, 'orijinal_id')) {
                $translatedData['orijinal_id'] = $baseData['orijinal_id'] ?? $recordId;
            }

            foreach ($textColumns as $column) {
                if (array_key_exists($column, $translatedData)) {
                    $translatedData[$column] = TranslationHelper::translate($baseData[$column] ?? '', $locale);
                }
            }

            if ($slugColumn && array_key_exists($slugColumn, $translatedData)) {
                $translatedData[$slugColumn] = self::buildUniqueSlug(
                    $table,
                    $slugColumn,
                    $baseData,
                    $locale,
                    $titleColumn,
                    $languageId
                );
            }

            if (Schema::hasColumn($table, 'created_at')) {
                $translatedData['created_at'] = now();
            }

            if (Schema::hasColumn($table, 'updated_at')) {
                $translatedData['updated_at'] = now();
            }

            if (!self::shouldInsert($table, $translatedData, $languageId, $slugColumn, $recordId)) {
                continue;
            }

            DB::table($table)->insert($translatedData);
        }
    }

    /**
     * Slug değerini hedef dile göre üretir ve benzersiz hale getirir.
     */
    protected static function buildUniqueSlug(
        string $table,
        string $column,
        array $baseData,
        string $locale,
        ?string $titleColumn,
        int $languageId
    ): string {
        $source = $baseData[$column] ?? null;

        if ($titleColumn && !empty($baseData[$titleColumn])) {
            $source = TranslationHelper::translate($baseData[$titleColumn], $locale);
        }

        if (empty($source)) {
            $source = uniqid('item-');
        }

        $slug = Str::slug($source);

        if (Schema::hasColumn($table, 'dil')) {
            $slug = $slug . '-' . $locale;
        }

        $originalSlug = $slug;
        $counter = 1;

        while (DB::table($table)->where($column, $slug)->where('dil', $languageId)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Aynı kayıt zaten varsa tekrar eklemeyi engeller.
     */
    protected static function shouldInsert(
        string $table,
        array $translatedData,
        int $languageId,
        ?string $slugColumn,
        int $originalId
    ): bool {
        $query = DB::table($table)->where('dil', $languageId);

        if ($slugColumn && isset($translatedData[$slugColumn])) {
            $query->where($slugColumn, $translatedData[$slugColumn]);
        } elseif (Schema::hasColumn($table, 'bagli_id')) {
            $query->where('bagli_id', $originalId);
        } elseif (Schema::hasColumn($table, 'orijinal_id')) {
            $query->where('orijinal_id', $originalId);
        } else {
            // Benzersiz kontrol yapamıyorsak doğrudan eklemeyi deniyoruz.
            return true;
        }

        return !$query->exists();
    }
}



