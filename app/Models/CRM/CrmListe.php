<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CrmListe extends Model
{
    protected $table = 'crm_listeler';

    protected $fillable = [
        'ad', 'slug', 'aciklama', 'renk', 'sira', 'durum', 'kurallar', 'olusturan_id',
    ];

    protected $casts = [
        'kurallar' => 'array',
        'durum'    => 'boolean',
    ];

    /** Bu listedeki müşteriler (Çoka Çok) */
    public function musteriler()
    {
        return $this->belongsToMany(Customer::class, 'crm_customer_liste', 'liste_id', 'customer_id')
            ->withPivot('liste_durum', 'kaynak')
            ->withTimestamps();
    }

    /** Liste oluşturulurken otomatik slug */
    protected static function booted(): void
    {
        static::saving(function ($liste) {
            if (empty($liste->slug) && !empty($liste->ad)) {
                $liste->slug = Str::slug($liste->ad);
            }
        });

        // Liste silinince SADECE ilişki kopar, müşteriler durur (FK olmasa da garanti)
        static::deleting(function ($liste) {
            $liste->musteriler()->detach();
        });
    }
}
