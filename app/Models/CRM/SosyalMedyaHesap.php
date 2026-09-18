<?php

namespace App\Models\CRM;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SosyalMedyaHesap extends Model
{
    protected $table = 'sosyal_medya_hesaplari';

    /** Kasa bölümleri — hesap hangi gruba ait */
    public const BOLUM_SOSYAL = 'sosyal_medya';
    public const BOLUM_WEB    = 'web_sitesi';

    public const BOLUMLER = [
        self::BOLUM_SOSYAL => 'Sosyal Medya',
        self::BOLUM_WEB    => 'Web Sitesi Giriş Bilgileri',
    ];

    /** Bölüme göre hazır platform seçenekleri (form dropdown'u bunu kullanır) */
    public const PLATFORMLAR = [
        self::BOLUM_SOSYAL => [
            'Instagram' => 'instagram', 'Facebook' => 'facebook', 'TikTok' => 'music',
            'YouTube' => 'youtube', 'LinkedIn' => 'linkedin', 'X (Twitter)' => 'twitter',
            'Google Business' => 'map-pin', 'Gmail' => 'mail', 'Diğer' => 'more-horizontal',
        ],
        self::BOLUM_WEB => [
            'WordPress Admin' => 'layout-dashboard', 'Site Yönetim Paneli' => 'settings',
            'cPanel' => 'server', 'Plesk' => 'server', 'FTP' => 'folder',
            'Hosting Paneli' => 'hard-drive', 'Domain Paneli' => 'globe',
            'Veritabanı' => 'database', 'Diğer' => 'more-horizontal',
        ],
    ];

    public static function bolumAdi(?string $bolum): string
    {
        return self::BOLUMLER[$bolum] ?? self::BOLUMLER[self::BOLUM_SOSYAL];
    }

    protected $fillable = [
        'kayit_id', 'bolum', 'platform', 'kullanici_adi', 'sifre',
        'email', 'link', 'aciklama', 'sira', 'sorumlu_id',
    ];

    /**
     * Şifreler veritabanında ŞİFRELİ saklanır (düz metin DEĞİL). Yazarken
     * otomatik şifrelenir; okurken otomatik çözülür — çağıran kodda değişiklik
     * gerekmez.
     *
     * DÜZ METİNLE UYUMLU OKUMA (04.08.2026): Standart 'encrypted' cast, henüz
     * şifrelenmemiş (düz metin) bir satırı görünce DecryptException fırlatıp
     * sayfayı komple kırıyordu — kasa-sifrele.php çalıştırılmadan önce eklenen
     * her yeni kayıt bu hataya sebep olurdu. Artık çözülemeyen değer olduğu
     * gibi (düz metin) gösterilir; sayfa kırılmaz.
     *
     * UYARI: Çözme anahtarı .env içindeki APP_KEY'dir. APP_KEY değişir veya
     * kaybolursa şifrelenmiş kayıtlar BİR DAHA OKUNAMAZ. .env yedeklenmeli,
     * APP_KEY asla değiştirilmemelidir.
     */
    public function getSifreAttribute(?string $deger): ?string
    {
        if ($deger === null || $deger === '') return $deger;

        try {
            return Crypt::decryptString($deger);
        } catch (DecryptException $e) {
            return $deger; // henüz şifrelenmemiş — düz metin olduğu gibi gösterilir
        }
    }

    public function setSifreAttribute(?string $deger): void
    {
        $this->attributes['sifre'] = $deger === null || $deger === ''
            ? $deger
            : Crypt::encryptString($deger);
    }

    public function kayit()
    {
        return $this->belongsTo(SosyalMedyaKayit::class, 'kayit_id');
    }

    /** Bu platformdan sorumlu sosyal medya uzmanı */
    public function sorumlu()
    {
        return $this->belongsTo(\App\Models\Yonetici::class, 'sorumlu_id');
    }
}
