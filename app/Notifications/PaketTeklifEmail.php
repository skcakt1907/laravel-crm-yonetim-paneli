<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaketTeklifEmail extends Notification
{
    use Queueable;

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $uye             = $this->data['uye'];
        $paketler        = $this->data['paketler'];
        $paraBirimi      = $this->data['para_birimi'] ?? 'TL';
        $toplamTl        = (float) ($this->data['toplam_tl'] ?? 0);
        $toplamParaBirimi = (float) ($this->data['toplam_para_birimi'] ?? 0);
        $teklifId        = $this->data['teklif_id'] ?? null;

        $detayUrl = rtrim(config('app.url'), '/') . '/hesabim/teklifler';

        return (new MailMessage)
            ->subject('📦 Size Özel Paket Teklifi')
            ->view('emails.paket-teklif', [
                'uye'              => $uye,
                'paketler'         => $paketler,
                'paraBirimi'       => $paraBirimi,
                'toplamTl'         => $toplamTl,
                'toplamParaBirimi' => $toplamParaBirimi,
                'teklifId'         => $teklifId,
                'detayUrl'         => $detayUrl,
            ]);
    }
}