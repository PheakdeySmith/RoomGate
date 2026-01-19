<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BusinessSetting extends Model
{
    protected $fillable = [
        'app_name',
        'app_short_name',
        'company_name',
        'tagline',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'support_email',
        'support_phone',
        'terms_url',
        'privacy_url',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'telegram_url',
        'logo_light_path',
        'logo_dark_path',
        'logo_small_path',
        'login_logo_path',
        'footer_logo_path',
        'favicon_path',
    ];

    public static function current(): self
    {
        return Cache::remember('business_settings:current', 300, function () {
            $settings = self::query()->first();
            if (!$settings) {
                $settings = self::query()->create([
                    'app_name' => 'RoomGate',
                    'app_short_name' => 'RoomGate',
                    'company_name' => 'RoomGate',
                ]);
            }

            return $settings;
        });
    }
}
