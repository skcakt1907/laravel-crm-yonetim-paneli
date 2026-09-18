<?php

namespace App\Observers;

use App\Models\Package;

class PackageObserver
{
    public function creating(Package $package): void
    {
        // Otomatik çeviri kaldırıldı; manuel değerler korunur.
    }

    public function updating(Package $package): void
    {
        // Otomatik çeviri kaldırıldı; manuel değerler korunur.
    }
}





















