<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $type,
        public ?string $verifyUrl = null
    ) {}

    public function build(): self
    {
        $subject = $this->type === 'password_reset'
            ? 'RoomGate password reset code'
            : 'RoomGate email verification code';

        return $this->subject($subject)
            ->view('emails.otp-code')
            ->with([
                'code' => $this->code,
                'type' => $this->type,
                'verifyUrl' => $this->verifyUrl,
            ]);
    }
}
