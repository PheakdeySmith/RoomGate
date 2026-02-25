# RoomGate-only settings roadmap

## Scope synced from school-system

### High value
- [x] Two Factor Setting
- [x] Email Settings
- [x] SMS Settings
- [x] API Permission
- [x] Cron Job visibility
- [x] Backup (run + status)

### Medium value
- [x] General Settings (centralized)
- [x] Manage Currency
- [x] Notification Setting
- [x] Language Settings / Language
- [x] Utility settings (requested add-on)

## Implementation checklist
- [x] Add platform meta settings storage (`business_settings.meta`)
- [x] Add managed currencies table + model
- [x] Add admin System Setup routes and controller actions
- [x] Add admin System Setup UI page with sections/forms
- [x] Add role/permission hook (`system_setup.manage`)
- [x] Add API access middleware (`api.access`) and attach to sanctum API routes
- [x] Add login 2FA challenge flow with OTP + email delivery
- [x] Add runtime application of locale/timezone/notification/mail settings
- [x] Add backup artisan commands (db, uploads, all) + schedule
- [x] Wire utility defaults into utility billing flow
- [x] Add/extend tests for system setup + 2FA + API middleware
- [x] Run full regression test suite

## Notes on fit with current RoomGate implementation
- Preserved existing role middleware (`platform_admin|admin`) and added optional permission-based hardening.
- Reused existing OTP + mail infrastructure instead of introducing new auth stack.
- Reused existing utility module and only added default behavior knobs.
- Kept webhook routes unaffected by API role gate to avoid payment/outbound breakage.
