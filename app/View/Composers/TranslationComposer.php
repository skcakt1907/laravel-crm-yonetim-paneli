<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Helpers\TranslationHelper;

class TranslationComposer
{
    /**
     * View'a çeviri helper'ını ekle
     */
    public function compose(View $view): void
    {
        $view->with('translate', function ($text) {
            return TranslationHelper::translate($text);
        });
        
        $view->with('t', function ($text) {
            return TranslationHelper::translate($text);
        });
    }
}


