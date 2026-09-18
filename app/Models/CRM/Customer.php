<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'crm_customers';

    protected $fillable = [
        'adi', 'unvan', 'firma_tipi',
        'email', 'telefon', 'gsm', 'web_sitesi',
        'sektor', 'kaynak', 'durum', 'sorumlu_id',
        'adres', 'il', 'ilce', 'mahalle',
        'vergi_dairesi', 'vergi_no', 'tc_kimlik',
        'bayi_mi', 'bayi_tarihi', 'bayi_komisyon',
        'dogum_tarihi', 'not_icerik',
        'etiketler', 'bakiye',
    ];

    protected $casts = [
        'etiketler'    => 'array',
        'bakiye'       => 'decimal:2',
        'bayi_mi'      => 'boolean',
        'bayi_tarihi'  => 'date',
        'dogum_tarihi' => 'date',
        'bayi_komisyon'=> 'decimal:2',
    ];

    /**
     * Müşteri durumu: pasif / potansiyel / aktif
     */
  public const DURUMLAR = [
      'pasif'      => ['label' => 'Pasif',      'ikon' => '⏸️', 'renk' => '#6b7280', 'class' => 'badge-secondary'],
      'potansiyel' => ['label' => 'Potansiyel', 'ikon' => '✨', 'renk' => '#f59e0b', 'class' => 'badge-warning'],
      'aktif'      => ['label' => 'Aktif',      'ikon' => '✅', 'renk' => '#10b981', 'class' => 'badge-success'],
  ];
    /**
     * Eski (sicak/ilimli/soguk) ve yeni değerleri normalize edip bilgi döndürür.
     * NOT: 'dn_unity' artık bir DURUM değil, ayrı bir LİSTE'dir (crm_listeler "DN Unity").
     * Kalan eski dn_unity durum değerleri Aktif olarak gösterilir.
     */
    public static function durumBilgi(?string $durum): array
    {
        $legacy = ['sicak' => 'aktif', 'soguk' => 'pasif', 'ilimli' => 'potansiyel', 'dn_unity' => 'aktif'];
        $key = $legacy[$durum] ?? $durum ?? 'pasif';
        if (!array_key_exists($key, self::DURUMLAR)) {
            $key = 'pasif';
        }

        return self::DURUMLAR[$key] + ['key' => $key];
    }

    /**
     * Sıralı bir sonraki durum (pasif→potansiyel→aktif→pasif).
     */
    public static function durumSonraki(?string $durum): string
    {
        $keys = array_keys(self::DURUMLAR);
        $cur = self::durumBilgi($durum)['key'];
        $i = array_search($cur, $keys, true);

        return $keys[($i + 1) % count($keys)];
    }

    public function bakiyeHareketleri()
    {
        return $this->hasMany(CustomerBakiyeHareketi::class, 'musteri_id')->latest();
    }

    public function sorumlu()
    {
        return $this->belongsTo(Yonetici::class, 'sorumlu_id');
    }

    /** Data Center listeleri (Çoka Çok) — bir müşteri birden çok listede olabilir */
    public function listeler()
    {
        return $this->belongsToMany(CrmListe::class, 'crm_customer_liste', 'customer_id', 'liste_id')
            ->withPivot('liste_durum', 'kaynak')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        /*
         * BİRLEŞTİRİLMİŞ KAYITLARI GİZLE (31.07.2026)
         *
         * Mükerrer müşteri kartları birleştirildi (33 grup / 34 kayıt).
         * Kaybeden kayıt SİLİNMEDİ — `birlesen_id` ile hangi karta birleştiği
         * yazıldı. Böylece geri dönülebiliyor ve eski id'yle gelen link
         * hâlâ doğru kartı bulabiliyor.
         *
         * Ama listelerde/dropdown'larda görünmemeliler, yoksa "Yeliz" araması
         * yine 3 sonuç döner. Bu global kural tüm Customer sorgularından
         * onları çıkarır.
         *
         * Birleşmişleri de görmek gerekirse:
         *   Customer::withoutGlobalScope('birlesmemis')->...
         */
        static::addGlobalScope('birlesmemis', function ($sorgu) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('crm_customers', 'birlesen_id')) {
                $sorgu->whereNull('crm_customers.birlesen_id');
            }
        });

        /** Müşteri silinince liste ilişkilerini de kopar (FK olmasa da garanti) */
        static::deleting(function ($customer) {
            $customer->listeler()->detach();
        });
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'musteri_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'musteri_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'musteri_id');
    }
}