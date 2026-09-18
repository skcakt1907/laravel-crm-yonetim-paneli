<?php

namespace App\Observers;

use App\Models\Advert;

class AdvertObserver
{
    /** Otomatik çeviri kaldırıldı; yeni ilanlarda ek işlem yapılmaz. */
    public function created(Advert $advert): void
    {
    }
}