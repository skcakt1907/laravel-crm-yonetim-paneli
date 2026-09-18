<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ayar extends Model
{
    protected $table = 'ayarlar';
    
    protected $fillable = [
        'site_baslik', 'site_url', 'domain_url', 'site_tema', 'firma_logo',
        'firma_footerlogo', 'favicon', 'firma_adi', 'firma_telefon', 'firma_fax',
        'firma_email', 'firma_adres', 'google_maps', 'google_analytics',
        'dogrulama_kodu', 'canli_destek', 'whatsapp', 'facebook', 'twitter',
        'instagram', 'linkedin', 'youtube', 'copyright', 'site_desc', 'site_keyw',
        'renk1', 'renk2', 'renk3', 'defaultsms', 'defaultpayment'
    ];
}
