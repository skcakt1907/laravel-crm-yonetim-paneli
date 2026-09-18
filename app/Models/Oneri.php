<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Öneri zinciri — kim kimi önerdi.
 *
 *  bayi_onerisi      → Öner-Kazan. Önerilen bayinin komisyonunun %50'si öneren bayiye.
 *  musteri_referansi → Bağlanan müşterinin aldığı her şeyin %10'u öneren kişiye.
 *
 * Kazanç hesabı bu modelde DEĞİL, kazanç motorunda yapılır; burada yalnızca
 * ilişki, oran ve toplam tutulur.
 */
class Oneri extends Model
{
    protected $table = 'oneriler';

    public const TIP_BAYI     = 'bayi_onerisi';
    public const TIP_MUSTERI  = 'musteri_referansi';

    /** Tip başına varsayılan oranlar */
    public const ORANLAR = [
        self::TIP_BAYI    => 50.00,
        self::TIP_MUSTERI => 10.00,
    ];

    protected $fillable = [
        'oneren_tip', 'oneren_id',
        'onerilen_tip', 'onerilen_id',
        'tip', 'oran', 'suresiz', 'gecerlilik_bitis',
        'durum', 'kaynak', 'aciklama', 'olusturan_id',
    ];

    // toplam_kazanc bilerek fillable DEĞİL — yalnızca kazancEkle() ile artar.

    protected $casts = [
        'oran'             => 'decimal:2',
        'toplam_kazanc'    => 'decimal:2',
        'suresiz'          => 'boolean',
        'gecerlilik_bitis' => 'date',
    ];

    /* ───────────── Sorgu yardımcıları ───────────── */

    public function scopeAktif($q)
    {
        return $q->where('durum', 'aktif');
    }

    /** Bu kişiyi kim önerdi? (yoksa null) */
    public static function onereniniBul(string $onerilenTip, int $onerilenId, string $tip): ?self
    {
        return static::where('onerilen_tip', $onerilenTip)
            ->where('onerilen_id', $onerilenId)
            ->where('tip', $tip)
            ->aktif()
            ->first();
    }

    /** Bu öneri şu an kazanç üretiyor mu? (süre dolmuş olabilir) */
    public function gecerliMi(): bool
    {
        if ($this->durum !== 'aktif') return false;
        if ($this->suresiz) return true;

        return $this->gecerlilik_bitis === null
            || $this->gecerlilik_bitis->endOfDay()->greaterThanOrEqualTo(Carbon::now());
    }

    /**
     * Öneri bağı kurar. Aynı kişiye ikinci öneren atanamaz,
     * kişi kendini öneremez, karşılıklı (A→B ve B→A) zincir kurulamaz.
     *
     * @return array{0: ?self, 1: string}  [oneri, mesaj]
     */
    public static function bagKur(
        string $onerenTip, int $onerenId,
        string $onerilenTip, int $onerilenId,
        string $tip, ?float $oran = null,
        string $kaynak = 'admin', ?int $olusturanId = null
    ): array {
        if ($onerenTip === $onerilenTip && $onerenId === $onerilenId) {
            return [null, 'Bir kişi kendini öneremez.'];
        }

        $mevcut = static::where('onerilen_tip', $onerilenTip)
            ->where('onerilen_id', $onerilenId)
            ->where('tip', $tip)
            ->first();
        if ($mevcut) {
            return [null, 'Bu kişinin zaten bir önereni var (#' . $mevcut->id . ').'];
        }

        // Karşılıklı zincir engeli: önerilen kişi, önereni önermiş olmasın
        $ters = static::where('onerilen_tip', $onerenTip)
            ->where('onerilen_id', $onerenId)
            ->where('oneren_tip', $onerilenTip)
            ->where('oneren_id', $onerilenId)
            ->exists();
        if ($ters) {
            return [null, 'Karşılıklı öneri kurulamaz (bu kişi zaten sizi önermiş).'];
        }

        $oneri = static::create([
            'oneren_tip'   => $onerenTip,
            'oneren_id'    => $onerenId,
            'onerilen_tip' => $onerilenTip,
            'onerilen_id'  => $onerilenId,
            'tip'          => $tip,
            'oran'         => $oran ?? (self::ORANLAR[$tip] ?? 0),
            'suresiz'      => true,
            'durum'        => 'aktif',
            'kaynak'       => $kaynak,
            'olusturan_id' => $olusturanId,
        ]);

        return [$oneri, 'Öneri bağı kuruldu.'];
    }

    /** Bu öneriden kazanılan tutarı toplama ekler. */
    public function kazancEkle(float $tutar): void
    {
        $this->increment('toplam_kazanc', round($tutar, 2));
    }

    /* ───────────── Görüntüleme ───────────── */

    public function getTipAdiAttribute(): string
    {
        return $this->tip === self::TIP_BAYI ? 'Öner-Kazan (bayi)' : 'Müşteri referansı';
    }

    /** Öneren kişinin adı (bayi ya da üye) */
    public function getOnerenAdiAttribute(): string
    {
        if ($this->oneren_tip === 'bayi') {
            $b = Bayi::find($this->oneren_id);
            return $b->firma_adi ?? ('Bayi #' . $this->oneren_id);
        }
        $u = \Illuminate\Support\Facades\DB::table('uyeler')->find($this->oneren_id);
        return $u ? trim(($u->ad ?? '') . ' ' . ($u->soyad ?? '')) : ('Üye #' . $this->oneren_id);
    }
}
