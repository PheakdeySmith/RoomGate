<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Services\TranslationExporter;
use Illuminate\Http\Request;

class AdminTranslationController extends Controller
{
    private const LOCALES = ['en', 'km'];

    public function index()
    {
        $translations = Translation::query()
            ->whereIn('locale', self::LOCALES)
            ->orderBy('key')
            ->get()
            ->groupBy('key');

        $rows = $translations->map(function ($group, $key) {
            $values = $group->keyBy('locale');
            return [
                'key' => $key,
                'en' => optional($values->get('en'))->text,
                'km' => optional($values->get('km'))->text,
            ];
        })->values();

        return view('admin::dashboard.translations', [
            'rows' => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'en' => ['nullable', 'string'],
            'km' => ['nullable', 'string'],
        ]);

        foreach (self::LOCALES as $locale) {
            if (!array_key_exists($locale, $validated)) {
                continue;
            }
            Translation::updateOrCreate(
                ['key' => $validated['key'], 'locale' => $locale],
                ['text' => $validated[$locale]]
            );
        }

        TranslationExporter::exportLocales(self::LOCALES);

        return back()->with('status', 'Translation created.');
    }

    public function update(Request $request, string $key)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'en' => ['nullable', 'string'],
            'km' => ['nullable', 'string'],
        ]);

        if ($key !== $validated['key']) {
            Translation::query()->where('key', $key)->update(['key' => $validated['key']]);
        }

        foreach (self::LOCALES as $locale) {
            if (!array_key_exists($locale, $validated)) {
                continue;
            }
            Translation::updateOrCreate(
                ['key' => $validated['key'], 'locale' => $locale],
                ['text' => $validated[$locale]]
            );
        }

        TranslationExporter::exportLocales(self::LOCALES);

        return back()->with('status', 'Translation updated.');
    }

    public function destroy(string $key)
    {
        Translation::query()->where('key', $key)->delete();
        TranslationExporter::exportLocales(self::LOCALES);

        return back()->with('status', 'Translation deleted.');
    }
}
