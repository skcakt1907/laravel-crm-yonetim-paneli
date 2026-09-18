<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class Ceviri extends Model
{
    protected $table = 'ceviriler';

    protected $fillable = ['model_type', 'model_id', 'lang', 'alan', 'deger'];

    public function model()
    {
        return $this->morphTo();
    }

    /**
     * model_type'ı normalize et: Eloquent model -> tablo adı, string -> aynen kullan.
     * Tablo adı kullanarak Eloquent ve raw DB::table sorguları aynı kayıtları paylaşır.
     */
    protected static function tipCoz($model): string
    {
        if (is_object($model) && $model instanceof Model) {
            return $model->getTable();
        }
        return (string) $model;
    }

    protected static function idCoz($model, ?int $id = null): int
    {
        if (is_object($model) && $model instanceof Model) {
            return (int) $model->getKey();
        }
        return (int) $id;
    }

    /**
     * Tek alan için çeviri al. Bulamazsa null.
     * Kullanım:
     *   Ceviri::get($yazilim, 'en', 'adi')        // Eloquent model
     *   Ceviri::get('yazilimlar', 'en', 'adi', 5) // raw table+id
     */
    public static function get($model, string $lang, string $alan, ?int $id = null): ?string
    {
        try {
            $row = static::where('model_type', static::tipCoz($model))
                ->where('model_id', static::idCoz($model, $id))
                ->where('lang', $lang)
                ->where('alan', $alan)
                ->first();
            return $row?->deger;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Birden çok alan için tek sorguda dile göre çevirileri getir.
     * Dönen: ['adi'=>'...', 'kisa'=>'...']
     */
    public static function getMany($model, string $lang, ?int $id = null): array
    {
        try {
            return static::where('model_type', static::tipCoz($model))
                ->where('model_id', static::idCoz($model, $id))
                ->where('lang', $lang)
                ->pluck('deger', 'alan')
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Çevirileri kaydet.
     * $ceviriler = ['en' => ['adi'=>'...', 'kisa'=>'...'], 'ar' => [...]]
     * Boş alanların çevirisi silinir.
     */
    public static function sync($model, array $ceviriler, ?int $id = null): void
    {
        if (!Schema::hasTable('ceviriler')) return;

        $modelType = static::tipCoz($model);
        $modelId = static::idCoz($model, $id);
        if (!$modelType || !$modelId) return;

        foreach ($ceviriler as $lang => $alanlar) {
            if (!is_array($alanlar)) continue;
            // TR'yi yedekleme — bu ana tabloda saklı
            if ($lang === 'tr') continue;
            foreach ($alanlar as $alan => $deger) {
                $deger = is_string($deger) ? trim($deger) : $deger;
                if ($deger === null || $deger === '') {
                    static::where('model_type', $modelType)
                        ->where('model_id', $modelId)
                        ->where('lang', $lang)
                        ->where('alan', $alan)
                        ->delete();
                } else {
                    static::updateOrCreate(
                        ['model_type' => $modelType, 'model_id' => $modelId, 'lang' => $lang, 'alan' => $alan],
                        ['deger' => $deger]
                    );
                }
            }
        }
    }

    /**
     * Bir entity için tüm dillerdeki tüm alanları getir (admin form için).
     * Dönen: ['en' => ['adi'=>'...', ...], 'ar' => [...]]
     */
    public static function getAllForForm($model, ?int $id = null): array
    {
        try {
            $rows = static::where('model_type', static::tipCoz($model))
                ->where('model_id', static::idCoz($model, $id))
                ->get();
            $out = [];
            foreach ($rows as $r) {
                $out[$r->lang][$r->alan] = $r->deger;
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
