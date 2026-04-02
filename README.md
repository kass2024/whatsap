# WhatsApp customer support (Flutter + Laravel)

## Layout

- `wa-support-api` — Laravel 13 REST API, WhatsApp Cloud webhook, Sanctum auth, 24h session rules, FCM.
- `wa_support_mobile` — Flutter agent app (chat UI, polling, templates when session expired).

## Backend setup

1. Create MySQL database `wa_support` (or adjust `.env`).
2. `cd wa-support-api`
3. `composer install`
4. Copy `.env.example` to `.env` and set `APP_KEY` (`php artisan key:generate`), WhatsApp and FCM variables.
5. `php artisan migrate --seed` (default users: `admin@example.com` / `password`, `agent@example.com` / `password`).
6. `php artisan storage:link`
7. `php artisan serve --host=0.0.0.0 --port=8000`

### Webhook (Meta)

- Callback URL: `https://YOUR_PUBLIC_DOMAIN/webhook/whatsapp`
- Verify token: must match `WHATSAPP_VERIFY_TOKEN` in `.env`
- `WHATSAPP_APP_SECRET` is used to validate `X-Hub-Signature-256` on incoming POSTs

### Media URLs for the mobile app

Set `APP_URL` to the same base URL devices can reach (e.g. `http://192.168.1.10:8000`). WhatsApp downloads inbound media into `storage/app/public/wa-media`; the API returns URLs under `/storage/...`.

### FCM (HTTP v1)

1. Create a Firebase project and download a service account JSON.
2. Set `FCM_PROJECT_ID` and `FCM_SERVICE_ACCOUNT_PATH` (absolute path to the JSON file).

## Flutter app

1. Install Flutter SDK and add to PATH.
2. `cd wa_support_mobile`
3. `flutter create .` (generates `android/`, `ios/`, etc. without overwriting `lib/`)
4. `flutter pub get`
5. Configure Firebase for Android/iOS (`google-services.json` / `GoogleService-Info.plist`) if you use FCM.
6. Run with API base URL override, e.g.  
   `flutter run --dart-define=API_BASE=http://192.168.1.10:8000/api`

Android emulator uses `http://10.0.2.2:8000/api` by default in `lib/config/api_config.dart`. For HTTP (not HTTPS) on Android 9+, you may need a network security config allowing cleartext to your dev host.

## Security

Rotate any API tokens copied from another project if they may have been exposed. Never commit `.env` or service account JSON.
