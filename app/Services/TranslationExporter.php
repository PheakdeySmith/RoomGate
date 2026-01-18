<?php

namespace App\Services;

use App\Models\Translation;

class TranslationExporter
{
    public static function exportLocales(array $locales): void
    {
        foreach ($locales as $locale) {
            self::exportLocale($locale);
        }
    }

    public static function exportLocale(string $locale): void
    {
        $basePath = public_path('assets/assets/json/locales');
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $filePath = $basePath.DIRECTORY_SEPARATOR.$locale.'.json';
        $baseData = [];
        if (is_file($filePath)) {
            $decoded = json_decode(file_get_contents($filePath), true);
            if (is_array($decoded)) {
                $baseData = $decoded;
            }
        }

        $dbData = Translation::query()
            ->where('locale', $locale)
            ->pluck('text', 'key')
            ->all();

        $merged = array_merge($baseData, $dbData);
        file_put_contents(
            $filePath,
            json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
