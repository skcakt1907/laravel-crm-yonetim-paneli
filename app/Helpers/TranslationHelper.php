<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Lang;

class TranslationHelper
{
    /**
     * TR mesaj değerlerinden (lang/tr/messages.php) key bulup,
     * aktif dile göre (lang/<locale>/messages.php) çevirisini döndürür.
     * Eşleşme bulunamazsa metni aynen döndürür.
     */
    public static function translate($text, $targetLanguage = null)
    {
        if (!is_string($text) || $text === '') {
            return $text;
        }

        $locale = is_string($targetLanguage) && $targetLanguage !== ''
            ? $targetLanguage
            : (session('locale') ?: app()->getLocale());

        if (!$locale || $locale === 'tr') {
            return $text;
        }

        // Önce category_titles array'ini kontrol et (kategori isimleri için)
        static $categoryTitlesCache = [];
        $cacheKey = $locale . '_' . md5($text);
        
        if (!isset($categoryTitlesCache[$cacheKey])) {
            $trCategoryTitles = Lang::get('messages.category_titles', [], 'tr');
            $targetCategoryTitles = Lang::get('messages.category_titles', [], $locale);
            
            if (is_array($trCategoryTitles) && is_array($targetCategoryTitles)) {
                // Trim yaparak karşılaştır (boşluk sorununu çöz)
                $trimmedText = trim($text);
                
                // Önce direkt key olarak kontrol et
                if (isset($trCategoryTitles[$text]) && isset($targetCategoryTitles[$text])) {
                    $categoryTitlesCache[$cacheKey] = $targetCategoryTitles[$text];
                } else {
                    // Trim'li versiyonunu kontrol et
                    foreach ($trCategoryTitles as $trKey => $trValue) {
                        if (trim($trKey) === $trimmedText || trim($trValue) === $trimmedText) {
                            if (isset($targetCategoryTitles[$trKey])) {
                                $categoryTitlesCache[$cacheKey] = $targetCategoryTitles[$trKey];
                            } else {
                                $categoryTitlesCache[$cacheKey] = null;
                            }
                            break;
                        }
                    }
                }
            }
            
            if (!isset($categoryTitlesCache[$cacheKey])) {
                $categoryTitlesCache[$cacheKey] = null;
            }
        }
        
        if ($categoryTitlesCache[$cacheKey] !== null && $categoryTitlesCache[$cacheKey] !== '') {
            return $categoryTitlesCache[$cacheKey];
        }

        // Eğer category_titles'da bulunamadıysa, normal messages array'ini kontrol et
        static $reverseMap = null;
        if ($reverseMap === null) {
            $reverseMap = [];
            $trMessages = Lang::get('messages', [], 'tr');
            if (is_array($trMessages)) {
                foreach ($trMessages as $key => $value) {
                    if (is_string($value) && $value !== '') {
                        $reverseMap[$value] = $key;
                    }
                }
            }
        }

        $key = $reverseMap[$text] ?? null;
        if (!$key) {
            return $text;
        }

        $translated = Lang::get("messages.$key", [], $locale);
        return is_string($translated) && $translated !== '' ? $translated : $text;
    }

    /**
     * Model bazlı çeviri: önce ceviriler tablosuna bak, yoksa translate() fallback.
     * Model Ceviribilir trait'i kullanıyorsa ceviri() metodu çağrılır.
     */
    public static function translateModel($model, string $alan, ?string $targetLanguage = null): ?string
    {
        if (!$model) return null;

        $locale = $targetLanguage ?: (session('locale') ?: app()->getLocale());
        $orijinal = $model->{$alan} ?? null;

        if (!$locale || $locale === 'tr') {
            return $orijinal;
        }

        if (method_exists($model, 'ceviri')) {
            $deger = $model->ceviri($alan, $locale);
            if ($deger !== null && $deger !== '' && $deger !== $orijinal) {
                return $deger;
            }
        }

        if (is_string($orijinal) && $orijinal !== '') {
            return static::translate($orijinal, $locale);
        }

        return $orijinal;
    }

    /**
     * Raw DB row (DB::table()->get() satırı) için çeviri.
     * Öncelik sırası:
     *   1) ceviriler tablosu (yeni unified sistem)
     *   2) Aynı tablodaki {alan}_{lang} kolonu (eski per-column sistem)
     *   3) lang/generated TR->LANG sözlüğü (legacy)
     *   4) Orijinal TR metin
     */
    public static function translateField(string $table, int $id, string $alan, $orijinal, ?string $targetLanguage = null)
    {
        $locale = $targetLanguage ?: (session('locale') ?: app()->getLocale());
        if (!$locale || $locale === 'tr' || !$id) {
            return $orijinal;
        }

        // 1) ceviriler tablosu (tek istek için cache)
        static $cache = [];
        $key = $table . '|' . $id . '|' . $locale;
        if (!isset($cache[$key])) {
            $cache[$key] = \App\Models\Ceviri::getMany($table, $locale, $id);
        }
        if (!empty($cache[$key][$alan])) {
            return $cache[$key][$alan];
        }

        // 2) {alan}_{lang} kolonu (eski sistem — sayfalar.adi_en gibi)
        static $colCache = [];
        $colKey = $table . '|' . $id;
        if (!isset($colCache[$colKey])) {
            try {
                $colCache[$colKey] = (array) \Illuminate\Support\Facades\DB::table($table)->where('id', $id)->first();
            } catch (\Throwable $e) {
                $colCache[$colKey] = [];
            }
        }
        $colName = $alan . '_' . $locale;
        if (!empty($colCache[$colKey][$colName])) {
            return $colCache[$colKey][$colName];
        }

        // 3) lang/generated sözlüğü
        if (is_string($orijinal) && $orijinal !== '') {
            return static::translate($orijinal, $locale);
        }

        return $orijinal;
    }

    /**
     * HTML içeriği için de çeviri yapılmaz.
     */
    private static function translateHtml(string $html, string $targetLanguage): string
    {
        return $html;
    }
}


