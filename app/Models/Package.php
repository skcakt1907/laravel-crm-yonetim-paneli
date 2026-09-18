<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'title_tr',
        'title_en',
        'title_ar',
        'desc_tr',
        'desc_en',
        'desc_ar',
        'is_active',
    ];

    /**
     * Sanal alanlar (localized accessor'lar için)
     */
    protected $appends = [
        'title',
        'description',
    ];

    /**
     * Geçerli locale'e göre başlık döner.
     */
    public function getTitleAttribute(): ?string
    {
        $locale = app()->getLocale() ?? 'tr';

        return $this->getLocalizedField('title', $locale);
    }

    /**
     * Geçerli locale'e göre açıklama döner.
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale() ?? 'tr';

        return $this->getLocalizedField('desc', $locale);
    }

    /**
     * İstenen alanı locale'e göre çözer, gerekirse TR'ye fallback yapar.
     */
    public function getLocalizedField(string $base, ?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale() ?: 'tr';

        $column = "{$base}_{$locale}";

        // Önce istenen dil
        if (! empty($this->{$column})) {
            return $this->{$column};
        }

        // Yoksa TR (master)
        $fallbackColumn = "{$base}_tr";

        return $this->{$fallbackColumn} ?? null;
    }
}





















