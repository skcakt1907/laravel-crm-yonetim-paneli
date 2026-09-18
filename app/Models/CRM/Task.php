<?php

namespace App\Models\CRM;

use App\Models\Yonetici;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'musteri_id',
        'firsat_id',
        'atanan_id',
        'olusturan_id',
        'olusturan_adi',
        'kanban_card_id',
        'konu',
        'tip',
        'departman',
        'durum',
        'oncelik',
        'son_tarih',
        'tamamlandi_at',
        'aciklama',
    ];

    protected $casts = [
        'son_tarih' => 'datetime',
        'tamamlandi_at' => 'datetime',
    ];

    /**
     * Departman seçenekleri (key => label).
     */
    public static function departmanlar(): array
    {
        return [
            'sosyal_medya'       => '📱 Sosyal Medya',
            'dijital_pazarlama'  => '📈 Dijital Pazarlama',
            'grafik_tasarim'     => '🎨 Grafik Tasarımı',
            'web_yazilim'        => '💻 Web Yazılım',
            'produksiyon'        => '🎬 Prodüksiyon',
            'finans'             => '💰 Finans',
            'sirket_genel'       => '🏢 Şirket Genel',
            'marka_danismanligi' => '🧭 Marka Danışmanlığı',
            'musteri_iliskileri' => '🤝 Müşteri İlişkileri',
            'influ_marketing'    => '📢 Influencer / Marketing',
            'basin_yayin'        => '📰 Basın / Yayın',
            'duty'               => '🗂️ Duty',
            'tatilim_sensin'     => '🌴 Tatilim Sensin',
        ];
    }

    /**
     * Bir departman key'inin okunabilir etiketi.
     */
    public static function departmanLabel(?string $key): string
    {
        if (empty($key)) {
            return '—';
        }

        return self::departmanlar()[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public function musteri()
    {
        return $this->belongsTo(Customer::class, 'musteri_id');
    }

    public function firsat()
    {
        return $this->belongsTo(Opportunity::class, 'firsat_id');
    }

    public function atanan()
    {
        return $this->belongsTo(Yonetici::class, 'atanan_id');
    }

    /**
     * Goreve atanan TUM kisiler (coklu atama).
     *
     * atanan()  -> birincil atanan (crm_tasks.atanan_id), geriye donuk uyumluluk
     * atananlar() -> hepsi, birincil dahil (crm_task_members)
     */
    public function atananlar()
    {
        return $this->belongsToMany(
            \App\Models\Yonetici::class,
            'crm_task_members',
            'task_id',
            'yonetici_id'
        )->withTimestamps();
    }

    public function olusturan()
    {
        return $this->belongsTo(Yonetici::class, 'olusturan_id');
    }

    public function mesajlar()
    {
        return $this->hasMany(TaskMessage::class, 'task_id')->orderBy('id');
    }
}