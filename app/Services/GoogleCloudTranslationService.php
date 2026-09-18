<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCloudTranslationService
{
    private string $apiKey;
    private string $apiUrl = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey
            ?? config('services.google_translate.api_key')
            ?? env('GOOGLE_CLOUD_TRANSLATE_KEY', '');
    }

    public function translate(string $text, string $targetLanguage = 'en', string $sourceLanguage = 'tr', string $format = 'html'): string
    {
        if ($text === '' || $this->apiKey === '') {
            return $text;
        }

        try {
            $response = Http::post($this->apiUrl, [
                'q'      => $text,
                'source' => strtolower($sourceLanguage),
                'target' => strtolower($targetLanguage),
                'format' => $format, // 'text' veya 'html'
                'key'    => $this->apiKey,
            ]);

            if ($response->successful()) {
                return $response->json('data.translations.0.translatedText') ?? $text;
            }

            Log::error('Google Translate API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Google Translate API exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return $text;
    }
}


