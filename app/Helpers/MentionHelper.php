<?php

namespace App\Helpers;

/**
 * MentionHelper
 * 
 * Not içeriğindeki @ad_soyad pattern'lerini HTML'e çevirir.
 */
class MentionHelper
{
    /**
     * Bir metindeki @mention'ları stilli HTML span'lara çevirir.
     * XSS güvenliği için önce escape edilir.
     */
    public static function renderMentions($text)
    {
        if (empty($text)) return '';
        
        // Önce escape et (XSS güvenliği)
        $escaped = e($text);
        
        // @ad_soyad pattern'ini stillendir
        $rendered = preg_replace_callback(
            '/@([a-zA-ZığüşöçĞÜŞÖÇİı_]+(?:_[a-zA-ZığüşöçĞÜŞÖÇİı]+)*)/u',
            function($matches) {
                $name = str_replace('_', ' ', $matches[1]);
                return '<span class="mention" title="' . e($matches[0]) . '">@' . e($name) . '</span>';
            },
            $escaped
        );
        
        // Satır sonlarını koru
        return nl2br($rendered);
    }
}