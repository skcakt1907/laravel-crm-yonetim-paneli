<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BayiOdemeOnayEmail extends Notification
{
    use Queueable;

    protected $odeme;
    
    public function __construct($odeme)
    {
        $this->odeme = $odeme;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('✅ Ödeme Talebiniz Onaylandı!')
            ->greeting('Merhaba ' . $notifiable->adi . '!')
            ->line('Ödeme talebiniz onaylandı ve işleme alındı.')
            ->line('**Ödeme Tutarı:** ₺' . number_format($this->odeme->tutar, 2))
            ->line('**Banka:** ' . ($this->odeme->banka_adi ?? 'Kayıtlı Hesap'))
            ->line('**IBAN:** ' . ($this->odeme->iban ?? '-'))
            ->line('Ödeme 1-3 iş günü içerisinde hesabınıza aktarılacaktır.')
            ->action('Ödeme Geçmişi', url('/admin/bayi/odeme-gecmisi'))
            ->line('İyi çalışmalar dileriz!');
    }
}
