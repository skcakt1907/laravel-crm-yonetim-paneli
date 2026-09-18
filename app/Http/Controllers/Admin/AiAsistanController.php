<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * AI Asistan — tam sayfa giriş (Faz 1).
 * Şimdilik SADECE GÖRÜNÜM / "yakında" modu; backend (Claude) API anahtarı gelince eklenecek.
 */
class AiAsistanController extends Controller
{
    public function index()
    {
        return view('admin.ai-asistan.index');
    }
}
