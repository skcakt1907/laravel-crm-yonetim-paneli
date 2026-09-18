<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class Uye extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    
    protected $table = 'uyeler';
    
    // Eski veritabanı yapısı - timestamps yok
    public $timestamps = false;
    
    protected $fillable = [
        'ad', 'soyad', 'email', 'kullanici_adi', 'sifre', 'telefon', 'tc',
        'firmaadi', 'vergino', 'vergidairesi', 'bakiye', 'dnbank_bakiye', 'durum',
        'ip', 'tarih', 'ktarih', 'utipi', 'cinsiyet', 'dtarih',
        'nereden_duydunuz', 'notlar', 'profil', 'bayi', 'remember_token',
        'son_giris', 'email_verified_at',
        // Adres bilgileri
        'ulke', 'sehir', 'adres',
        // Fatura bilgileri
        'fatura_unvan', 'fatura_tc', 'fatura_adres', 'fatura_sehir', 'fatura_ulke',
        // Bildirim tercihleri (her iki kolon adı varyantı)
        'email_bildirim', 'sms_bildirim', 'email_bildirimleri', 'sms_bildirimleri',
        // Sözleşme / onaylar
        'hizmet_sozlesme', 'gizlilik_sozlesme', 'kvkk_onay',
    ];
    
    protected $hidden = [
        'sifre', 'remember_token'
    ];
    
    protected $casts = [
        'durum' => 'integer',
        'bayi' => 'integer',
        'utipi' => 'integer',
        'email_verified_at' => 'datetime',
    ];
    
    // Laravel Auth için password field mapping
    public function getAuthPassword()
    {
        return $this->sifre;
    }
    
    /**
     * Email doğrulama notification'ını gönder
     */
    public function sendEmailVerificationNotification()
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addHours(24),
            [
                'id' => $this->id,
                'hash' => sha1($this->email),
            ]
        );
        
        \Mail::to($this->email)->send(new \App\Mail\EmailVerificationMail($this, $verificationUrl));
    }
}