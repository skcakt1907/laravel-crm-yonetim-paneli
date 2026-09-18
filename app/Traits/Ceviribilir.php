<?php

namespace App\Traits;

use App\Models\Ceviri;

trait Ceviribilir
{
    public function ceviriler()
    {
        return $this->morphMany(Ceviri::class, 'model', 'model_type', 'model_id')
            ->where('model_type', $this->getTable());
    }

    public function getMorphClass()
    {
        return $this->getTable();
    }

    /**
     * Belirli bir alan için aktif dile göre çeviri döner.
     * Çeviri yoksa orijinal (TR) değeri döner.
     */
    public function ceviri(string $alan, ?string $lang = null): ?string
    {
        $lang = $lang ?: app()->getLocale();
        if ($lang === 'tr') {
            return $this->{$alan} ?? null;
        }

        // Memoization
        if (!isset($this->_ceviriCache)) {
            $this->_ceviriCache = [];
        }
        $key = $lang . '|' . $alan;
        if (array_key_exists($key, $this->_ceviriCache)) {
            return $this->_ceviriCache[$key];
        }

        try {
            $row = $this->ceviriler()
                ->where('lang', $lang)
                ->where('alan', $alan)
                ->first();
            $deger = $row?->deger;
        } catch (\Throwable $e) {
            $deger = null;
        }

        $this->_ceviriCache[$key] = $deger ?: ($this->{$alan} ?? null);
        return $this->_ceviriCache[$key];
    }

    /**
     * Tüm çevirileri dile göre array olarak getirir: ['adi'=>'...', 'kisa'=>'...', ...]
     * Admin formunda mevcut değerleri doldurmak için.
     */
    public function ceviriler_array(?string $lang = null): array
    {
        $lang = $lang ?: app()->getLocale();
        if ($lang === 'tr') return [];

        try {
            return $this->ceviriler()
                ->where('lang', $lang)
                ->pluck('deger', 'alan')
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
