<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UyeBildirim extends Model
{
    protected $table = 'uye_bildirimler';

    protected $fillable = [
        'uye_id', 'baslik', 'mesaj', 'tip', 'link', 'ikon',
        'okundu', 'okundu_tarih', 'gonderen_id',
    ];

    protected $casts = [
        'okundu'       => 'boolean',
        'okundu_tarih' => 'datetime',
    ];

    public function uye()
    {
        return $this->belongsTo(Uye::class, 'uye_id');
    }

    public static function forUye(int $uyeId)
    {
        return static::where(function ($q) use ($uyeId) {
            $q->where('uye_id', $uyeId)
              ->orWhereNull('uye_id');
        });
    }

    public static function okunmamisSayisi(int $uyeId): int
    {
        return static::forUye($uyeId)->where('okundu', false)->count();
    }

    public static function gonder(
        ?int $uyeId,
        string $baslik,
        string $mesaj,
        string $tip = 'info',
        ?string $link = null,
        ?string $ikon = null
    ): static {
        return static::create([
            'uye_id'      => $uyeId,
            'baslik'      => $baslik,
            'mesaj'       => $mesaj,
            'tip'         => $tip,
            'link'        => $link,
            'ikon'        => $ikon,
            'gonderen_id' => session('admin_id'),
        ]);
    }

    public static function tumUyelereGonder(
        string $baslik,
        string $mesaj,
        string $tip = 'info',
        ?string $link = null,
        ?string $ikon = null
    ): static {
        return static::gonder(null, $baslik, $mesaj, $tip, $link, $ikon);
    }
}
