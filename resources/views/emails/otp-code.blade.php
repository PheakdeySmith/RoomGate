<!doctype html>
<html lang="en">
  <body style="margin:0;padding:0;background:#f5f6fa;font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa;padding:32px 0;">
      <tr>
        <td align="center">
          <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;padding:28px 28px 24px;">
            <tr>
              <td>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                  <div style="font-weight:800;letter-spacing:0.4px;color:#1f2a44;font-size:18px;">RoomGate</div>
                  <a href="{{ url('/') }}" style="text-decoration:none;border:1px solid #d9dfea;color:#1f2a44;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;">Go to RoomGate</a>
                </div>

                <h2 style="margin:0 0 10px;color:#1f2a44;font-size:22px;">
                  {{ $type === 'password_reset' ? 'Reset your password' : 'Complete registration' }}
                </h2>
                <p style="margin:0 0 18px;color:#6b7280;font-size:14px;line-height:1.6;">
                  Please enter this confirmation code in the window where you started creating your account:
                </p>

                <div style="background:#f3f5fb;border-radius:8px;padding:18px;text-align:center;font-size:26px;font-weight:700;letter-spacing:6px;color:#1f2a44;margin-bottom:18px;">
                  {{ $code }}
                </div>

                <p style="margin:0 0 12px;color:#6b7280;font-size:13px;line-height:1.6;">
                  From your device use the code to confirm email.
                </p>

                @if ($type === 'email_verify' && !empty($verifyUrl))
                  <p style="margin:0 0 8px;color:#6b7280;font-size:13px;line-height:1.6;">
                    Or click this button to confirm your email:
                  </p>
                  <div style="margin:10px 0 18px;">
                    <a href="{{ $verifyUrl }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;">
                      Confirm your email
                    </a>
                  </div>
                @endif

                <p style="margin:0;color:#6b7280;font-size:12px;">
                  If you didn't create an account in RoomGate, please ignore this message.
                </p>
              </td>
            </tr>
          </table>
          <p style="margin:14px 0 0;color:#9aa3b2;font-size:11px;text-align:center;">
            You have received this notification because you signed up for RoomGate.
          </p>
        </td>
      </tr>
    </table>
  </body>
</html>
