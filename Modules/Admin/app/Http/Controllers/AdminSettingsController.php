<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    public function edit()
    {
        return view('admin::dashboard.settings', [
            'settings' => BusinessSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        $settings = BusinessSetting::current();

        $validated = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_short_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:100'],
            'terms_url' => ['nullable', 'url', 'max:255'],
            'privacy_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'telegram_url' => ['nullable', 'url', 'max:255'],
            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'logo_small' => ['nullable', 'image', 'max:2048'],
            'login_logo' => ['nullable', 'image', 'max:2048'],
            'footer_logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ]);

        $settings->fill($validated);

        $settings->logo_light_path = $this->storeAsset($request, 'logo_light', $settings->logo_light_path);
        $settings->logo_dark_path = $this->storeAsset($request, 'logo_dark', $settings->logo_dark_path);
        $settings->logo_small_path = $this->storeAsset($request, 'logo_small', $settings->logo_small_path);
        $settings->login_logo_path = $this->storeAsset($request, 'login_logo', $settings->login_logo_path);
        $settings->footer_logo_path = $this->storeAsset($request, 'footer_logo', $settings->footer_logo_path);
        $settings->favicon_path = $this->storeAsset($request, 'favicon', $settings->favicon_path);

        $settings->save();

        return back()->with('status', 'Settings updated.');
    }

    private function storeAsset(Request $request, string $key, ?string $currentPath): ?string
    {
        if (!$request->hasFile($key)) {
            return $currentPath;
        }

        $file = $request->file($key);
        $filename = uniqid('branding_', true) . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path('uploads/images');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $file->move($targetDir, $filename);
        $path = 'uploads/images/' . $filename;

        app(AuditLogger::class)->log(
            'uploaded',
            'BrandAsset',
            $path,
            null,
            ['path' => $path, 'field' => $key],
            $request
        );

        return $path;
    }
}
