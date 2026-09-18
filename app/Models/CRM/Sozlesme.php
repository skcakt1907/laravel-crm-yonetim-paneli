<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;

class Sozlesme extends Model
{
    protected $table = 'crm_sozlesmeler';

    protected $fillable = [
        'kategori_id', 'musteri_id', 'sozlesme_no', 'baslik', 'taraf_adi',
        'tutar', 'icerik', 'durum', 'tarih', 'olusturan_id',
        'baslangic_tarihi', 'bitis_tarihi', 'bitis_bildirim_at',
        'uye_id', 'dosya', 'dil', 'kaynak', 'ana_sozlesme_id',
    ];

    protected $casts = [
        'tutar' => 'decimal:2',
        'tarih' => 'date',
        'baslangic_tarihi'  => 'date',
        'bitis_tarihi'      => 'date',
        'bitis_bildirim_at' => 'datetime',
    ];

    /** Bitişine kaç gün kaldı? (bitiş yoksa null, süresi geçmişse negatif) */
    public function getKalanGunAttribute(): ?int
    {
        if (!$this->bitis_tarihi) return null;

        return (int) now()->startOfDay()->diffInDays($this->bitis_tarihi->startOfDay(), false);
    }

    public function kategori()
    {
        return $this->belongsTo(SozlesmeKategori::class, 'kategori_id');
    }

    public function musteri()
    {
        return $this->belongsTo(Customer::class, 'musteri_id');
    }

    /* ═══ ANA / EK SÖZLEŞME BAĞI (tek seviye) ═══ */

    /** Bu sözleşme bir EK ise, bağlı olduğu ANA sözleşme */
    public function anaSozlesme()
    {
        return $this->belongsTo(self::class, 'ana_sozlesme_id');
    }

    /** Bu sözleşme ANA ise, ona bağlı EK sözleşmeler */
    public function ekSozlesmeler()
    {
        return $this->hasMany(self::class, 'ana_sozlesme_id')->orderBy('tarih')->orderBy('id');
    }

    /** Bu bir ek sözleşme mi? */
    public function getEkMiAttribute(): bool
    {
        return !empty($this->ana_sozlesme_id);
    }
}
