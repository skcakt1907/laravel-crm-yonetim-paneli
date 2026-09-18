<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BayiYeniSatisEmail extends Notification
{
    use Queueable;

    protected $satis;
    
    public function __construct($satis)
    {
        $this->satis = $satis;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🎉 Yeni Satış Gerçekleşti!')
            ->greeting('Merhaba ' . $notifiable->adi . '!')
            ->line('Yeni bir satış gerçekleştirdiniz.')
            ->line('**Satış Tutarı:** ₺' . number_format($this->satis->satis_tutari, 2))
            ->line('**Komisyon:** ₺' . number_format($this->satis->komisyon_tutari, 2))
            ->line('**Müşteri:** ' . ($this->satis->musteri_adi ?? 'Bilinmiyor'))
            ->action('Detayları Görüntüle', url('/admin/bayi/satislar'))
            ->line('Satışınız için teşekkür ederiz!');
    }
}
