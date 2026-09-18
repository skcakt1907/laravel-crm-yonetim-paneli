<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SyncPackageTranslations extends Command
{
    protected $signature = 'translations:sync-packages 
        {--locale=* : Target locale(s), defaults to en,ar}
        {--dry-run : Preview changes without writing files}
        {--limit= : Optional limit for number of records processed}
        {--force-remote : Force remote translation even if disabled}';

    protected $description = 'Generate or update package/category translation dictionaries based on database contents.';

    protected array $defaultLocales = ['en', 'ar'];
    protected array $categoryNames = [];

    public function handle(): int
    {
        $this->info('Starting package translation sync…');

        $locales = $this->option('locale');
        $locales = is_array($locales) && count($locales) ? $locales : $this->defaultLocales;

        $limit = $this->option('limit');
        $limit = is_numeric($limit) ? (int) $limit : null;

        $texts = $this->collectTexts($limit);

        if ($texts->isEmpty()) {
            $this->warn('No package or category titles found.');
            return self::SUCCESS;
        }

        $this->line(sprintf('Found %d unique titles to process.', $texts->count()));

        $dryRun = (bool) $this->option('dry-run');
        $forceRemote = (bool) $this->option('force-remote');

        $results = [];

        foreach ($locales as $locale) {
            $results[$locale] = $this->syncLocale($locale, $texts->toArray(), $dryRun, $forceRemote);
        }

        if ($dryRun) {
            $this->warn('Dry run complete. No files were written.');
        } else {
            $this->info('Translation dictionaries updated.');
        }

        foreach ($results as $locale => $stats) {
            $this->line(sprintf(
                '[%s] total=%d existing=%d new=%d skipped=%d failed=%d',
                $locale,
                $stats['total'],
                $stats['existing'],
                $stats['new'],
                $stats['skipped'],
                $stats['failed']
            ));
        }

        return self::SUCCESS;
    }

    protected function collectTexts(?int $limit = null)
    {
        $categories = DB::table('web_kategori')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->pluck('adi')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        $this->categoryNames = $categories->toArray();

        $packages = DB::table('yazilimlar')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->pluck('adi');

        return $packages
            ->merge($categories)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();
    }

    protected function syncLocale(string $locale, array $texts, bool $dryRun, bool $forceRemote): array
    {
        $this->line(sprintf('→ Processing locale [%s]', $locale));

        $packageFile = $this->generatedPath($locale, 'package_titles.php');
        $categoryFile = $this->generatedPath($locale, 'category_titles.php');

        $existingPackages = $this->loadArray($packageFile);
        $existingCategories = $this->loadArray($categoryFile);

        // Merge manual dictionaries from messages.php
        $messages = $this->loadMessagesArray($locale);
        $existingPackages = array_merge($messages['package_titles'], $existingPackages);
        $existingCategories = array_merge($messages['category_titles'], $existingCategories);

        $stats = [
            'total' => count($texts),
            'existing' => 0,
            'new' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $newPackages = $existingPackages;
        $newCategories = $existingCategories;

        foreach ($texts as $text) {
            if (isset($newPackages[$text]) || isset($newCategories[$text])) {
                $stats['existing']++;
                continue;
            }

            $translated = $this->translate($text, $locale, $forceRemote);

            if (!$translated) {
                $stats['failed']++;
                continue;
            }

            if ($this->looksLikeCategory($text)) {
                $newCategories[$text] = $translated;
            } else {
                $newPackages[$text] = $translated;
            }

            $stats['new']++;
        }

        ksort($newPackages, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($newCategories, SORT_NATURAL | SORT_FLAG_CASE);

        if (!$dryRun) {
            $this->writeArray($packageFile, $newPackages);
            $this->writeArray($categoryFile, $newCategories);
        } else {
            $this->line(sprintf('Would write %d package titles and %d category titles.', count($newPackages), count($newCategories)));
        }

        return $stats;
    }

    protected function translate(string $text, string $targetLocale, bool $forceRemote): ?string
    {
        if ($targetLocale === 'tr') {
            return $text;
        }

        if (!$forceRemote && !$this->remoteTranslationEnabled()) {
            return null;
        }

        try {
            $url = 'https://translate.googleapis.com/translate_a/single';
            $params = [
                'client' => 'gtx',
                'sl' => 'tr',
                'tl' => $targetLocale,
                'dt' => 't',
                'q' => $text,
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'Accept: application/json',
                        'Accept-Language: en-US,en;q=0.9',
                    ],
                    'timeout' => 10,
                ],
            ]);

            $response = @file_get_contents($url . '?' . http_build_query($params), false, $context);

            if ($response === false) {
                $this->warn("Failed to translate '{$text}' for locale {$targetLocale}");
                return null;
            }

            $data = json_decode($response, true);

            if (!is_array($data) || !isset($data[0])) {
                $this->warn("Unexpected response translating '{$text}' for locale {$targetLocale}");
                return null;
            }

            $translated = '';
            foreach ($data[0] as $chunk) {
                if (isset($chunk[0])) {
                    $translated .= $chunk[0];
                }
            }

            return trim($translated) ?: null;
        } catch (\Throwable $throwable) {
            $this->warn("Error translating '{$text}' for locale {$targetLocale}: {$throwable->getMessage()}");
            return null;
        }
    }

    protected function generatedPath(string $locale, string $filename): string
    {
        $directory = lang_path("generated/{$locale}");

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0775, true, true);
        }

        return $directory . DIRECTORY_SEPARATOR . $filename;
    }

    protected function loadArray(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $data = include $path;

        return is_array($data) ? $data : [];
    }

    protected function writeArray(string $path, array $data): void
    {
        $export = $this->exportArray($data);
        File::put($path, $export);
    }

    protected function exportArray(array $data): string
    {
        $lines = ["<?php", "", "return ["];

        foreach ($data as $key => $value) {
            $lines[] = sprintf("    '%s' => '%s',", addslashes($key), addslashes($value));
        }

        $lines[] = "];";
        $lines[] = "";

        return implode(PHP_EOL, $lines);
    }

    protected function looksLikeCategory(string $text): bool
    {
        // Heuristic: if the title exists in categories table, treat as category.
        return in_array($text, $this->categoryNames, true);
    }

    protected function loadMessagesArray(string $locale): array
    {
        $path = lang_path("{$locale}/messages.php");

        if (!File::exists($path)) {
            return [
                'package_titles' => [],
                'category_titles' => [],
            ];
        }

        $data = include $path;

        return [
            'package_titles' => $data['package_titles'] ?? [],
            'category_titles' => $data['category_titles'] ?? [],
        ];
    }

    protected function remoteTranslationEnabled(): bool
    {
        $value = env('TRANSLATION_REMOTE', false);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

