<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        static $cached = null;
        if ($cached instanceof self) {
            return $cached;
        }

        $cached = self::query()->first();
        if (!$cached) {
            $cached = self::query()->create([
                'app_name' => 'RoomGate',
                'app_short_name' => 'RoomGate',
                'company_name' => 'RoomGate',
            ]);
        }

        return $cached;
    }
}
