<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpCode extends Model
{
    public const TYPE_EMAIL_VERIFY = 'email_verify';
    public const TYPE_PASSWORD_RESET = 'password_reset';

    protected $fillable = [
        'user_id',
        'email',
        'type',
        'code_hash',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public static function issue(string $email, string $type, ?int $userId, CarbonInterface $expiresAt): string
    {
        self::query()
            ->where('email', $email)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        self::create([
            'user_id' => $userId,
            'email' => Str::lower($email),
            'type' => $type,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        return $code;
    }

    public static function verifyCode(string $email, string $type, string $code): ?self
    {
        $record = self::query()
            ->where('email', Str::lower($email))
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (!$record) {
            return null;
        }

        if (!Hash::check($code, $record->code_hash)) {
            return null;
        }

        return $record;
    }
}
